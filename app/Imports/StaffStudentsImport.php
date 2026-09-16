<?php

declare(strict_types=1);

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use RuntimeException;

final class StaffStudentsImport implements ToCollection, WithHeadingRow
{
    public int $importedCount = 0;

    public int $skippedCount = 0;

    public function __construct(
        private readonly int $institutionSemesterId,
        private readonly bool $isFullScope,
        private readonly array $allowedPeriods = [],
    ) {
    }

    public function collection(Collection $rows): void
    {
        $preparedRows = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            $data = $this->normalizeRow($row->toArray());

            if (
                $data['student_code'] === '' &&
                $data['national_id'] === '' &&
                $data['name_ar'] === ''
            ) {
                continue;
            }

            $validator = Validator::make(
                $data,
                [
                    'student_code' => ['required', 'string', 'max:100'],
                    'national_id' => ['required', 'string', 'max:100'],
                    'name_ar' => ['required', 'string', 'max:255'],
                    'name_en' => ['nullable', 'string', 'max:255'],
                    'class_group' => ['required', 'string', 'max:255'],
                    'academic_level' => ['required', 'string', 'max:255'],
                    'enrollment_status' => [
                        'required',
                        'in:draft,active,suspended',
                    ],
                ],
                [
                    'student_code.required' =>
                        "Row {$rowNumber}: Student Code is required.",

                    'national_id.required' =>
                        "Row {$rowNumber}: National ID is required.",

                    'name_ar.required' =>
                        "Row {$rowNumber}: Arabic Name is required.",

                    'class_group.required' =>
                        "Row {$rowNumber}: Class Group is required.",

                    'academic_level.required' =>
                        "Row {$rowNumber}: Academic Level is required.",

                    'enrollment_status.required' =>
                        "Row {$rowNumber}: Enrollment Status is required.",

                    'enrollment_status.in' =>
                        "Row {$rowNumber}: Enrollment Status must be draft, active or suspended.",
                ]
            );

            if ($validator->fails()) {
                throw new RuntimeException(
                    $validator->errors()->first()
                );
            }

            $preparedRows[] = [
                'row_number' => $rowNumber,
                ...$data,
            ];
        }

        if (empty($preparedRows)) {
            throw new RuntimeException(
                __('ui.student_excel_empty', [], null, 'The Excel file contains no student records.')
            );
        }

        DB::transaction(function () use ($preparedRows): void {
            foreach ($preparedRows as $data) {
                $this->importStudent($data);
            }
        });
    }

    private function importStudent(array $data): void
    {
        $rowNumber = $data['row_number'];

        /*
         * Find the class group inside the staff member's scope.
         */
        $classGroupQuery = DB::table('class_groups as cg')
            ->join(
                'academic_levels as al',
                'al.id',
                '=',
                'cg.academic_level_id'
            )
            ->where(
                'cg.institution_semester_id',
                $this->institutionSemesterId
            )
            ->where(
                'cg.name_ar',
                $data['class_group']
            )
            ->where(
                'al.name_ar',
                $data['academic_level']
            )
            ->whereNotIn(
                'cg.lifecycle_status',
                ['archived']
            );

        if (! $this->isFullScope) {
            if (empty($this->allowedPeriods)) {
                throw new RuntimeException(
                    "Row {$rowNumber}: You do not have an allowed operational period."
                );
            }

            $classGroupQuery->whereIn(
                'cg.operational_period_id',
                $this->allowedPeriods
            );
        }

        $classGroup = $classGroupQuery->first([
            'cg.id',
            'cg.operational_period_id',
        ]);

        if (! $classGroup) {
            throw new RuntimeException(
                "Row {$rowNumber}: Class Group '{$data['class_group']}' with Academic Level '{$data['academic_level']}' was not found in your permitted scope."
            );
        }

        /*
         * Check whether this student already exists.
         */
        $person = DB::table('people')
            ->where('national_id', $data['national_id'])
            ->first();

        if ($person) {
            $existingProfile = DB::table('student_profiles')
                ->where('person_id', $person->id)
                ->first();

            if ($existingProfile) {
                $existingEnrollment = DB::table('student_enrollments')
                    ->where('student_profile_id', $existingProfile->id)
                    ->where(
                        'institution_semester_id',
                        $this->institutionSemesterId
                    )
                    ->first();

                if ($existingEnrollment) {
                    $this->skippedCount++;

                    return;
                }

                /*
                 * Existing student profile but no enrollment
                 * in the current semester.
                 */
                DB::table('student_enrollments')->insert([
                    'student_profile_id' => $existingProfile->id,
                    'institution_semester_id' => $this->institutionSemesterId,
                    'class_group_id' => $classGroup->id,
                    'enrollment_status' => $data['enrollment_status'],
                    'enrolled_on' => now()->toDateString(),
                    'activated_on' => $data['enrollment_status'] === 'active'
                        ? now()
                        : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->importedCount++;

                return;
            }
        }

        /*
         * Create person.
         */
        if ($person) {
            $personId = $person->id;

            DB::table('people')
                ->where('id', $personId)
                ->update([
                    'full_name_ar' => $data['name_ar'],
                    'full_name_en' => $data['name_en'] !== ''
                        ? $data['name_en']
                        : null,
                    'updated_at' => now(),
                ]);
        } else {
            $personId = DB::table('people')->insertGetId([
                'national_id' => $data['national_id'],
                'full_name_ar' => $data['name_ar'],
                'full_name_en' => $data['name_en'] !== ''
                    ? $data['name_en']
                    : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        /*
         * Prevent duplicate student code.
         */
        $studentCodeExists = DB::table('student_profiles')
            ->where('student_code', $data['student_code'])
            ->exists();

        if ($studentCodeExists) {
            throw new RuntimeException(
                "Row {$rowNumber}: Student Code '{$data['student_code']}' already exists."
            );
        }

        /*
         * Create student profile.
         */
        $studentProfileId = DB::table('student_profiles')->insertGetId([
            'person_id' => $personId,
            'student_code' => $data['student_code'],
            'lifecycle_status' => 'active',
            'registered_on' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        /*
         * Create enrollment.
         */
        DB::table('student_enrollments')->insert([
            'student_profile_id' => $studentProfileId,
            'institution_semester_id' => $this->institutionSemesterId,
            'class_group_id' => $classGroup->id,
            'enrollment_status' => $data['enrollment_status'],
            'enrolled_on' => now()->toDateString(),
            'activated_on' => $data['enrollment_status'] === 'active'
                ? now()
                : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->importedCount++;
    }

    /**
     * Accept both English headings from the exported Excel
     * and Arabic translated headings.
     */
    private function normalizeRow(array $row): array
    {
        return [
            'student_code' => $this->value(
                $row,
                [
                    'student_code',
                    'رمز الطالب',
                ]
            ),

            'national_id' => $this->value(
                $row,
                [
                    'national_id',
                    'رقم الهوية',
                ]
            ),

            'name_ar' => $this->value(
                $row,
                [
                    'arabic_name',
                    'الاسم بالعربية',
                ]
            ),

            'name_en' => $this->value(
                $row,
                [
                    'english_name',
                    'الاسم بالإنجليزية',
                ]
            ),

            'class_group' => $this->value(
                $row,
                [
                    'class_group',
                    'الشعبة الصفية',
                ]
            ),

            'academic_level' => $this->value(
                $row,
                [
                    'academic_level',
                    'المرحلة الدراسية',
                ]
            ),

            'enrollment_status' => $this->normalizeStatus(
                $this->value(
                    $row,
                    [
                        'enrollment_status',
                        'حالة التسجيل',
                    ]
                )
            ),
        ];
    }

    private function value(array $row, array $keys): string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row)) {
                return trim((string) ($row[$key] ?? ''));
            }
        }

        return '';
    }

    private function normalizeStatus(string $status): string
    {
        $status = trim($status);

        return match ($status) {
            'draft',
            'مسودة' => 'draft',

            'active',
            'نشط',
            'فعال',
            'فعالة' => 'active',

            'suspended',
            'موقوف',
            'معلقة',
            'معلق' => 'suspended',

            default => $status,
        };
    }
}

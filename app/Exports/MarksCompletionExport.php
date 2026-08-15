<?php

declare(strict_types=1);

namespace App\Exports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Excel export for the marks completion / mark-sheet status dashboard.
 *
 * Each row is one non-superseded mark sheet, showing class group, subject,
 * assigned teacher, status, and version.
 *
 * @param array<int>|null $allowedPeriodIds  When set, restricts results to
 *   class groups whose operational_period_id is in this list. Used by the
 *   staff portal to enforce period grants server-side.
 */
final class MarksCompletionExport implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    public function __construct(
        private readonly int    $institutionSemesterId,
        private readonly ?int   $classGroupId,
        private readonly string $locale = 'ar',
        /** @var array<int>|null */
        private readonly ?array $allowedPeriodIds = null,
    ) {}

    public function title(): string
    {
        return $this->locale === 'ar' ? 'اكتمال إدخال الدرجات' : 'Marks Completion';
    }

    public function headings(): array
    {
        if ($this->locale === 'ar') {
            return ['المجموعة الدراسية', 'المادة', 'المعلم', 'الحالة', 'الإصدار', 'تاريخ التقديم', 'تاريخ الموافقة'];
        }

        return ['Class Group', 'Subject', 'Teacher', 'Status', 'Version', 'Submitted At', 'Approved At'];
    }

    public function collection(): Collection
    {
        $cgNameField      = $this->locale === 'ar' ? 'cg.name_ar' : 'cg.name_ar';
        $subjectNameField = $this->locale === 'ar' ? 's.name_ar' : 's.name_en';
        $staffNameField   = $this->locale === 'ar' ? 'p.full_name_ar' : 'p.full_name_en';

        $query = DB::table('mark_sheets as ms')
            ->join('class_groups as cg', 'cg.id', '=', 'ms.class_group_id')
            ->join('institution_subject_offerings as iso', 'iso.id', '=', 'ms.subject_offering_id')
            ->join('subjects as s', 's.id', '=', 'iso.subject_id')
            ->join('teaching_assignments as ta', 'ta.id', '=', 'ms.teaching_assignment_id')
            ->join('staff_profiles as sp', 'sp.id', '=', 'ta.staff_profile_id')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->where('ms.institution_semester_id', $this->institutionSemesterId)
            ->whereNull('ms.superseded_by_id')
            ->orderBy('cg.name_ar')
            ->orderBy('s.name_ar');

        // Period-grant restriction: enforced server-side so a forged classGroupId
        // cannot expose sheets from periods outside the staff member's grants.
        if ($this->allowedPeriodIds !== null) {
            if (count($this->allowedPeriodIds) === 0) {
                return collect();
            }
            $query->whereIn('cg.operational_period_id', $this->allowedPeriodIds);
        }

        if ($this->classGroupId) {
            $query->where('ms.class_group_id', $this->classGroupId);
        }

        return $query->select(
            DB::raw("$cgNameField as class_group_name"),
            DB::raw("$subjectNameField as subject_name"),
            DB::raw("COALESCE($staffNameField, p.full_name_ar) as teacher_name"),
            'ms.status',
            'ms.version',
            'ms.submitted_at',
            'ms.approved_at',
        )->get()->map(fn ($row) => [
            $row->class_group_name,
            $row->subject_name,
            $row->teacher_name,
            $this->translateStatus($row->status),
            $row->version,
            $row->submitted_at ? substr($row->submitted_at, 0, 10) : '',
            $row->approved_at  ? substr($row->approved_at, 0, 10) : '',
        ]);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 11]],
        ];
    }

    private function translateStatus(string $status): string
    {
        if ($this->locale === 'ar') {
            return match ($status) {
                'draft'     => 'مسودة',
                'submitted' => 'مُقدَّم',
                'returned'  => 'مُعاد',
                'verified'  => 'مُتحقَّق',
                'approved'  => 'مُعتمَد',
                default     => $status,
            };
        }

        return ucfirst($status);
    }
}

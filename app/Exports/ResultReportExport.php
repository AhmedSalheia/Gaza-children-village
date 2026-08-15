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
 * Excel export for published result publications.
 *
 * Each row is one result_publication_row joined to student and class data.
 * Only non-revoked, non-superseded publications are included.
 *
 * @param array<int>|null $allowedPeriodIds  When set, restricts results to
 *   class groups whose operational_period_id is in this list. Used by the
 *   staff portal to enforce period grants server-side.
 */
final class ResultReportExport implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
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
        return $this->locale === 'ar' ? 'تقرير النتائج المنشورة' : 'Published Results Report';
    }

    public function headings(): array
    {
        if ($this->locale === 'ar') {
            return ['المجموعة الدراسية', 'اسم الطالب', 'رقم الطالب', 'المادة', 'الدرجة (100)', 'التقدير', 'حالة الاكتمال', 'تاريخ النشر'];
        }

        return ['Class Group', 'Student Name', 'Student Code', 'Subject', 'Score (100)', 'Grade', 'Completeness', 'Published At'];
    }

    public function collection(): Collection
    {
        $cgNameField      = $this->locale === 'ar' ? 'cg.name_ar' : 'cg.name_ar';
        $studentNameField = $this->locale === 'ar' ? 'p.full_name_ar' : 'p.full_name_en';
        $subjectNameField = $this->locale === 'ar' ? 's.name_ar' : 's.name_en';

        $query = DB::table('result_publication_rows as rpr')
            ->join('result_publications as rp', 'rp.id', '=', 'rpr.result_publication_id')
            ->join('class_groups as cg', 'cg.id', '=', 'rp.class_group_id')
            ->join('student_enrollments as se', 'se.id', '=', 'rpr.enrollment_id')
            ->join('student_profiles as sp', 'sp.id', '=', 'se.student_profile_id')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->join('institution_subject_offerings as iso', 'iso.id', '=', 'rpr.subject_offering_id')
            ->join('subjects as s', 's.id', '=', 'iso.subject_id')
            ->where('rp.institution_semester_id', $this->institutionSemesterId)
            ->where('rp.status', 'published')
            ->whereNull('rp.superseded_by_id')
            ->orderBy('cg.name_ar')
            ->orderBy('p.full_name_ar')
            ->orderBy('s.name_ar');

        // Period-grant restriction: enforced server-side so a forged classGroupId
        // cannot expose results from periods outside the staff member's grants.
        if ($this->allowedPeriodIds !== null) {
            if (count($this->allowedPeriodIds) === 0) {
                return collect();
            }
            $query->whereIn('cg.operational_period_id', $this->allowedPeriodIds);
        }

        if ($this->classGroupId) {
            $query->where('rp.class_group_id', $this->classGroupId);
        }

        return $query->select(
            DB::raw("$cgNameField as class_group_name"),
            DB::raw("COALESCE($studentNameField, p.full_name_ar) as student_name"),
            'sp.student_code',
            DB::raw("$subjectNameField as subject_name"),
            'rpr.normalized_score',
            'rpr.grade_code',
            'rpr.completeness_status',
            'rp.published_at',
        )->get()->map(fn ($row) => [
            $row->class_group_name,
            $row->student_name,
            $row->student_code,
            $row->subject_name,
            $row->normalized_score !== null ? number_format((float) $row->normalized_score, 1) : '',
            $row->grade_code ?? '',
            $this->translateCompleteness($row->completeness_status),
            $row->published_at ? substr($row->published_at, 0, 10) : '',
        ]);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 11]],
        ];
    }

    private function translateCompleteness(string $status): string
    {
        if ($this->locale === 'ar') {
            return match ($status) {
                'complete'       => 'مكتمل',
                'incomplete'     => 'غير مكتمل',
                'all_absent'     => 'غياب كامل',
                'no_assessments' => 'لا توجد تقييمات',
                default          => $status,
            };
        }

        return match ($status) {
            'complete'       => 'Complete',
            'incomplete'     => 'Incomplete',
            'all_absent'     => 'All Absent',
            'no_assessments' => 'No Assessments',
            default          => ucfirst(str_replace('_', ' ', $status)),
        };
    }
}

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
 * Excel export for the student attendance report.
 *
 * Each row represents one student_attendance_record joined to the sheet,
 * student name, class group, and date.
 *
 * Filters applied at construction time; this class is a pure data transfer
 * object — no side effects, no DB writes.
 *
 * @param array<int>|null $allowedPeriodIds  When set, restricts results to
 *   sheets whose operational_period_id is in this list. Used by staff
 *   portal to enforce the authenticated user's period grants server-side,
 *   preventing forged classGroupId values from leaking out-of-scope data.
 */
final class AttendanceReportExport implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    public function __construct(
        private readonly int     $institutionSemesterId,
        private readonly ?int    $classGroupId,
        private readonly ?string $dateFrom,
        private readonly ?string $dateTo,
        private readonly string  $locale = 'ar',
        /** @var array<int>|null */
        private readonly ?array  $allowedPeriodIds = null,
    ) {}

    public function title(): string
    {
        return $this->locale === 'ar' ? 'تقرير الحضور' : 'Attendance Report';
    }

    public function headings(): array
    {
        if ($this->locale === 'ar') {
            return ['التاريخ', 'المجموعة الدراسية', 'اسم الطالب', 'رقم الطالب', 'الحالة', 'السبب', 'وقت الوصول', 'حالة السجل'];
        }

        return ['Date', 'Class Group', 'Student Name', 'Student Code', 'Status', 'Reason', 'Arrived At', 'Sheet Status'];
    }

    public function collection(): Collection
    {
        $query = DB::table('student_attendance_records as sar')
            ->join('student_attendance_sheets as sas', 'sas.id', '=', 'sar.sheet_id')
            ->join('class_groups as cg', 'cg.id', '=', 'sas.class_group_id')
            ->join('student_profiles as sp', 'sp.id', '=', 'sar.student_profile_id')
            ->where('sas.institution_semester_id', $this->institutionSemesterId)
            ->orderBy('sas.attendance_date')
            ->orderBy('sp.id');

        // Period-grant restriction: enforced server-side so a forged classGroupId
        // cannot leak data from periods the staff member is not assigned to.
        if ($this->allowedPeriodIds !== null) {
            if (count($this->allowedPeriodIds) === 0) {
                return collect();
            }
            $query->whereIn('sas.operational_period_id', $this->allowedPeriodIds);
        }

        if ($this->classGroupId) {
            $query->where('sas.class_group_id', $this->classGroupId);
        }

        if ($this->dateFrom) {
            $query->where('sas.attendance_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->where('sas.attendance_date', '<=', $this->dateTo);
        }

        $nameField   = $this->locale === 'ar' ? 'sp.full_name_ar' : 'sp.full_name_en';
        $cgNameField = 'cg.name_ar';

        return $query->select(
            'sas.attendance_date',
            DB::raw("$cgNameField as class_group_name"),
            DB::raw("COALESCE($nameField, sp.full_name_ar) as student_name"),
            'sp.student_code',
            'sar.status_code',
            'sar.reason',
            'sar.confirmed_arrived_at',
            'sas.status as sheet_status',
        )->get()->map(fn ($row) => [
            $row->attendance_date,
            $row->class_group_name,
            $row->student_name,
            $row->student_code,
            $this->translateStatus($row->status_code),
            $row->reason ?? '',
            $row->confirmed_arrived_at ?? '',
            $this->translateSheetStatus($row->sheet_status),
        ]);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 11]],
        ];
    }

    private function translateStatus(?string $code): string
    {
        if ($this->locale === 'ar') {
            return match ($code) {
                'present' => 'حاضر',
                'absent'  => 'غائب',
                'late'    => 'متأخر',
                default   => $code ?? '',
            };
        }

        return match ($code) {
            'present' => 'Present',
            'absent'  => 'Absent',
            'late'    => 'Late',
            default   => $code ?? '',
        };
    }

    private function translateSheetStatus(?string $status): string
    {
        if ($this->locale === 'ar') {
            return match ($status) {
                'draft'     => 'مسودة',
                'submitted' => 'مُقدَّم',
                'returned'  => 'مُعاد',
                'verified'  => 'مُتحقَّق',
                default     => $status ?? '',
            };
        }

        return ucfirst($status ?? '');
    }
}

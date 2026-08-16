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
 * Excel export for the staff attendance report.
 *
 * Each row represents one staff_attendance_record for the requested scope.
 */
final class StaffAttendanceReportExport implements FromCollection, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    public function __construct(
        private readonly int $institutionSemesterId,
        private readonly ?int $operationalPeriodId,
        private readonly ?string $dateFrom,
        private readonly ?string $dateTo,
        private readonly string $locale = 'ar',
    ) {}

    public function title(): string
    {
        return $this->locale === 'ar' ? 'تقرير حضور الكادر' : 'Staff Attendance Report';
    }

    public function headings(): array
    {
        if ($this->locale === 'ar') {
            return ['التاريخ', 'اسم الموظف', 'رمز الموظف', 'الحالة', 'السبب', 'وقت الوصول', 'وقت المغادرة', 'موثَّق'];
        }

        return ['Date', 'Staff Name', 'Staff Code', 'Status', 'Reason', 'Arrived At', 'Departed At', 'Verified'];
    }

    public function collection(): Collection
    {
        $nameField = $this->locale === 'ar' ? 'p.full_name_ar' : 'p.full_name_en';

        $query = DB::table('staff_attendance_records as sar')
            ->join('staff_profiles as sp', 'sp.id', '=', 'sar.staff_profile_id')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->where('sar.institution_semester_id', $this->institutionSemesterId)
            ->orderBy('sar.record_date')
            ->orderBy('p.full_name_ar');

        if ($this->operationalPeriodId) {
            $query->where('sar.operational_period_id', $this->operationalPeriodId);
        }

        if ($this->dateFrom) {
            $query->where('sar.record_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->where('sar.record_date', '<=', $this->dateTo);
        }

        return $query->select(
            'sar.record_date',
            DB::raw("COALESCE($nameField, p.full_name_ar) as staff_name"),
            'sp.staff_code',
            'sar.status_code',
            'sar.reason',
            'sar.confirmed_arrived_at',
            'sar.confirmed_departed_at',
            'sar.is_verified',
        )->get()->map(fn ($row) => [
            $row->record_date,
            $row->staff_name,
            $row->staff_code,
            $this->translateStatus($row->status_code),
            $row->reason ?? '',
            $row->confirmed_arrived_at ?? '',
            $row->confirmed_departed_at ?? '',
            $row->is_verified ? ($this->locale === 'ar' ? 'نعم' : 'Yes') : ($this->locale === 'ar' ? 'لا' : 'No'),
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
                'absent' => 'غائب',
                'late' => 'متأخر',
                default => $code ?? '',
            };
        }

        return match ($code) {
            'present' => 'Present',
            'absent' => 'Absent',
            'late' => 'Late',
            default => $code ?? '',
        };
    }
}

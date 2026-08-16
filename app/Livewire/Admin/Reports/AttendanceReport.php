<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Reports;

use App\Exports\AttendanceReportExport;
use App\Exports\StaffAttendanceReportExport;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Admin attendance report page.
 *
 * Provides:
 *  - Student attendance summary: records per class group / date range
 *  - Staff attendance summary: records per operational period / date range
 *  - Excel export for both report types (audited via report_exports table)
 *
 * Access gated by attendance_report.read permission on the admin account's roles.
 */
class AttendanceReport extends Component
{
    public string $reportType = 'student'; // student | staff

    public int $semesterId = 0;

    public int $classGroupId = 0;

    public int $periodId = 0;

    public string $dateFrom = '';

    public string $dateTo = '';

    public bool $canExport = false;

    public function mount(): void
    {
        // Gate: any admin without attendance_report.read sees a 403.
        abort_if(! $this->adminCan('attendance_report.read'), 403);

        $this->canExport = $this->adminCan('attendance_report.export');

        // Default to first open semester
        $sem = DB::table('institution_semesters')
            ->where('status', 'open')
            ->orderByDesc('id')
            ->first(['id']);

        if ($sem) {
            $this->semesterId = (int) $sem->id;
        }

        $this->dateFrom = now()->subDays(14)->toDateString();
        $this->dateTo = now()->toDateString();
    }

    #[Computed]
    public function semesters(): Collection
    {
        return DB::table('institution_semesters as is_')
            ->join('institutions as i', 'i.id', '=', 'is_.institution_id')
            ->join('semesters as s', 's.id', '=', 'is_.semester_id')
            ->whereIn('is_.status', ['open', 'archived'])
            ->orderByDesc('is_.id')
            ->get(['is_.id', 'i.name_ar as institution_name', 's.name_ar as semester_name', 'is_.status']);
    }

    #[Computed]
    public function classGroups(): Collection
    {
        if ($this->semesterId === 0) {
            return collect();
        }

        return DB::table('class_groups')
            ->where('institution_semester_id', $this->semesterId)
            ->orderBy('name_ar')
            ->get(['id', 'name_ar', 'code']);
    }

    #[Computed]
    public function operationalPeriods(): Collection
    {
        if ($this->semesterId === 0) {
            return collect();
        }

        return DB::table('operational_periods')
            ->where('institution_semester_id', $this->semesterId)
            ->orderBy('sequence')
            ->get(['id', 'name_ar', 'code']);
    }

    #[Computed]
    public function studentRows(): Collection
    {
        if ($this->semesterId === 0 || $this->reportType !== 'student') {
            return collect();
        }

        $query = DB::table('student_attendance_records as sar')
            ->join('student_attendance_sheets as sas', 'sas.id', '=', 'sar.sheet_id')
            ->join('class_groups as cg', 'cg.id', '=', 'sas.class_group_id')
            ->join('student_profiles as sp', 'sp.id', '=', 'sar.student_profile_id')
            ->where('sas.institution_semester_id', $this->semesterId);

        if ($this->classGroupId > 0) {
            $query->where('sas.class_group_id', $this->classGroupId);
        }

        if ($this->dateFrom !== '') {
            $query->where('sas.attendance_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo !== '') {
            $query->where('sas.attendance_date', '<=', $this->dateTo);
        }

        return $query
            ->orderBy('sas.attendance_date')
            ->orderBy('cg.name_ar')
            ->select(
                'sas.attendance_date',
                'cg.name_ar as class_group_name',
                'sp.full_name_ar as student_name',
                'sp.student_code',
                'sar.status_code',
                'sar.reason',
                'sas.status as sheet_status',
            )
            ->limit(500)
            ->get();
    }

    #[Computed]
    public function staffRows(): Collection
    {
        if ($this->semesterId === 0 || $this->reportType !== 'staff') {
            return collect();
        }

        $query = DB::table('staff_attendance_records as sar')
            ->join('staff_profiles as sp', 'sp.id', '=', 'sar.staff_profile_id')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->where('sar.institution_semester_id', $this->semesterId);

        if ($this->periodId > 0) {
            $query->where('sar.operational_period_id', $this->periodId);
        }

        if ($this->dateFrom !== '') {
            $query->where('sar.record_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo !== '') {
            $query->where('sar.record_date', '<=', $this->dateTo);
        }

        return $query
            ->orderBy('sar.record_date')
            ->orderBy('p.full_name_ar')
            ->select(
                'sar.record_date',
                'p.full_name_ar as staff_name',
                'sp.staff_code',
                'sar.status_code',
                'sar.confirmed_arrived_at',
                'sar.is_verified',
            )
            ->limit(500)
            ->get();
    }

    #[Computed]
    public function summaryStats(): object
    {
        if ($this->semesterId === 0 || $this->reportType !== 'student') {
            return (object) ['total' => 0, 'present' => 0, 'absent' => 0, 'late' => 0];
        }

        $query = DB::table('student_attendance_records as sar')
            ->join('student_attendance_sheets as sas', 'sas.id', '=', 'sar.sheet_id')
            ->where('sas.institution_semester_id', $this->semesterId);

        if ($this->classGroupId > 0) {
            $query->where('sas.class_group_id', $this->classGroupId);
        }

        if ($this->dateFrom !== '') {
            $query->where('sas.attendance_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo !== '') {
            $query->where('sas.attendance_date', '<=', $this->dateTo);
        }

        $map = $query->selectRaw('sar.status_code, count(*) as cnt')
            ->groupBy('sar.status_code')
            ->pluck('cnt', 'status_code')
            ->toArray();

        $total = array_sum($map);

        return (object) [
            'total' => $total,
            'present' => (int) ($map['present'] ?? 0),
            'absent' => (int) ($map['absent'] ?? 0),
            'late' => (int) ($map['late'] ?? 0),
        ];
    }

    public function exportStudentAttendance(): void
    {
        if (! $this->canExport || $this->semesterId === 0) {
            return;
        }

        $export = new AttendanceReportExport(
            institutionSemesterId: $this->semesterId,
            classGroupId: $this->classGroupId > 0 ? $this->classGroupId : null,
            dateFrom: $this->dateFrom ?: null,
            dateTo: $this->dateTo ?: null,
        );

        $rows = $export->collection();
        $filename = 'student-attendance-'.now()->format('Y-m-d').'.xlsx';
        $path = 'reports/'.Str::uuid().'/'.$filename;

        Storage::disk('local')->makeDirectory(dirname($path));
        Excel::store($export, $path, 'local');

        $exportId = $this->auditExport('attendance_report', [
            'institution_semester_id' => $this->semesterId,
            'class_group_id' => $this->classGroupId,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ], $rows->count(), $path);

        $this->dispatch('start-download', url: route('admin.reports.download', [
            'export' => encrypt($exportId),
            'name' => $filename,
        ]));
    }

    public function exportStaffAttendance(): void
    {
        if (! $this->canExport || $this->semesterId === 0) {
            return;
        }

        $export = new StaffAttendanceReportExport(
            institutionSemesterId: $this->semesterId,
            operationalPeriodId: $this->periodId > 0 ? $this->periodId : null,
            dateFrom: $this->dateFrom ?: null,
            dateTo: $this->dateTo ?: null,
        );

        $rows = $export->collection();
        $filename = 'staff-attendance-'.now()->format('Y-m-d').'.xlsx';
        $path = 'reports/'.Str::uuid().'/'.$filename;

        Storage::disk('local')->makeDirectory(dirname($path));
        Excel::store($export, $path, 'local');

        $exportId = $this->auditExport('staff_attendance_report', [
            'institution_semester_id' => $this->semesterId,
            'operational_period_id' => $this->periodId,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ], $rows->count(), $path);

        $this->dispatch('start-download', url: route('admin.reports.download', [
            'export' => encrypt($exportId),
            'name' => $filename,
        ]));
    }

    /**
     * Write the export audit row and return its ID so the download controller
     * can verify actor ownership before serving the file.
     */
    private function auditExport(string $type, array $scope, int $rowCount, string $filePath): int
    {
        return (int) DB::table('report_exports')->insertGetId([
            'export_type' => $type,
            'actor_type' => 'admin',
            'actor_account_id' => (int) auth('admin')->id(),
            'scope' => json_encode($scope),
            'locale' => 'ar',
            'row_count' => $rowCount,
            'file_path' => $filePath,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function adminCan(string $permission): bool
    {
        $accountId = auth('admin')->id();

        if (! $accountId) {
            return false;
        }

        return DB::table('administrative_account_roles as aar')
            ->join('role_permissions as rp', 'rp.role_id', '=', 'aar.role_id')
            ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
            ->where('aar.administrative_account_id', $accountId)
            ->whereNull('aar.revoked_at')
            ->where('p.key', $permission)
            ->exists();
    }

    public function render(): View
    {
        return view('livewire.admin.reports.attendance-report')
            ->layout('layouts.admin');
    }
}

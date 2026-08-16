<?php

declare(strict_types=1);

namespace App\Livewire\Staff\Reports;

use App\Exports\AttendanceReportExport;
use App\Livewire\Staff\Concerns\HasStaffAuth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Authorization\App\Data\PermissionKey;

/**
 * Staff attendance report — scoped to the authenticated staff member's
 * institution/semester/operational-period.
 *
 * Principal sees the full semester. Secretary and teacher see only their
 * granted operational periods.
 *
 * Gated by attendance_report.read.
 *
 * SECURITY: all export methods enforce period grants server-side via
 * allowedPeriodIds() regardless of which classGroupId the client submits,
 * preventing Livewire message forgery from leaking cross-period data.
 */
class AttendanceReport extends Component
{
    use HasStaffAuth;

    public string $reportType = 'student';

    public int $classGroupId = 0;

    public string $dateFrom = '';

    public string $dateTo = '';

    public bool $canExport = false;

    public function mount(): void
    {
        $this->requirePermission(PermissionKey::ATTENDANCE_REPORT_READ);
        $this->canExport = $this->staffCan(PermissionKey::ATTENDANCE_REPORT_EXPORT);

        $this->dateFrom = now()->subDays(14)->toDateString();
        $this->dateTo = now()->toDateString();
    }

    #[Computed]
    public function classGroups(): Collection
    {
        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            return collect();
        }

        $query = DB::table('class_groups as cg')
            ->where('cg.institution_semester_id', $scope['institution_semester_id'])
            ->orderBy('cg.name_ar');

        if (! $this->isFullScopePosition()) {
            $allowed = $this->allowedPeriodIds();

            if (empty($allowed)) {
                return collect();
            }

            $query->whereIn('cg.operational_period_id', $allowed);
        }

        return $query->get(['cg.id', 'cg.name_ar', 'cg.code']);
    }

    #[Computed]
    public function studentRows(): Collection
    {
        if ($this->reportType !== 'student') {
            return collect();
        }

        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            return collect();
        }

        $query = DB::table('student_attendance_records as sar')
            ->join('student_attendance_sheets as sas', 'sas.id', '=', 'sar.sheet_id')
            ->join('class_groups as cg', 'cg.id', '=', 'sas.class_group_id')
            ->join('student_profiles as sp', 'sp.id', '=', 'sar.student_profile_id')
            ->where('sas.institution_semester_id', $scope['institution_semester_id']);

        // Period restriction — always enforced server-side regardless of classGroupId filter
        if (! $this->isFullScopePosition()) {
            $allowed = $this->allowedPeriodIds();

            if (empty($allowed)) {
                return collect();
            }

            $query->whereIn('sas.operational_period_id', $allowed);
        }

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
        if ($this->reportType !== 'staff' || ! $this->isFullScopePosition()) {
            return collect();
        }

        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            return collect();
        }

        return DB::table('staff_attendance_records as sar')
            ->join('staff_profiles as sp', 'sp.id', '=', 'sar.staff_profile_id')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->where('sar.institution_semester_id', $scope['institution_semester_id'])
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
        if ($this->reportType !== 'student') {
            return (object) ['total' => 0, 'present' => 0, 'absent' => 0, 'late' => 0];
        }

        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            return (object) ['total' => 0, 'present' => 0, 'absent' => 0, 'late' => 0];
        }

        $query = DB::table('student_attendance_records as sar')
            ->join('student_attendance_sheets as sas', 'sas.id', '=', 'sar.sheet_id')
            ->where('sas.institution_semester_id', $scope['institution_semester_id']);

        if (! $this->isFullScopePosition()) {
            $allowed = $this->allowedPeriodIds();

            if (empty($allowed)) {
                return (object) ['total' => 0, 'present' => 0, 'absent' => 0, 'late' => 0];
            }

            $query->whereIn('sas.operational_period_id', $allowed);
        }

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
        if (! $this->canExport) {
            return;
        }

        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            return;
        }

        // Compute period restriction server-side — never trust the client's classGroupId alone
        $allowedPeriodIds = $this->isFullScopePosition() ? null : $this->allowedPeriodIds();

        if ($allowedPeriodIds !== null && count($allowedPeriodIds) === 0) {
            return; // no scope, nothing to export
        }

        $export = new AttendanceReportExport(
            institutionSemesterId: $scope['institution_semester_id'],
            classGroupId: $this->classGroupId > 0 ? $this->classGroupId : null,
            dateFrom: $this->dateFrom ?: null,
            dateTo: $this->dateTo ?: null,
            allowedPeriodIds: $allowedPeriodIds,
        );

        $rows = $export->collection();
        $filename = 'student-attendance-'.now()->format('Y-m-d').'.xlsx';
        $path = 'reports/'.Str::uuid().'/'.$filename;

        Storage::disk('local')->makeDirectory(dirname($path));
        Excel::store($export, $path, 'local');

        $exportId = $this->auditExport('attendance_report', [
            'institution_semester_id' => $scope['institution_semester_id'],
            'class_group_id' => $this->classGroupId,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ], $rows->count(), $path);

        $this->dispatch('start-download', url: route('staff.reports.download', [
            'export' => encrypt($exportId),
            'name' => $filename,
        ]));
    }

    private function auditExport(string $type, array $scope, int $rowCount, string $filePath): int
    {
        return (int) DB::table('report_exports')->insertGetId([
            'export_type' => $type,
            'actor_type' => 'staff',
            'actor_account_id' => (int) auth('staff')->id(),
            'scope' => json_encode($scope),
            'locale' => 'ar',
            'row_count' => $rowCount,
            'file_path' => $filePath,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function render(): View
    {
        return view('livewire.staff.reports.attendance-report')
            ->layout('layouts.staff');
    }
}

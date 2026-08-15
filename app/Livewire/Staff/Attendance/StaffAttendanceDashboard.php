<?php

declare(strict_types=1);

namespace App\Livewire\Staff\Attendance;

use App\Livewire\Staff\Concerns\HasStaffAuth;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Principal / deputy principal staff attendance dashboard.
 *
 * Shows an overview of staff attendance for the current semester.
 *
 * SECURITY — Institution and period scope:
 *   • periodSummaries() only includes periods that are in the actor's
 *     institution_semester. For non-full-scope positions (secretaries who
 *     may hold staff_attendance.read), only their explicitly granted periods
 *     are included in the summary cards.
 *   • attendanceRows() validates selectedPeriodId against the institution
 *     semester (layer 1) AND period grants (layer 2 for restricted roles)
 *     before executing the query. Fails with 403 on tampering.
 *   • Staff totals in the summary are scoped to institution_id to prevent
 *     cross-institution count disclosure.
 *
 * Read-only — principals do not enter or verify records here.
 */
final class StaffAttendanceDashboard extends Component
{
    use HasStaffAuth;

    public string $selectedDate = '';
    public int $selectedPeriodId = 0;

    public function mount(): void
    {
        $this->requirePermission('staff_attendance.read');
        $this->selectedDate     = now()->toDateString();
        $this->selectedPeriodId = $this->defaultPeriodId();
    }

    /** @return list<object> */
    public function periodSummaries(): array
    {
        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null || $scope['institution_id'] === null) {
            return [];
        }

        $institutionId = $scope['institution_id'];

        $periodsQuery = DB::table('operational_periods')
            ->where('institution_semester_id', $scope['institution_semester_id'])
            ->select('id', 'name_en as name')
            ->orderBy('name_en');

        // For restricted roles (e.g. secretaries who hold staff_attendance.read),
        // only show summary cards for their explicitly granted periods.
        if (! $this->isFullScopePosition()) {
            $allowedPeriodIds = $this->allowedPeriodIds();

            if (empty($allowedPeriodIds)) {
                return [];
            }

            $periodsQuery->whereIn('id', $allowedPeriodIds);
        }

        $periods = $periodsQuery->get();

        return $periods->map(function ($period) use ($institutionId) {
            // Staff total scoped to this institution only
            $total = DB::table('staff_profiles as sp')
                ->join('staff_institution_assignments as sia', function ($j): void {
                    $j->on('sia.staff_profile_id', '=', 'sp.id')
                      ->whereNull('sia.ended_on');
                })
                ->where('sia.institution_id', $institutionId)
                ->count(DB::raw('DISTINCT sp.id'));

            $filled = DB::table('staff_attendance_records')
                ->where('operational_period_id', $period->id)
                ->whereDate('record_date', $this->selectedDate)
                ->whereNotNull('status_code')
                ->count();

            $present = DB::table('staff_attendance_records as sar')
                ->where('sar.operational_period_id', $period->id)
                ->whereDate('sar.record_date', $this->selectedDate)
                ->whereIn('sar.status_code', ['present', 'late', 'official_duty'])
                ->count();

            return (object) [
                'period_id'   => $period->id,
                'period_name' => $period->name,
                'total'       => $total,
                'filled'      => $filled,
                'present'     => $present,
                'absent'      => $filled - $present,
                'unrecorded'  => max(0, $total - $filled),
            ];
        })->all();
    }

    /** @return \Illuminate\Support\Collection */
    public function attendanceRows(): \Illuminate\Support\Collection
    {
        if ($this->selectedPeriodId === 0) {
            return collect();
        }

        $scope = $this->staffScope();

        // Layer 1: validate the client-supplied period belongs to the actor's
        // institution semester — prevents cross-institution data disclosure.
        // Returns empty (not abort) because this method is called from render();
        // aborting inside render() breaks the Livewire component lifecycle.
        if ($scope['institution_semester_id'] !== null) {
            $belongsToSemester = DB::table('operational_periods')
                ->where('id', $this->selectedPeriodId)
                ->where('institution_semester_id', $scope['institution_semester_id'])
                ->exists();

            if (! $belongsToSemester) {
                return collect(); // No disclosure
            }
        }

        // Layer 2: for non-full-scope positions, validate the period is in their
        // explicit grant list — prevents disclosure of sibling periods.
        if (! $this->isFullScopePosition()) {
            $allowed = $this->allowedPeriodIds();

            if (! in_array($this->selectedPeriodId, $allowed, true)) {
                return collect(); // No disclosure
            }
        }

        return DB::table('staff_attendance_records as sar')
            ->join('people as p', function ($j): void {
                $j->on('p.id', '=', DB::raw(
                    '(SELECT sp2.person_id FROM staff_profiles sp2 WHERE sp2.id = sar.staff_profile_id LIMIT 1)'
                ));
            })
            ->where('sar.operational_period_id', $this->selectedPeriodId)
            ->whereDate('sar.record_date', $this->selectedDate)
            ->select(
                'p.full_name_ar as name',
                'sar.status_code',
                'sar.reason',
                'sar.confirmed_arrived_at',
                'sar.confirmed_departed_at',
                'sar.scanned_arrived_at',
                'sar.scanned_departed_at',
                'sar.is_verified',
                'sar.source',
            )
            ->orderBy('p.full_name_ar')
            ->get();
    }

    public function render(): View
    {
        return view('livewire.staff.attendance.staff-dashboard', [
            'summaries'       => $this->periodSummaries(),
            'attendanceRows'  => $this->attendanceRows(),
        ]);
    }

    private function defaultPeriodId(): int
    {
        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            return 0;
        }

        $query = DB::table('operational_periods')
            ->where('institution_semester_id', $scope['institution_semester_id']);

        if (! $this->isFullScopePosition()) {
            $allowed = $this->allowedPeriodIds();

            if (empty($allowed)) {
                return 0;
            }

            $query->whereIn('id', $allowed);
        }

        $id = $query->value('id');

        return $id ? (int) $id : 0;
    }
}

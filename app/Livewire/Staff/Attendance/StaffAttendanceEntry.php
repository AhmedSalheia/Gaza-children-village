<?php

declare(strict_types=1);

namespace App\Livewire\Staff\Attendance;

use App\Livewire\Staff\Concerns\HasStaffAuth;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Modules\Attendance\Actions\CorrectVerifiedStaffRecord;
use Modules\Attendance\Actions\CreateDailyStaffRecord;
use Modules\Attendance\Actions\VerifyStaffRecord;
use Modules\Attendance\Data\StaffAttendanceStatus;
use Modules\Attendance\Exceptions\StaffAttendanceException;
use Modules\Attendance\Models\StaffAttendanceRecord;

/**
 * Secretary daily staff attendance entry grid.
 *
 * SECURITY — Two-layer period guard on every client-supplied period ID:
 *   1. The period must belong to the actor's server-side institution_semester_id.
 *      This prevents full-scope principals from reaching another institution.
 *   2. For non-full-scope positions (secretary/teacher) the period must also
 *      be in their explicit grant list.
 *
 * For mutations (saveRow, verifyRecord, startCorrection, submitCorrection):
 *   → abort(403) if the period fails either check.
 *
 * For reads (staffRows):
 *   → return empty collection if the client-supplied selectedPeriodId fails
 *     either check. This avoids crashing the render while preventing disclosure.
 */
final class StaffAttendanceEntry extends Component
{
    use HasStaffAuth;

    public string $selectedDate = '';

    public int $selectedPeriodId = 0;

    /** @var array<int, string> Editable row state: staffProfileId → statusCode */
    public array $rowStatus = [];

    /** @var array<int, string> */
    public array $rowReason = [];

    /** @var array<int, string> */
    public array $rowArrivedAt = [];

    /** @var array<int, string> */
    public array $rowDepartedAt = [];

    // ── Correction form state ─────────────────────────────────────────────────

    public ?int $correctingRecordId = null;

    public string $correctStatus = '';

    public string $correctReason = '';

    public string $correctArrivedAt = '';

    public string $correctDepartedAt = '';

    public string $flashMessage = '';

    public string $flashType = '';

    public function mount(): void
    {
        $this->requirePermission('staff_attendance.enter');
        $this->selectedDate = now()->toDateString();
        $this->selectedPeriodId = $this->defaultPeriodId();
    }

    public function updatedSelectedDate(): void
    {
        $this->clearRowState();
        $this->cancelCorrection();
    }

    public function updatedSelectedPeriodId(): void
    {
        $this->clearRowState();
        $this->cancelCorrection();
    }

    // ── Row save / verify ─────────────────────────────────────────────────────

    public function saveRow(int $staffProfileId): void
    {
        $this->requirePermission('staff_attendance.enter');
        $this->assertPeriodAllowed($this->selectedPeriodId); // aborts on tampering

        $statusCode = $this->rowStatus[$staffProfileId] ?? '';

        if ($statusCode === '') {
            return;
        }

        try {
            app(CreateDailyStaffRecord::class)(
                staffProfileId: $staffProfileId,
                operationalPeriodId: $this->selectedPeriodId,
                date: $this->selectedDate,
                statusCode: $statusCode,
                reason: $this->rowReason[$staffProfileId] ?? null,
                creatorStaffProfileId: $this->resolveStaffProfileId(),
                confirmedArrivedAt: $this->rowArrivedAt[$staffProfileId] ?? null,
                confirmedDepartedAt: $this->rowDepartedAt[$staffProfileId] ?? null,
            );

            $this->flashMessage = 'Saved.';
            $this->flashType = 'success';
        } catch (StaffAttendanceException $e) {
            $this->flashMessage = $e->getMessage();
            $this->flashType = 'error';
        }
    }

    public function verifyRecord(int $recordId): void
    {
        $this->requirePermission('staff_attendance.verify');

        $record = StaffAttendanceRecord::find($recordId);

        if (! $record) {
            $this->flashMessage = 'Record not found.';
            $this->flashType = 'error';

            return;
        }

        $this->assertPeriodAllowed((int) $record->operational_period_id);

        try {
            app(VerifyStaffRecord::class)($record, $this->resolveStaffProfileId());
            $this->flashMessage = 'Record verified.';
            $this->flashType = 'success';
        } catch (StaffAttendanceException $e) {
            $this->flashMessage = $e->getMessage();
            $this->flashType = 'error';
        }
    }

    // ── Correction form ───────────────────────────────────────────────────────

    public function startCorrection(int $recordId): void
    {
        $this->requirePermission('staff_attendance.correct');

        $record = StaffAttendanceRecord::find($recordId);

        if (! $record || ! $record->is_verified) {
            $this->flashMessage = 'Only verified records can be corrected.';
            $this->flashType = 'error';

            return;
        }

        $this->assertPeriodAllowed((int) $record->operational_period_id);

        $this->correctingRecordId = $recordId;
        $this->correctStatus = (string) $record->status_code;
        $this->correctReason = (string) ($record->reason ?? '');
        $this->correctArrivedAt = (string) ($record->confirmed_arrived_at ?? '');
        $this->correctDepartedAt = (string) ($record->confirmed_departed_at ?? '');
    }

    public function submitCorrection(): void
    {
        $this->requirePermission('staff_attendance.correct');

        if ($this->correctingRecordId === null) {
            return;
        }

        $record = StaffAttendanceRecord::find($this->correctingRecordId);

        if (! $record) {
            $this->cancelCorrection();

            return;
        }

        $this->assertPeriodAllowed((int) $record->operational_period_id);

        try {
            app(CorrectVerifiedStaffRecord::class)(
                record: $record,
                newStatusCode: $this->correctStatus,
                reason: $this->correctReason ?: null,
                actorStaffProfileId: $this->resolveStaffProfileId(),
                confirmedArrivedAt: $this->correctArrivedAt ?: null,
                confirmedDepartedAt: $this->correctDepartedAt ?: null,
            );

            $this->flashMessage = 'Correction saved.';
            $this->flashType = 'success';
            $this->cancelCorrection();
        } catch (StaffAttendanceException $e) {
            $this->flashMessage = $e->getMessage();
            $this->flashType = 'error';
        }
    }

    public function cancelCorrection(): void
    {
        $this->correctingRecordId = null;
        $this->correctStatus = '';
        $this->correctReason = '';
        $this->correctArrivedAt = '';
        $this->correctDepartedAt = '';
    }

    // ── Data queries ──────────────────────────────────────────────────────────

    public function staffRows(): Collection
    {
        if ($this->selectedPeriodId === 0 || $this->selectedDate === '') {
            return collect();
        }

        // READ guard: return empty if the client-supplied period is out of scope.
        // Do NOT abort — that would break the component render.
        if (! $this->periodAllowedInScope($this->selectedPeriodId)) {
            return collect();
        }

        $scope = $this->staffScope();

        // institution_id is guaranteed non-null if periodAllowedInScope passed
        /** @var int $institutionId */
        $institutionId = $scope['institution_id'];

        // Load existing attendance records for this period + date
        $records = DB::table('staff_attendance_records')
            ->where('operational_period_id', $this->selectedPeriodId)
            ->whereDate('record_date', $this->selectedDate)
            ->get()
            ->keyBy('staff_profile_id');

        // Staff with active assignments at this institution only
        $staff = DB::table('staff_profiles as sp')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->join('staff_institution_assignments as sia', function ($j): void {
                $j->on('sia.staff_profile_id', '=', 'sp.id')
                    ->whereNull('sia.ended_on');
            })
            ->where('sia.institution_id', $institutionId)
            ->select('sp.id as staff_profile_id', 'p.full_name_ar as name')
            ->orderBy('p.full_name_ar')
            ->distinct()
            ->get();

        return $staff->map(function ($member) use ($records) {
            $record = $records->get($member->staff_profile_id);

            return (object) [
                'staff_profile_id' => $member->staff_profile_id,
                'name' => $member->name,
                'record_id' => $record?->id,
                'status_code' => $record?->status_code,
                'reason' => $record?->reason,
                'confirmed_arrived' => $record?->confirmed_arrived_at,
                'confirmed_departed' => $record?->confirmed_departed_at,
                'scanned_arrived' => $record?->scanned_arrived_at,
                'scanned_departed' => $record?->scanned_departed_at,
                'is_verified' => (bool) ($record?->is_verified ?? false),
            ];
        });
    }

    /** @return list<object> */
    public function availablePeriods(): array
    {
        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            return [];
        }

        $query = DB::table('operational_periods')
            ->where('institution_semester_id', $scope['institution_semester_id'])
            ->select('id', 'name_en as name');

        if (! $this->isFullScopePosition()) {
            $allowed = $this->allowedPeriodIds();

            if (empty($allowed)) {
                return [];
            }

            $query->whereIn('id', $allowed);
        }

        return $query->orderBy('name')->get()->all();
    }

    public function render(): View
    {
        return view('livewire.staff.attendance.staff-entry', [
            'staffRows' => $this->staffRows(),
            'periods' => $this->availablePeriods(),
            'statuses' => StaffAttendanceStatus::catalogue(),
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function clearRowState(): void
    {
        $this->rowStatus = [];
        $this->rowReason = [];
        $this->rowArrivedAt = [];
        $this->rowDepartedAt = [];
    }

    private function defaultPeriodId(): int
    {
        $periods = $this->availablePeriods();

        return $periods ? (int) $periods[0]->id : 0;
    }

    private function resolveStaffProfileId(): int
    {
        $profileId = $this->staffProfileId();

        if ($profileId === null) {
            abort(403, 'No staff profile linked to this account.');
        }

        return $profileId;
    }

    /**
     * Returns true iff the period passes both the institution-semester check and
     * (for restricted positions) the period-grant check.
     *
     * Used by READS (return empty) and also by assertPeriodAllowed (abort 403).
     */
    private function periodAllowedInScope(int $periodId): bool
    {
        if ($periodId === 0) {
            return false;
        }

        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null || $scope['institution_id'] === null) {
            return false;
        }

        // Layer 1: period must belong to this actor's institution semester
        $belongsToSemester = DB::table('operational_periods')
            ->where('id', $periodId)
            ->where('institution_semester_id', $scope['institution_semester_id'])
            ->exists();

        if (! $belongsToSemester) {
            return false;
        }

        // Layer 2: for restricted roles, the period must be in their grant list
        if ($this->isFullScopePosition()) {
            return true;
        }

        return in_array($periodId, $this->allowedPeriodIds(), true);
    }

    /**
     * Abort 403 if the given period is outside the actor's scope.
     *
     * Two-layer check:
     *   1. Period must belong to the actor's trusted institution_semester_id.
     *   2. For non-full-scope (secretary) positions, also in their grant list.
     *
     * "Full scope" means all periods in the actor's own semester — not globally.
     */
    private function assertPeriodAllowed(int $periodId): void
    {
        if (! $this->periodAllowedInScope($periodId)) {
            abort(403, 'Period does not belong to your authorised scope.');
        }
    }
}

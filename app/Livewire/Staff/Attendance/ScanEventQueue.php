<?php

declare(strict_types=1);

namespace App\Livewire\Staff\Attendance;

use App\Livewire\Staff\Concerns\HasStaffAuth;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Modules\Attendance\Actions\ReviewScanEvent;
use Modules\Attendance\Exceptions\StaffAttendanceException;
use Modules\Attendance\Models\AttendanceScanEvent;

/**
 * Secretary QR scan event review queue.
 *
 * Lists pending scan events in the secretary's institution semester scope.
 * Secretary accepts (with direction confirmation) or rejects each event.
 *
 * SECURITY — THREE GUARDS apply before any read or mutation involving an event ID
 * received from the client:
 *   1. Event's institution_semester_id must match the actor's trusted semester.
 *   2. For non-full-scope positions the event's operational_period_id must be
 *      in the actor's explicit period grant list.
 *   3. Both are checked in startReview() (before storing the ID), render()
 *      (before exposing the event in the modal), and submitReview() (before
 *      any DB mutation).
 *
 * confirmedDirection is validated to one of [arrival, departure, unknown]
 * before use, and the direction update is wrapped in the same DB transaction
 * as the ReviewScanEvent call.
 */
final class ScanEventQueue extends Component
{
    use HasStaffAuth;

    public string $filterDate = '';
    public string $filterStatus = 'pending';

    public ?int $reviewingEventId = null;
    public string $reviewOutcome = '';
    public string $rejectionReason = '';
    public string $confirmedDirection = '';

    public string $flashMessage = '';
    public string $flashType = '';

    private const VALID_DIRECTIONS = ['arrival', 'departure', 'unknown'];

    public function mount(): void
    {
        $this->requirePermission('attendance_scan.review');
        $this->filterDate = now()->toDateString();
    }

    public function startReview(int $eventId): void
    {
        $this->requirePermission('attendance_scan.review');

        $event = AttendanceScanEvent::find($eventId);

        if (! $event) {
            return;
        }

        // Validate scope BEFORE storing the ID — prevents metadata disclosure
        // even when the event is only shown in a modal (not mutated yet).
        $this->assertEventBelongsToScope($event);

        $this->reviewingEventId    = $eventId;
        $this->reviewOutcome       = '';
        $this->rejectionReason     = '';
        $this->confirmedDirection  = '';
    }

    public function submitReview(): void
    {
        $this->requirePermission('attendance_scan.review');

        if ($this->reviewingEventId === null) {
            return;
        }

        $event = AttendanceScanEvent::find($this->reviewingEventId);

        if (! $event) {
            $this->flashMessage     = 'Scan event not found.';
            $this->flashType        = 'error';
            $this->reviewingEventId = null;

            return;
        }

        // Re-validate scope on every mutation (client may have tampered the ID)
        $this->assertEventBelongsToScope($event);

        if ($this->reviewOutcome === 'rejected' && empty(trim($this->rejectionReason))) {
            $this->flashMessage = 'Please provide a rejection reason.';
            $this->flashType    = 'error';

            return;
        }

        // Server-side validation of the direction override
        if ($this->confirmedDirection !== '' && ! in_array($this->confirmedDirection, self::VALID_DIRECTIONS, true)) {
            $this->flashMessage = 'Invalid direction value.';
            $this->flashType    = 'error';

            return;
        }

        try {
            $actorId = $this->resolveStaffProfileId();

            // Wrap direction update + review in a single atomic transaction
            DB::transaction(function () use ($event, $actorId): void {
                if ($this->reviewOutcome === 'accepted' && $this->confirmedDirection !== '') {
                    DB::table('attendance_scan_events')
                        ->where('id', $event->id)
                        ->update(['direction' => $this->confirmedDirection, 'updated_at' => now()]);

                    $event->refresh();
                }

                app(ReviewScanEvent::class)(
                    event:                  $event,
                    outcome:                $this->reviewOutcome,
                    reviewerStaffProfileId: $actorId,
                    rejectionReason:        $this->rejectionReason ?: null,
                );
            });

            $this->flashMessage = $this->reviewOutcome === 'accepted'
                ? 'Scan event accepted. Scanned time applied to attendance record.'
                : 'Scan event rejected.';
            $this->flashType    = 'success';
        } catch (StaffAttendanceException $e) {
            $this->flashMessage = $e->getMessage();
            $this->flashType    = 'error';
        } finally {
            $this->reviewingEventId = null;
        }
    }

    public function cancelReview(): void
    {
        $this->reviewingEventId = null;
    }

    /** @return \Illuminate\Support\Collection */
    public function events(): \Illuminate\Support\Collection
    {
        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            return collect();
        }

        $query = DB::table('attendance_scan_events as ase')
            ->join('people as p', function ($j): void {
                $j->on('p.id', '=', DB::raw(
                    '(SELECT sp2.person_id FROM staff_profiles sp2 WHERE sp2.id = ase.staff_profile_id LIMIT 1)'
                ));
            })
            // Scoped to the trusted institution_semester_id from the server-side position
            ->where('ase.institution_semester_id', $scope['institution_semester_id'])
            ->select(
                'ase.id',
                'ase.staff_profile_id',
                'p.full_name_ar as staff_name',
                'ase.operational_period_id',
                'ase.scanned_at',
                'ase.scan_date',
                'ase.direction',
                'ase.processing_status',
                'ase.rejection_reason',
                'ase.device_fingerprint',
            )
            ->orderByDesc('ase.scanned_at');

        if ($this->filterDate !== '') {
            $query->where('ase.scan_date', $this->filterDate);
        }

        if ($this->filterStatus !== '') {
            $query->where('ase.processing_status', $this->filterStatus);
        }

        if (! $this->isFullScopePosition()) {
            $allowed = $this->allowedPeriodIds();

            if (empty($allowed)) {
                return collect();
            }

            $query->whereIn('ase.operational_period_id', $allowed);
        }

        return $query->limit(100)->get();
    }

    public function render(): View
    {
        return view('livewire.staff.attendance.scan-queue', [
            'events'         => $this->events(),
            // Load the reviewing event only if it still passes the scope check.
            // This prevents metadata disclosure if reviewingEventId was tampered
            // between the startReview call and the subsequent render cycle.
            'reviewingEvent' => $this->reviewingEventId !== null
                ? $this->scopedReviewingEvent()
                : null,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function resolveStaffProfileId(): int
    {
        $profileId = $this->staffProfileId();

        if ($profileId === null) {
            abort(403, 'No staff profile linked to this account.');
        }

        return $profileId;
    }

    /**
     * Load the reviewing event only if it belongs to the actor's scope.
     * Returns null (hides the modal) rather than aborting — render must not crash.
     */
    private function scopedReviewingEvent(): ?AttendanceScanEvent
    {
        $event = AttendanceScanEvent::find($this->reviewingEventId);

        if (! $event) {
            $this->reviewingEventId = null;

            return null;
        }

        $scope = $this->staffScope();

        if (
            $scope['institution_semester_id'] !== null
            && (int) $event->institution_semester_id !== $scope['institution_semester_id']
        ) {
            // Tampered ID — clear state silently
            $this->reviewingEventId = null;

            return null;
        }

        if (! $this->isFullScopePosition()) {
            $allowed = $this->allowedPeriodIds();
            if (! in_array((int) $event->operational_period_id, $allowed, true)) {
                $this->reviewingEventId = null;

                return null;
            }
        }

        return $event;
    }

    /**
     * Abort 403 if the event is outside the actor's institution semester or period grant.
     *
     * institution_semester_id on the event is set at scan time (server-side) and
     * is the canonical institution scope anchor — not a client-supplied value.
     */
    private function assertEventBelongsToScope(AttendanceScanEvent $event): void
    {
        $scope = $this->staffScope();

        // Layer 1: event must belong to this actor's institution semester
        if (
            $scope['institution_semester_id'] !== null
            && (int) $event->institution_semester_id !== $scope['institution_semester_id']
        ) {
            abort(403, 'Scan event does not belong to your institution semester.');
        }

        // Layer 2: restricted roles must hold a period grant for the event's period
        if ($this->isFullScopePosition()) {
            return;
        }

        $allowed = $this->allowedPeriodIds();

        if (! in_array((int) $event->operational_period_id, $allowed, true)) {
            abort(403, 'Scan event is not in your assigned period scope.');
        }
    }
}

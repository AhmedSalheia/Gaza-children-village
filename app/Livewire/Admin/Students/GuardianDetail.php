<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Students;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Guardian profile detail view with student relationship summary
 * and pending correction-request review.
 *
 * Permission split:
 *   guardian_relationship.view   — required to mount and see the page.
 *   guardian_relationship.manage — required to approve or reject a request
 *                                  (mutations on the relationship row).
 */
final class GuardianDetail extends Component
{
    use HasAdminAuth;

    public int $guardianId;

    public ?object $guardian = null;

    public ?object $person = null;

    public function mount(int $guardianId): void
    {
        $this->requirePermission('guardian_relationship.view');
        $this->guardianId = $guardianId;

        $this->guardian = DB::table('guardian_profiles')->where('id', $guardianId)->first();

        if ($this->guardian === null) {
            $this->redirectRoute('admin.guardians.index', navigate: true);

            return;
        }

        $this->person = DB::table('people')->where('id', $this->guardian->person_id)->first();
    }

    public function relationships(): Collection
    {
        return DB::table('guardian_student_relationships as gsr')
            ->join('student_profiles as sp', 'sp.id', '=', 'gsr.student_profile_id')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->where('gsr.guardian_profile_id', $this->guardianId)
            ->get([
                'gsr.id',
                'gsr.relationship_type',
                'gsr.verification_status',
                'gsr.portal_eligible',
                'gsr.ends_on',
                'sp.id as student_id',
                'sp.student_code',
                'p.full_name_ar as student_name_ar',
            ]);
    }

    /**
     * All pending correction requests for this guardian's relationships,
     * ordered newest first.
     */
    public function pendingCorrectionRequests(): Collection
    {
        $relationshipIds = DB::table('guardian_student_relationships')
            ->where('guardian_profile_id', $this->guardianId)
            ->pluck('id');

        if ($relationshipIds->isEmpty()) {
            return collect();
        }

        return DB::table('guardian_correction_requests as gcr')
            ->join('guardian_student_relationships as gsr', 'gsr.id', '=', 'gcr.guardian_student_relationship_id')
            ->join('student_profiles as sp', 'sp.id', '=', 'gsr.student_profile_id')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->whereIn('gcr.guardian_student_relationship_id', $relationshipIds)
            ->where('gcr.status', 'pending')
            ->orderByDesc('gcr.created_at')
            ->get([
                'gcr.id',
                'gcr.guardian_student_relationship_id',
                'gcr.requested_contact_priority',
                'gcr.requested_is_emergency_contact',
                'gcr.note',
                'gcr.created_at',
                'gsr.contact_priority as current_contact_priority',
                'gsr.is_emergency_contact as current_is_emergency_contact',
                'sp.id as student_id',
                'sp.student_code',
                'p.full_name_ar as student_name_ar',
            ]);
    }

    /**
     * Approve a pending correction request: apply the proposed values to the
     * relationship row and mark the request approved.
     *
     * Requires guardian_relationship.manage — this action mutates a
     * relationship row, not just reads it.
     *
     * Concurrency: the entire flow is wrapped in a transaction. The resolution
     * claim is written with `WHERE status = 'pending'`; if `affected rows = 0`
     * the request was already resolved by a concurrent action and we bail out
     * without touching the relationship. This prevents a stale approve from
     * overwriting a concurrent reject (or a newly submitted pending request).
     */
    public function approveCorrectionRequest(int $requestId): void
    {
        $this->requirePermission('guardian_relationship.manage');

        DB::transaction(function () use ($requestId): void {
            // Atomically claim the request by transitioning it out of 'pending'.
            // We read the proposed values at the same time via a conditional UPDATE
            // trick: claim first, then read only if we won the race.
            $claimed = DB::table('guardian_correction_requests')
                ->where('id', $requestId)
                ->where('status', 'pending')
                ->update([
                    'status' => 'approved',
                    'pending_lock' => null,
                    'resolved_by_admin_id' => $this->adminId(),
                    'resolved_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($claimed === 0) {
                // Already resolved by a concurrent action — no-op.
                return;
            }

            // Re-read the now-resolved row to get the proposed values.
            $request = DB::table('guardian_correction_requests')
                ->where('id', $requestId)
                ->first();

            // Safety: verify ownership before touching the relationship row.
            $relBelongsToGuardian = DB::table('guardian_student_relationships')
                ->where('id', $request->guardian_student_relationship_id)
                ->where('guardian_profile_id', $this->guardianId)
                ->exists();

            if (! $relBelongsToGuardian) {
                // Roll back the claim — the request does not belong to this guardian.
                DB::table('guardian_correction_requests')
                    ->where('id', $requestId)
                    ->update([
                        'status' => 'pending',
                        'pending_lock' => 1,
                        'resolved_by_admin_id' => null,
                        'resolved_at' => null,
                        'updated_at' => now(),
                    ]);

                return;
            }

            $updates = ['updated_at' => now()];

            if ($request->requested_contact_priority !== null) {
                $updates['contact_priority'] = $request->requested_contact_priority;
            }

            if ($request->requested_is_emergency_contact !== null) {
                $updates['is_emergency_contact'] = (bool) $request->requested_is_emergency_contact;
            }

            DB::table('guardian_student_relationships')
                ->where('id', $request->guardian_student_relationship_id)
                ->update($updates);
        });
    }

    /**
     * Reject a pending correction request without applying any changes.
     *
     * Requires guardian_relationship.manage — consistent with approve.
     *
     * Concurrency: same claim-first pattern as approve. If affected rows = 0
     * the request was already resolved and we do nothing.
     */
    public function rejectCorrectionRequest(int $requestId): void
    {
        $this->requirePermission('guardian_relationship.manage');

        DB::transaction(function () use ($requestId): void {
            $claimed = DB::table('guardian_correction_requests')
                ->where('id', $requestId)
                ->where('status', 'pending')
                ->update([
                    'status' => 'rejected',
                    'pending_lock' => null,
                    'resolved_by_admin_id' => $this->adminId(),
                    'resolved_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($claimed === 0) {
                return;
            }

            // Verify ownership; roll back claim if the request doesn't belong here.
            $request = DB::table('guardian_correction_requests')
                ->where('id', $requestId)
                ->first();

            $relBelongsToGuardian = DB::table('guardian_student_relationships')
                ->where('id', $request->guardian_student_relationship_id)
                ->where('guardian_profile_id', $this->guardianId)
                ->exists();

            if (! $relBelongsToGuardian) {
                DB::table('guardian_correction_requests')
                    ->where('id', $requestId)
                    ->update([
                        'status' => 'pending',
                        'pending_lock' => 1,
                        'resolved_by_admin_id' => null,
                        'resolved_at' => null,
                        'updated_at' => now(),
                    ]);
            }
        });
    }

    public function render(): View
    {
        return view('livewire.admin.students.guardian-detail', [
            'relationships' => $this->relationships(),
            'pendingCorrectionRequests' => $this->pendingCorrectionRequests(),
            'canManage' => $this->adminCan('guardian_relationship.manage'),
        ])->layout('layouts.admin');
    }
}

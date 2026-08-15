<?php

declare(strict_types=1);

namespace App\Livewire\Guardian\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Authorization and eligibility helpers for Guardian portal Livewire components.
 *
 * ── Guardian → student access chain ──────────────────────────────────────
 * guardian_accounts.id
 *   → guardian_profiles.guardian_account_id
 *   → guardian_student_relationships.guardian_profile_id
 *       WHERE verification_status = 'verified'
 *         AND portal_eligible = true
 *         AND (ends_on IS NULL OR ends_on >= today)
 *
 * GuardianAccount alone grants NO student access. Portal eligibility is
 * determined entirely by the relationship record.
 *
 * ── Security contract ────────────────────────────────────────────────────
 * Call assertStudentAccessible() before returning ANY student data. The
 * check is lightweight (single indexed query) and prevents one authenticated
 * guardian from accessing another guardian's children.
 */
trait HasGuardianAuth
{
    /**
     * Per-instance cache for the resolved guardian_profile_id.
     * Instance-scoped so it does not bleed across tests or parallel requests.
     */
    private ?int $_guardianProfileIdCache = null;

    private bool $_guardianProfileIdResolved = false;

    // ── Profile resolver ──────────────────────────────────────────────────

    /**
     * Return the guardian_profile_id for the authenticated guardian account,
     * or null if no profile is linked.
     *
     * Cached for the lifetime of the component instance.
     */
    private function resolveGuardianProfileId(): ?int
    {
        if ($this->_guardianProfileIdResolved) {
            return $this->_guardianProfileIdCache;
        }

        $account = auth('guardian')->user();

        if ($account !== null) {
            $profile = DB::table('guardian_profiles')
                ->where('guardian_account_id', (int) $account->getKey())
                ->select('id')
                ->first();

            $this->_guardianProfileIdCache = $profile ? (int) $profile->id : null;
        }

        $this->_guardianProfileIdResolved = true;

        return $this->_guardianProfileIdCache;
    }

    // ── Profile presence ─────────────────────────────────────────────────

    /**
     * Return true if the authenticated guardian account has a linked profile.
     *
     * Use this to short-circuit before operations that require a profile.
     */
    protected function hasGuardianProfile(): bool
    {
        return $this->resolveGuardianProfileId() !== null;
    }

    // ── Eligibility ───────────────────────────────────────────────────────

    /**
     * Return all student_profile_ids this guardian has active, verified,
     * portal-eligible relationships for.
     *
     * @return list<int>
     */
    protected function eligibleStudentIds(): array
    {
        $profileId = $this->resolveGuardianProfileId();

        if ($profileId === null) {
            return [];
        }

        return DB::table('guardian_student_relationships')
            ->where('guardian_profile_id', $profileId)
            ->where('verification_status', 'verified')
            ->where('portal_eligible', true)
            ->where(fn ($q) => $q->whereNull('ends_on')->orWhere('ends_on', '>=', now()->toDateString()))
            ->pluck('student_profile_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Assert that the given student profile is accessible to this guardian.
     *
     * Aborts 403 if not accessible. Does NOT reveal whether the student
     * exists in the system — the same 403 is returned for unknown IDs.
     */
    protected function assertStudentAccessible(int $studentProfileId): void
    {
        $profileId = $this->resolveGuardianProfileId();

        if ($profileId === null) {
            abort(403, 'No guardian profile linked to this account.');
        }

        $exists = DB::table('guardian_student_relationships')
            ->where('guardian_profile_id', $profileId)
            ->where('student_profile_id', $studentProfileId)
            ->where('verification_status', 'verified')
            ->where('portal_eligible', true)
            ->where(fn ($q) => $q->whereNull('ends_on')->orWhere('ends_on', '>=', now()->toDateString()))
            ->exists();

        if (! $exists) {
            abort(403);
        }
    }

    /**
     * Return the authenticated guardian's relationship record to a student,
     * or null. Only returns portal-eligible relationships.
     */
    protected function relationshipTo(int $studentProfileId): ?object
    {
        $profileId = $this->resolveGuardianProfileId();

        if ($profileId === null) {
            return null;
        }

        return DB::table('guardian_student_relationships')
            ->where('guardian_profile_id', $profileId)
            ->where('student_profile_id', $studentProfileId)
            ->where('verification_status', 'verified')
            ->where('portal_eligible', true)
            ->where(fn ($q) => $q->whereNull('ends_on')->orWhere('ends_on', '>=', now()->toDateString()))
            ->select(
                'id',
                'relationship_type',
                'legal_authority',
                'contact_priority',
                'is_emergency_contact',
                'starts_on',
            )
            ->first();
    }
}

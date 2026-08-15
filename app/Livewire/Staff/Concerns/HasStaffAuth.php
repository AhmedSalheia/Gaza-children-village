<?php

declare(strict_types=1);

namespace App\Livewire\Staff\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Authorization, scope, and period isolation for Staff portal Livewire components.
 *
 * ── Single-position design ────────────────────────────────────────────────
 * Every authorization decision (permissions, scope, period grants, full-scope
 * status) derives from the SAME single "trusted" active position — the one with
 * the highest institution_semester_id — resolved by resolveActivePosition().
 *
 * This prevents cross-institution leakage: a full-scope role at Institution A
 * cannot elevate access within Institution B's semester, because all checks
 * are tied to the one resolved position's institution/semester/definition.
 *
 * ── Period restriction rules (F16 schema spec) ────────────────────────────
 * • Principal / deputy_principal / counselor → full-scope: no per-period
 *   restriction; they see and act on all operational periods in their semester.
 * • Secretary / teacher → period-restricted: must have explicit grants via
 *   staff_position_periods. Zero grants → zero access (reads return empty,
 *   mutation guards abort 403).
 *
 * ── Centralized scope guards ──────────────────────────────────────────────
 * Call the assert* helpers before every read detail and every public mutation.
 * Do NOT rely on rendered options/lists to prevent out-of-scope submissions.
 *
 * assertStudentAccessible(int $studentProfileId)
 * assertClassGroupInScope(int $classGroupId)
 * assertEnrollmentInScope(int $enrollmentId)
 */
trait HasStaffAuth
{
    /**
     * Position definitions that receive full semester-level access with no
     * per-period restriction (matches Modules\Staff\Enums\PositionDefinition).
     *
     * @var list<string>
     */
    private const FULL_SCOPE_POSITIONS = ['principal', 'deputy_principal', 'counselor'];

    /**
     * Per-component-instance position cache.
     *
     * Keyed by staff account primary key. Using a property (not a function-level
     * static) ensures each Livewire component instance starts with an empty cache
     * — critical for test isolation where the same account ID may be reused across
     * test methods after a database rollback.
     *
     * @var array<int, object|null>
     */
    private array $resolvedPositionCache = [];

    // ── Core position resolver ────────────────────────────────────────────

    /**
     * Resolve the single trusted active position for this staff account.
     *
     * "Active" means started_on <= today AND (ended_on IS NULL OR ended_on >= today).
     * When the account holds multiple active positions we select the one with the
     * highest institution_semester_id to prefer the most recent academic assignment.
     *
     * All other methods in this trait derive their data exclusively from this record
     * to ensure consistent scope isolation across concurrent positions.
     *
     * Results are cached in the per-instance $resolvedPositionCache property for
     * the lifetime of the Livewire component instance (one HTTP round-trip); safe
     * because auth identity cannot change mid-request.
     */
    private function resolveActivePosition(): ?object
    {
        $account = auth('staff')->user();

        if ($account === null || ! $account->staff_profile_id) {
            return null;
        }

        $key = (int) $account->getKey();

        if (! array_key_exists($key, $this->resolvedPositionCache)) {
            $this->resolvedPositionCache[$key] = DB::table('staff_positions')
                ->where('staff_profile_id', $account->staff_profile_id)
                ->where('started_on', '<=', now()->toDateString())
                ->where(fn ($q) => $q->whereNull('ended_on')->orWhere('ended_on', '>=', now()->toDateString()))
                ->orderByDesc('institution_semester_id')
                ->select('id', 'institution_id', 'institution_semester_id', 'position_definition')
                ->first();
        }

        return $this->resolvedPositionCache[$key];
    }

    // ── Permission ────────────────────────────────────────────────────────

    /**
     * Abort with 403 if the authenticated staff member does not hold the
     * given permission through the trusted active position.
     */
    protected function requirePermission(string $permissionKey): void
    {
        if (! $this->staffCan($permissionKey)) {
            abort(403, __('ui.unauthorized', [], null, 'You are not authorised to access this page.'));
        }
    }

    /**
     * Check whether the trusted active position grants a specific permission.
     *
     * Joins position_role_grants → role_permissions → permissions using only the
     * single resolved position_definition, preventing grants from other concurrent
     * positions at different institutions from leaking in.
     */
    protected function staffCan(string $permissionKey): bool
    {
        $pos = $this->resolveActivePosition();

        if ($pos === null) {
            return false;
        }

        return DB::table('position_role_grants as prg')
            ->join('role_permissions as rp', 'rp.role_id', '=', 'prg.role_id')
            ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
            ->where('prg.position_definition', $pos->position_definition)
            ->where('p.key', $permissionKey)
            ->exists();
    }

    // ── Operational scope ─────────────────────────────────────────────────

    /**
     * Return the institution_id and institution_semester_id from the trusted
     * active position. Either may be null if no active position is found.
     *
     * @return array{institution_id: int|null, institution_semester_id: int|null}
     */
    protected function staffScope(): array
    {
        $pos = $this->resolveActivePosition();

        return [
            'institution_id' => $pos ? (int) $pos->institution_id : null,
            'institution_semester_id' => $pos
                ? ($pos->institution_semester_id ? (int) $pos->institution_semester_id : null)
                : null,
        ];
    }

    // ── Period scope ──────────────────────────────────────────────────────

    /**
     * Whether the trusted active position is full-scope (no per-period restriction).
     *
     * Full-scope positions: principal, deputy_principal, counselor.
     * Period-restricted positions: secretary, teacher — need explicit grants.
     *
     * MUST be called before allowedPeriodIds() to decide whether to apply a period
     * filter at all. For full-scope positions, omit the period filter entirely.
     */
    protected function isFullScopePosition(): bool
    {
        $pos = $this->resolveActivePosition();

        if ($pos === null) {
            return false;
        }

        return in_array($pos->position_definition, self::FULL_SCOPE_POSITIONS, true);
    }

    /**
     * Return the operational_period_ids explicitly granted to the trusted active
     * position via staff_position_periods.
     *
     * IMPORTANT: call isFullScopePosition() first.
     * – Full-scope position → ignore this method; apply no period filter.
     * – Period-restricted with empty result → the staff has no period grants;
     *   reads must return zero results and mutations must abort 403.
     *
     * @return list<int>
     */
    protected function allowedPeriodIds(): array
    {
        $pos = $this->resolveActivePosition();

        if ($pos === null) {
            return [];
        }

        return DB::table('staff_position_periods')
            ->where('staff_position_id', (int) $pos->id)
            ->pluck('operational_period_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    // ── Centralized scope guards ──────────────────────────────────────────

    /**
     * Assert that the given student profile is accessible to this staff member.
     *
     * Requires the student to have at least one enrollment in the trusted semester.
     * For period-restricted positions (not isFullScopePosition) the enrollment's
     * class group must also fall within the allowed operational periods.
     *
     * Aborts 403 if no active semester scope, or 403/404 if the student is not
     * accessible under the combined institution + period restriction.
     */
    protected function assertStudentAccessible(int $studentProfileId): void
    {
        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            abort(403, 'No active institutional scope for your account.');
        }

        $query = DB::table('student_enrollments as se')
            ->join('class_groups as cg', 'cg.id', '=', 'se.class_group_id')
            ->where('se.student_profile_id', $studentProfileId)
            ->where('se.institution_semester_id', $scope['institution_semester_id']);

        if (! $this->isFullScopePosition()) {
            $allowed = $this->allowedPeriodIds();

            if (empty($allowed)) {
                abort(403, 'Your position has no period grants — no student access.');
            }

            $query->whereIn('cg.operational_period_id', $allowed);
        }

        if (! $query->exists()) {
            // The student either doesn't exist in this semester or is outside
            // the allowed periods — surface as 404 to avoid enumeration.
            abort(404);
        }
    }

    /**
     * Assert that the given class group is within the trusted institution semester
     * AND within the allowed operational periods for period-restricted positions.
     *
     * Call this before every mutation that references a class group.
     */
    protected function assertClassGroupInScope(int $classGroupId): void
    {
        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            abort(403, 'No active institutional scope for your account.');
        }

        $cg = DB::table('class_groups')
            ->where('id', $classGroupId)
            ->where('institution_semester_id', $scope['institution_semester_id'])
            ->select('id', 'operational_period_id')
            ->first();

        if (! $cg) {
            abort(403, 'Class group is not in your assigned semester.');
        }

        if (! $this->isFullScopePosition()) {
            $allowed = $this->allowedPeriodIds();

            if (empty($allowed) || ! in_array((int) $cg->operational_period_id, $allowed, true)) {
                abort(403, 'Class group is not in your assigned operational period.');
            }
        }
    }

    /**
     * Assert that the given enrollment is within the trusted institution semester
     * AND within the allowed operational periods for period-restricted positions.
     *
     * Call this before every mutation that references an enrollment by ID.
     */
    protected function assertEnrollmentInScope(int $enrollmentId): void
    {
        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            abort(403, 'No active institutional scope for your account.');
        }

        $row = DB::table('student_enrollments as se')
            ->join('class_groups as cg', 'cg.id', '=', 'se.class_group_id')
            ->where('se.id', $enrollmentId)
            ->where('se.institution_semester_id', $scope['institution_semester_id'])
            ->select('se.id', 'cg.operational_period_id')
            ->first();

        if (! $row) {
            abort(403, 'Enrollment is not in your assigned semester.');
        }

        if (! $this->isFullScopePosition()) {
            $allowed = $this->allowedPeriodIds();

            if (empty($allowed) || ! in_array((int) $row->operational_period_id, $allowed, true)) {
                abort(403, 'Enrollment is not in your assigned operational period.');
            }
        }
    }

    /**
     * Assert that the given attendance sheet is accessible to this staff member.
     *
     * Checks:
     *  1. The sheet exists and belongs to the trusted institution semester.
     *  2. For period-restricted positions (secretary, teacher): the sheet's class
     *     group must fall within the explicitly granted operational periods.
     *
     * Returns the raw sheet row (with class_group_id and operational_period_id)
     * so callers can perform additional checks (e.g. homeroom assignment for teachers)
     * without a second DB round-trip.
     *
     * Aborts 404 when the sheet is not found or is outside the institution/semester.
     * Aborts 403 when it is outside the allowed period.
     */
    protected function assertSheetInScope(int $sheetId): object
    {
        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            abort(403, 'No active institutional scope for your account.');
        }

        $sheet = DB::table('student_attendance_sheets as sas')
            ->join('class_groups as cg', 'cg.id', '=', 'sas.class_group_id')
            ->where('sas.id', $sheetId)
            ->where('sas.institution_semester_id', $scope['institution_semester_id'])
            ->select('sas.id', 'sas.class_group_id', 'sas.institution_semester_id', 'cg.operational_period_id')
            ->first();

        if (! $sheet) {
            // Sheet does not exist or belongs to a different institution/semester.
            // Surface as 404 to prevent enumeration.
            abort(404);
        }

        if (! $this->isFullScopePosition()) {
            $allowed = $this->allowedPeriodIds();

            if (empty($allowed) || ! in_array((int) $sheet->operational_period_id, $allowed, true)) {
                abort(403, 'Attendance sheet is not in your assigned operational period.');
            }
        }

        return $sheet;
    }

    // ── Actor identity helpers ────────────────────────────────────────────

    /**
     * Return a string actor reference for audit trail parameters.
     */
    protected function staffActorReference(): string
    {
        return sprintf('staff:%d', (int) auth('staff')->id());
    }

    /**
     * Return the authenticated staff account ID.
     */
    protected function staffAccountId(): int
    {
        return (int) auth('staff')->id();
    }

    /**
     * Return the staff_profile_id for the authenticated staff member, or null.
     */
    protected function staffProfileId(): ?int
    {
        $account = auth('staff')->user();

        return $account?->staff_profile_id ? (int) $account->staff_profile_id : null;
    }
}

<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Illuminate\Support\Facades\DB;

/**
 * Central authorization checks for report generation and download.
 *
 * Used at every trust boundary that is separated in time from the original
 * Livewire action: queued job execution and file download. Permissions and
 * staff scope are ALWAYS resolved fresh from the database so that revocation
 * takes effect immediately, even for already-generated exports.
 *
 * Cross-module table access is DB::table() only (approved boundary pattern).
 */
final class ReportAuthorizationService
{
    /**
     * Legacy report components (attendance / marks report pages) predate the
     * report_definitions registry. Their export types map to the page-level
     * permissions those components enforce.
     *
     * @var array<string, string>
     */
    private const LEGACY_EXPORT_PERMISSIONS = [
        'attendance_report' => 'attendance_report.export',
        'staff_attendance_report' => 'attendance_report.export',
        'marks_completion' => 'result_report.export',
        'result_report' => 'result_report.export',
    ];

    /**
     * The definition permission required for a given export type (definition
     * code). Returns null when the definition does not exist.
     */
    public function requiredPermission(string $exportType): ?string
    {
        $key = DB::table('report_definitions')
            ->where('code', $exportType)
            ->value('permission_key');

        return $key !== null ? (string) $key : null;
    }

    public function adminCan(int $accountId, string $permission): bool
    {
        return DB::table('administrative_account_roles as aar')
            ->join('role_permissions as rp', 'rp.role_id', '=', 'aar.role_id')
            ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
            ->where('aar.administrative_account_id', $accountId)
            ->whereNull('aar.revoked_at')
            ->where('p.key', $permission)
            ->exists();
    }

    /**
     * Whether the admin may retrieve an export of the given type right now.
     */
    public function adminCanAccessExport(int $accountId, string $exportType): bool
    {
        // Legacy report-page exports are gated by their page permission only.
        if (isset(self::LEGACY_EXPORT_PERMISSIONS[$exportType])) {
            return $this->adminCan($accountId, self::LEGACY_EXPORT_PERMISSIONS[$exportType]);
        }

        $definitionPermission = $this->requiredPermission($exportType);

        return $definitionPermission !== null
            && $this->adminCan($accountId, 'report.read')
            && $this->adminCan($accountId, 'export.create')
            && $this->adminCan($accountId, $definitionPermission);
    }

    /**
     * Resolve the staff account's current active position (trusted scope).
     */
    public function staffActivePosition(int $accountId): ?object
    {
        $profileId = DB::table('staff_accounts')
            ->where('id', $accountId)
            ->value('staff_profile_id');

        if (! $profileId) {
            return null;
        }

        return DB::table('staff_positions')
            ->where('staff_profile_id', (int) $profileId)
            ->where('started_on', '<=', now()->toDateString())
            ->where(fn ($q) => $q->whereNull('ended_on')->orWhere('ended_on', '>=', now()->toDateString()))
            ->orderByDesc('institution_semester_id')
            ->select('id', 'institution_id', 'institution_semester_id', 'position_definition')
            ->first();
    }

    public function staffPositionCan(string $positionDefinition, string $permission): bool
    {
        return DB::table('position_role_grants as prg')
            ->join('role_permissions as rp', 'rp.role_id', '=', 'prg.role_id')
            ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
            ->where('prg.position_definition', $positionDefinition)
            ->where('p.key', $permission)
            ->exists();
    }

    /**
     * Whether the staff account may retrieve an export of the given type right now.
     */
    public function staffCanAccessExport(int $accountId, string $exportType): bool
    {
        $position = $this->staffActivePosition($accountId);

        if ($position === null) {
            return false;
        }

        // Legacy report-page exports are gated by their page permission only.
        if (isset(self::LEGACY_EXPORT_PERMISSIONS[$exportType])) {
            return $this->staffPositionCan(
                (string) $position->position_definition,
                self::LEGACY_EXPORT_PERMISSIONS[$exportType],
            );
        }

        $definitionPermission = $this->requiredPermission($exportType);

        if ($definitionPermission === null) {
            return false;
        }

        // admin_only definitions are never staff-accessible.
        $adminOnly = (bool) DB::table('report_definitions')
            ->where('code', $exportType)
            ->value('admin_only');

        if ($adminOnly) {
            return false;
        }

        $def = (string) $position->position_definition;

        return $this->staffPositionCan($def, 'report.read')
            && $this->staffPositionCan($def, 'export.create')
            && $this->staffPositionCan($def, $definitionPermission);
    }

    /** Full-scope positions need no per-period grants. */
    public function isFullScopePosition(string $positionDefinition): bool
    {
        return in_array($positionDefinition, ['principal', 'deputy_principal', 'counselor'], true);
    }

    /** @return list<int> */
    public function allowedPeriodIds(int $staffPositionId): array
    {
        return DB::table('staff_position_periods')
            ->where('staff_position_id', $staffPositionId)
            ->pluck('operational_period_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}

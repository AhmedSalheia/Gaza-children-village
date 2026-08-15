<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Publications;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;
use Modules\AcademicManagement\Actions\ConfigureAttendancePolicy;
use Modules\AcademicManagement\Actions\PublishAttendanceSnapshot;
use Modules\AcademicManagement\Actions\RevokeAttendanceSnapshot;
use Modules\AcademicManagement\Exceptions\MarksException;
use Modules\AcademicManagement\Models\AttendancePublicationPolicy;
use Modules\AcademicManagement\Models\AttendancePublicationSnapshot;
use Modules\Authorization\Data\PermissionKey;

/**
 * Admin UI for configuring attendance publication policy and publishing snapshots.
 *
 * Per-semester configuration:
 *   - Enable/disable guardian-visible attendance
 *   - Detail level (summary_only | daily_status)
 *   - publish_delay_days, show_reason, show_arrival_departure
 *
 * Per-class-group actions:
 *   - Publish a new attendance snapshot
 *   - View and revoke existing snapshots
 */
final class AttendancePublicationPolicyConfig extends Component
{
    use HasAdminAuth;

    public int    $semesterId    = 0;
    public int    $classGroupId  = 0;

    // Policy form fields
    public bool   $policyEnabled            = false;
    public string $detailLevel              = 'summary_only';
    public int    $publishDelayDays         = 0;
    public bool   $showReason               = false;
    public bool   $showArrivalDeparture     = false;

    public int    $revokingSnapshotId   = 0;
    public string $revokeReason         = '';

    public string $flashMessage = '';
    public string $flashType    = '';

    public function mount(): void
    {
        $this->requirePermission(PermissionKey::STUDENT_ATTENDANCE_PUBLISH);
    }

    public function updatedSemesterId(): void
    {
        $this->classGroupId = 0;
        $this->loadPolicy();
    }

    public function openSemesters(): Collection
    {
        return DB::table('institution_semesters as is2')
            ->join('institutions as i', 'i.id', '=', 'is2.institution_id')
            ->join('semesters as s', 's.id', '=', 'is2.semester_id')
            ->where('is2.status', 'open')
            ->orderBy('i.name_ar')
            ->get(['is2.id', 'i.name_ar as institution_name', 's.name_ar as semester_name']);
    }

    public function classGroups(): Collection
    {
        if ($this->semesterId === 0) {
            return collect();
        }

        return DB::table('class_groups')
            ->where('institution_semester_id', $this->semesterId)
            ->where('lifecycle_status', 'active')
            ->orderBy('name_ar')
            ->get(['id', 'name_ar']);
    }

    public function currentPolicy(): ?AttendancePublicationPolicy
    {
        if ($this->semesterId === 0) {
            return null;
        }

        return AttendancePublicationPolicy::where('institution_semester_id', $this->semesterId)->first();
    }

    public function snapshots(): Collection
    {
        if ($this->semesterId === 0 || $this->classGroupId === 0) {
            return collect();
        }

        return DB::table('attendance_publication_snapshots')
            ->where('institution_semester_id', $this->semesterId)
            ->where('class_group_id', $this->classGroupId)
            ->orderByDesc('version')
            ->get(['id', 'version', 'status', 'published_at', 'revoked_at', 'revoke_reason', 'period_from', 'period_to', 'superseded_by_id']);
    }

    // ── Mutations ─────────────────────────────────────────────────────────

    public function savePolicy(): void
    {
        $this->requirePermission(PermissionKey::STUDENT_ATTENDANCE_PUBLISH);

        if ($this->semesterId === 0) {
            return;
        }

        $this->validate([
            'detailLevel'      => ['required', 'in:summary_only,daily_status'],
            'publishDelayDays' => ['required', 'integer', 'min:0', 'max:30'],
        ]);

        app(ConfigureAttendancePolicy::class)(
            institutionSemesterId: $this->semesterId,
            enabled: $this->policyEnabled,
            detailLevel: $this->detailLevel,
            publishDelayDays: $this->publishDelayDays,
            showReason: $this->showReason,
            showArrivalDeparture: $this->showArrivalDeparture,
        );

        $this->flash('Policy saved.', 'success');
    }

    public function publishSnapshot(): void
    {
        $this->requirePermission(PermissionKey::STUDENT_ATTENDANCE_PUBLISH);

        if ($this->semesterId === 0 || $this->classGroupId === 0) {
            return;
        }

        try {
            app(PublishAttendanceSnapshot::class)(
                institutionSemesterId: $this->semesterId,
                classGroupId: $this->classGroupId,
                publisherStaffProfileId: 0, // admin sentinel
            );
            $this->flash('Attendance snapshot published.', 'success');
        } catch (MarksException $e) {
            $this->flash($e->getMessage(), 'error');
        }
    }

    public function startRevokeSnapshot(int $snapshotId): void
    {
        $this->requirePermission(PermissionKey::STUDENT_ATTENDANCE_PUBLISH);
        $this->revokingSnapshotId = $snapshotId;
        $this->revokeReason       = '';
    }

    public function confirmRevokeSnapshot(): void
    {
        $this->requirePermission(PermissionKey::STUDENT_ATTENDANCE_PUBLISH);

        $this->validate(['revokeReason' => ['required', 'string', 'min:5']]);

        $snapshot = AttendancePublicationSnapshot::where('id', $this->revokingSnapshotId)
            ->where('institution_semester_id', $this->semesterId)
            ->first();

        if (! $snapshot) {
            abort(404);
        }

        try {
            app(RevokeAttendanceSnapshot::class)(
                snapshot: $snapshot,
                revokeReason: $this->revokeReason,
                revokedByStaffProfileId: 0,
            );
            $this->revokingSnapshotId = 0;
            $this->revokeReason       = '';
            $this->flash('Snapshot revoked.', 'success');
        } catch (MarksException $e) {
            $this->flash($e->getMessage(), 'error');
        }
    }

    public function cancelRevokeSnapshot(): void
    {
        $this->revokingSnapshotId = 0;
        $this->revokeReason       = '';
    }

    public function render(): View
    {
        return view('livewire.admin.publications.attendance-policy-config', [
            'openSemesters' => $this->openSemesters(),
            'classGroups'   => $this->classGroups(),
            'policy'        => $this->currentPolicy(),
            'snapshots'     => $this->snapshots(),
            'canPublish'    => $this->adminCan(PermissionKey::STUDENT_ATTENDANCE_PUBLISH),
        ])->layout('layouts.admin');
    }

    private function loadPolicy(): void
    {
        if ($this->semesterId === 0) {
            $this->policyEnabled        = false;
            $this->detailLevel          = 'summary_only';
            $this->publishDelayDays     = 0;
            $this->showReason           = false;
            $this->showArrivalDeparture = false;

            return;
        }

        $p = $this->currentPolicy();

        if ($p) {
            $this->policyEnabled        = $p->enabled;
            $this->detailLevel          = $p->detail_level;
            $this->publishDelayDays     = $p->publish_delay_days;
            $this->showReason           = $p->show_reason;
            $this->showArrivalDeparture = $p->show_arrival_departure;
        }
    }

    private function flash(string $message, string $type = 'success'): void
    {
        $this->flashMessage = $message;
        $this->flashType    = $type;
    }
}

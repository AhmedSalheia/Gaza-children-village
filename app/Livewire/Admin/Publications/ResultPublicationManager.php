<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Publications;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;
use Modules\AcademicManagement\Actions\PublishResults;
use Modules\AcademicManagement\Actions\RevokeResultPublication;
use Modules\AcademicManagement\Exceptions\MarksException;
use Modules\AcademicManagement\Models\ResultPublication;
use Modules\Authorization\Data\PermissionKey;

/**
 * Admin UI for publishing, versioning, and revoking result publications.
 *
 * Workflow:
 *   1. Select institution semester → select class group → Publish
 *   2. View all publication versions for the selected class group
 *   3. Revoke a published version (reason required)
 */
final class ResultPublicationManager extends Component
{
    use HasAdminAuth;

    public int $semesterId = 0;

    public int $classGroupId = 0;

    public int $revokingId = 0;

    public string $revokeReason = '';

    public string $flashMessage = '';

    public string $flashType = '';

    public function mount(): void
    {
        $this->requirePermission(PermissionKey::RESULTS_PUBLISH);
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

    public function publications(): Collection
    {
        if ($this->semesterId === 0 || $this->classGroupId === 0) {
            return collect();
        }

        return DB::table('result_publications')
            ->where('institution_semester_id', $this->semesterId)
            ->where('class_group_id', $this->classGroupId)
            ->orderByDesc('version')
            ->get([
                'id', 'version', 'status', 'published_at', 'revoked_at',
                'revoke_reason', 'superseded_by_id', 'publisher_staff_profile_id',
            ]);
    }

    public function readinessSummary(): ?object
    {
        if ($this->semesterId === 0 || $this->classGroupId === 0) {
            return null;
        }

        $total = DB::table('mark_sheets')
            ->where('institution_semester_id', $this->semesterId)
            ->where('class_group_id', $this->classGroupId)
            ->whereNotIn('status', ['superseded'])
            ->count();

        $approved = DB::table('mark_sheets')
            ->where('institution_semester_id', $this->semesterId)
            ->where('class_group_id', $this->classGroupId)
            ->where('status', 'approved')
            ->count();

        return (object) [
            'total' => $total,
            'approved' => $approved,
            'outstanding' => $total - $approved,
            'ready' => $total > 0 && $approved === $total,
        ];
    }

    // ── Mutations ─────────────────────────────────────────────────────────

    public function publish(): void
    {
        $this->requirePermission(PermissionKey::RESULTS_PUBLISH);

        if ($this->semesterId === 0 || $this->classGroupId === 0) {
            return;
        }

        $profileId = $this->adminProfileId();

        try {
            app(PublishResults::class)(
                institutionSemesterId: $this->semesterId,
                classGroupId: $this->classGroupId,
                publisherStaffProfileId: $profileId ?? 0,
            );
            $this->flash('Results published successfully.', 'success');
        } catch (MarksException $e) {
            $this->flash($e->getMessage(), 'error');
        }
    }

    public function startRevoke(int $publicationId): void
    {
        $this->requirePermission(PermissionKey::RESULTS_REVOKE);
        $this->revokingId = $publicationId;
        $this->revokeReason = '';
    }

    public function confirmRevoke(): void
    {
        $this->requirePermission(PermissionKey::RESULTS_REVOKE);

        $this->validate(['revokeReason' => ['required', 'string', 'min:5']]);

        $pub = ResultPublication::where('id', $this->revokingId)
            ->where('institution_semester_id', $this->semesterId)
            ->first();

        if (! $pub) {
            abort(404);
        }

        $profileId = $this->adminProfileId();

        try {
            app(RevokeResultPublication::class)(
                publication: $pub,
                revokeReason: $this->revokeReason,
                revokedByStaffProfileId: $profileId ?? 0,
            );
            $this->revokingId = 0;
            $this->revokeReason = '';
            $this->flash('Publication revoked.', 'success');
        } catch (MarksException $e) {
            $this->flash($e->getMessage(), 'error');
        }
    }

    public function cancelRevoke(): void
    {
        $this->revokingId = 0;
        $this->revokeReason = '';
    }

    public function render(): View
    {
        return view('livewire.admin.publications.result-publication-manager', [
            'openSemesters' => $this->openSemesters(),
            'classGroups' => $this->classGroups(),
            'publications' => $this->publications(),
            'readiness' => $this->readinessSummary(),
            'canPublish' => $this->adminCan(PermissionKey::RESULTS_PUBLISH),
            'canRevoke' => $this->adminCan(PermissionKey::RESULTS_REVOKE),
        ])->layout('layouts.admin');
    }

    private function flash(string $message, string $type = 'success'): void
    {
        $this->flashMessage = $message;
        $this->flashType = $type;
    }

    private function adminProfileId(): ?int
    {
        return null; // AdminAccount has no staff_profile_id; use 0 as sentinel
    }
}

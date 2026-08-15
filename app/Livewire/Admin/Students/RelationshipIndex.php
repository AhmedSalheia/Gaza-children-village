<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Students;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Students\Actions\ActivateRelationship;
use Modules\Students\Actions\EndRelationship;
use Modules\Students\Actions\VerifyRelationship;
use Modules\Students\Models\GuardianStudentRelationship;

/**
 * List all guardian-student relationships with verify/activate/end actions.
 *
 * Schema reference (guardian_student_relationships):
 *  - verification_status  string(32), default 'unverified'
 *  - portal_eligible      boolean, default false
 *  - ends_on              date nullable  (null = active/not ended)
 *
 * There is no relationship_status column; active vs ended is derived from ends_on.
 */
final class RelationshipIndex extends Component
{
    use HasAdminAuth;
    use WithPagination;

    /** Filter by derived status: 'active' (ends_on IS NULL) or 'ended' (ends_on NOT NULL). */
    #[Url]
    public string $statusFilter = '';

    public string $flashMessage = '';

    public string $flashType = '';

    public function mount(): void
    {
        $this->requirePermission('guardian_relationship.view');
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function relationships(): LengthAwarePaginator
    {
        return DB::table('guardian_student_relationships as gsr')
            ->join('guardian_profiles as gp', 'gp.id', '=', 'gsr.guardian_profile_id')
            ->join('people as gp_person', 'gp_person.id', '=', 'gp.person_id')
            ->join('student_profiles as sp', 'sp.id', '=', 'gsr.student_profile_id')
            ->join('people as sp_person', 'sp_person.id', '=', 'sp.person_id')
            ->select([
                'gsr.id',
                'gsr.relationship_type',
                'gsr.verification_status',
                'gsr.portal_eligible',
                'gsr.ends_on',
                'gp.guardian_code',
                'gp_person.full_name_ar as guardian_name',
                'sp.student_code',
                'sp_person.full_name_ar as student_name',
            ])
            ->when($this->statusFilter === 'active', fn ($q) => $q->where(function ($inner): void {
                $inner->whereNull('gsr.ends_on')->orWhere('gsr.ends_on', '>=', now()->toDateString());
            }))
            ->when($this->statusFilter === 'ended', fn ($q) => $q->where('gsr.ends_on', '<', now()->toDateString()))
            ->orderByDesc('gsr.created_at')
            ->paginate(25);
    }

    public function verify(int $relId): void
    {
        $this->requirePermission('guardian_relationship.verify');

        try {
            $rel = GuardianStudentRelationship::findOrFail($relId);
            app(VerifyRelationship::class)($rel, 'admin:'.$this->adminId());
            $this->flash('success', __('ui.saved', [], null, 'Relationship verified.'));
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    public function activate(int $relId): void
    {
        $this->requirePermission('guardian_relationship.manage');

        try {
            $rel = GuardianStudentRelationship::findOrFail($relId);
            app(ActivateRelationship::class)($rel, 'admin:'.$this->adminId());
            $this->flash('success', __('ui.saved', [], null, 'Relationship activated.'));
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    public function end(int $relId): void
    {
        $this->requirePermission('guardian_relationship.manage');

        try {
            $rel = GuardianStudentRelationship::findOrFail($relId);
            app(EndRelationship::class)($rel, 'admin:'.$this->adminId());
            $this->flash('success', __('ui.saved', [], null, 'Relationship ended.'));
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    private function flash(string $type, string $message): void
    {
        $this->flashType = $type;
        $this->flashMessage = $message;
    }

    public function render(): View
    {
        return view('livewire.admin.students.relationships', [
            'relationships' => $this->relationships(),
        ])->layout('layouts.admin');
    }
}

<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Assignments;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\AcademicManagement\Actions\CreateTeachingAssignment;
use Modules\AcademicManagement\Actions\EndTeachingAssignment;
use Modules\AcademicManagement\Actions\ReplaceTeachingAssignment;
use Modules\AcademicManagement\Enums\AssignmentStatus;
use Modules\AcademicManagement\Models\TeachingAssignment;
use Modules\Authorization\Data\PermissionKey;

/**
 * Admin portal screen for managing teaching assignments.
 *
 * Filterable by institution semester and class group.
 * Shows active assignments by default; toggle to see full history.
 *
 * Supported workflows:
 *  - Create: link a teacher/trainer position to a class group + subject offering.
 *  - End: mark an active assignment ended (history preserved).
 *  - Replace: atomically supersede an active assignment and create a new one
 *    for the same class group + subject offering with a different teacher.
 *    Uses the ReplaceTeachingAssignment action to maintain `superseded` history.
 */
final class TeachingAssignmentIndex extends Component
{
    use HasAdminAuth;
    use WithPagination;

    #[Url]
    public int $instSemId = 0;

    #[Url]
    public int $classGroupId = 0;

    #[Url]
    public bool $showHistory = false;

    // Create form
    public bool $showForm = false;

    public int $formPositionId = 0;

    public int $formClassGroupId = 0;

    public int $formSubjectId = 0;

    public string $formStartsOn = '';

    // End form
    public ?int $endingId = null;

    public string $endReason = '';

    public string $endDate = '';

    // Replace form
    public ?int $replacingId = null;

    public int $replacePositionId = 0;

    public string $replaceDate = '';

    public string $replaceReason = '';

    public string $flashMessage = '';

    public string $flashType = '';

    public function mount(): void
    {
        $this->requirePermission(PermissionKey::TEACHING_ASSIGNMENT_READ);
        $this->formStartsOn = now()->toDateString();
        $this->endDate = now()->toDateString();
        $this->replaceDate = now()->toDateString();
    }

    public function openSemesters(): Collection
    {
        return DB::table('institution_semesters as is2')
            ->join('institutions as i', 'i.id', '=', 'is2.institution_id')
            ->join('semesters as s', 's.id', '=', 'is2.semester_id')
            ->orderBy('i.name_ar')
            ->orderBy('s.name_ar')
            ->get(['is2.id', 'i.name_ar as institution_name', 's.name_ar as semester_name', 'is2.status']);
    }

    public function classGroups(): Collection
    {
        if ($this->instSemId === 0) {
            return collect();
        }

        return DB::table('class_groups as cg')
            ->join('academic_levels as al', 'al.id', '=', 'cg.academic_level_id')
            ->where('cg.institution_semester_id', $this->instSemId)
            ->orderBy('cg.name_ar')
            ->get(['cg.id', 'cg.name_ar', 'al.name_ar as level_name']);
    }

    public function subjectOfferings(): Collection
    {
        if ($this->instSemId === 0) {
            return collect();
        }

        return DB::table('institution_subject_offerings as iso')
            ->join('subjects as s', 's.id', '=', 'iso.subject_id')
            ->where('iso.institution_semester_id', $this->instSemId)
            ->orderBy('s.name_ar')
            ->get(['iso.id', 's.name_ar as subject_name', 's.name_en as subject_name_en']);
    }

    /** Teacher/trainer positions in the selected semester. */
    public function eligiblePositions(): Collection
    {
        if ($this->instSemId === 0) {
            return collect();
        }

        $institutionId = DB::table('institution_semesters')
            ->where('id', $this->instSemId)
            ->value('institution_id');

        if (! $institutionId) {
            return collect();
        }

        return DB::table('staff_positions as sp')
            ->join('staff_profiles as spf', 'spf.id', '=', 'sp.staff_profile_id')
            ->join('staff_institution_assignments as sia', 'sia.id', '=', 'sp.staff_institution_assignment_id')
            ->join('people as p', 'p.id', '=', 'spf.person_id')
            ->where('sia.institution_id', $institutionId)
            ->whereNull('sia.ended_on')
            ->whereNull('sp.ended_on')
            ->whereIn('sp.position_definition', ['teacher', 'trainer'])
            ->where('sp.institution_semester_id', $this->instSemId)
            ->orderBy('p.full_name_ar')
            ->get(['sp.id', 'p.full_name_ar as staff_name', 'sp.position_definition']);
    }

    public function assignments()
    {
        if ($this->instSemId === 0) {
            return collect();
        }

        $query = DB::table('teaching_assignments as ta')
            ->join('class_groups as cg', 'cg.id', '=', 'ta.class_group_id')
            ->join('academic_levels as al', 'al.id', '=', 'cg.academic_level_id')
            ->join('institution_subject_offerings as iso', 'iso.id', '=', 'ta.subject_offering_id')
            ->join('subjects as sub', 'sub.id', '=', 'iso.subject_id')
            ->leftJoin('people as p', function ($j): void {
                $j->on('p.id', '=', DB::raw(
                    '(SELECT spf.person_id FROM staff_profiles spf WHERE spf.id = ta.staff_profile_id LIMIT 1)'
                ));
            })
            ->where('ta.institution_semester_id', $this->instSemId)
            ->select(
                'ta.id',
                'ta.staff_profile_id',
                'ta.starts_on',
                'ta.ends_on',
                'ta.status',
                'ta.ends_reason',
                'cg.name_ar as class_group_name',
                'al.name_ar as level_name',
                'sub.name_ar as subject_name',
                'p.full_name_ar as staff_name',
            );

        if (! $this->showHistory) {
            $query->where('ta.status', AssignmentStatus::Active->value);
        }

        if ($this->classGroupId > 0) {
            $query->where('ta.class_group_id', $this->classGroupId);
        }

        return $query->orderBy('cg.name_ar')->orderBy('sub.name_ar')->paginate(30);
    }

    public function updatingInstSemId(): void
    {
        $this->resetPage();
        $this->reset(['classGroupId', 'formPositionId', 'formClassGroupId', 'formSubjectId',
            'endingId', 'replacingId']);
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function save(): void
    {
        $this->requirePermission(PermissionKey::TEACHING_ASSIGNMENT_MANAGE);

        $this->validate([
            'instSemId' => ['required', 'integer', 'min:1'],
            'formPositionId' => ['required', 'integer', 'min:1'],
            'formClassGroupId' => ['required', 'integer', 'min:1'],
            'formSubjectId' => ['required', 'integer', 'min:1'],
            'formStartsOn' => ['required', 'date'],
        ]);

        try {
            app(CreateTeachingAssignment::class)(
                staffPositionId: $this->formPositionId,
                classGroupId: $this->formClassGroupId,
                subjectOfferingId: $this->formSubjectId,
                startsOn: new \DateTime($this->formStartsOn),
                actorRef: 'admin:'.$this->adminId(),
            );
            $this->reset(['showForm', 'formPositionId', 'formClassGroupId', 'formSubjectId']);
            $this->flash('success', 'Teaching assignment created.');
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    // ── End ───────────────────────────────────────────────────────────────────

    public function startEnd(int $id): void
    {
        $this->reset(['replacingId', 'replacePositionId', 'replaceReason']);
        $this->endingId = $id;
        $this->endReason = '';
        $this->endDate = now()->toDateString();
    }

    public function confirmEnd(): void
    {
        $this->requirePermission(PermissionKey::TEACHING_ASSIGNMENT_MANAGE);

        $this->validate([
            'endReason' => ['required', 'string', 'min:5'],
            'endDate' => ['required', 'date'],
        ]);

        try {
            $assignment = TeachingAssignment::findOrFail($this->endingId);
            app(EndTeachingAssignment::class)(
                $assignment,
                new \DateTime($this->endDate),
                $this->endReason,
                'admin:'.$this->adminId(),
            );
            $this->reset(['endingId', 'endReason', 'endDate']);
            $this->flash('success', 'Teaching assignment ended.');
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    public function cancelEnd(): void
    {
        $this->reset(['endingId', 'endReason', 'endDate']);
    }

    // ── Replace ───────────────────────────────────────────────────────────────

    /**
     * Open the replacement form for a given active assignment.
     *
     * Replace atomically supersedes the old assignment (history preserved as
     * 'superseded') and creates a new active assignment for the same class group
     * and subject offering with the newly selected teacher position.
     */
    public function startReplace(int $id): void
    {
        $this->reset(['endingId', 'endReason', 'endDate']);
        $this->replacingId = $id;
        $this->replacePositionId = 0;
        $this->replaceDate = now()->toDateString();
        $this->replaceReason = '';
    }

    public function confirmReplace(): void
    {
        $this->requirePermission(PermissionKey::TEACHING_ASSIGNMENT_MANAGE);

        $this->validate([
            'replacePositionId' => ['required', 'integer', 'min:1'],
            'replaceDate' => ['required', 'date'],
            'replaceReason' => ['required', 'string', 'min:5'],
        ]);

        try {
            $old = TeachingAssignment::findOrFail($this->replacingId);
            app(ReplaceTeachingAssignment::class)(
                old: $old,
                newStaffPositionId: $this->replacePositionId,
                replacedOn: new \DateTime($this->replaceDate),
                reason: $this->replaceReason,
                actorRef: 'admin:'.$this->adminId(),
            );
            $this->reset(['replacingId', 'replacePositionId', 'replaceDate', 'replaceReason']);
            $this->flash('success', 'Teaching assignment replaced — previous record preserved as superseded.');
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    public function cancelReplace(): void
    {
        $this->reset(['replacingId', 'replacePositionId', 'replaceDate', 'replaceReason']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function flash(string $type, string $message): void
    {
        $this->flashType = $type;
        $this->flashMessage = $message;
    }

    public function render(): View
    {
        return view('livewire.admin.assignments.teaching-index', [
            'openSemesters' => $this->openSemesters(),
            'classGroups' => $this->classGroups(),
            'subjectOfferings' => $this->subjectOfferings(),
            'eligiblePositions' => $this->eligiblePositions(),
            'assignments' => $this->assignments(),
            'canManage' => $this->adminCan(PermissionKey::TEACHING_ASSIGNMENT_MANAGE),
        ])->layout('layouts.admin');
    }
}

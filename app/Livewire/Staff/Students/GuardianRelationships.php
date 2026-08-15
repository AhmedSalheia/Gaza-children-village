<?php

declare(strict_types=1);

namespace App\Livewire\Staff\Students;

use App\Livewire\Staff\Concerns\HasStaffAuth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Students\Actions\CreateGuardianStudentRelationship;
use Modules\Students\Actions\EndRelationship;
use Modules\Students\Actions\VerifyRelationship;
use Modules\Students\Enums\LegalAuthorityStatus;
use Modules\Students\Enums\RelationshipType;

/**
 * Guardian relationship management for a single student.
 *
 * Action contracts:
 *   CreateGuardianStudentRelationship(StudentProfile, GuardianProfile,
 *       RelationshipType, LegalAuthorityStatus, startsOn, contactPriority, isEmergencyContact)
 *   VerifyRelationship(GuardianStudentRelationship, string $actorReference, ?string $evidenceReference)
 *   EndRelationship(GuardianStudentRelationship, string $actorReference, ?DateTimeInterface, ?string $reason)
 *
 * $studentProfileId is marked #[Locked] to prevent browser-side modification
 * that could expose another student's relationship data without an action call.
 *
 * assertStudentAccessible() is also re-called on every mutation as a defense-
 * in-depth measure.
 */
final class GuardianRelationships extends Component
{
    use HasStaffAuth;

    /** @var int Route-bound student profile ID; locked against browser mutation. */
    #[Locked]
    public int $studentProfileId;

    // Add form
    public bool $showAddForm = false;

    public int $guardianProfileId = 0;

    public string $relationshipType = '';

    public string $legalAuthority = 'unknown';

    public bool $isEmergencyContact = false;

    // End confirmation
    public ?int $endingRelationshipId = null;

    public string $endReason = '';

    public function mount(int $studentProfileId): void
    {
        $this->requirePermission('guardian_relationship.view');
        $this->studentProfileId = $studentProfileId;
        $this->assertStudentAccessible($this->studentProfileId);
    }

    public function relationships(): Collection
    {
        return DB::table('guardian_student_relationships as gsr')
            ->join('guardian_profiles as gp', 'gp.id', '=', 'gsr.guardian_profile_id')
            ->join('people as p', 'p.id', '=', 'gp.person_id')
            ->where('gsr.student_profile_id', $this->studentProfileId)
            ->select(
                'gsr.id',
                'gsr.relationship_type',
                'gsr.verification_status',
                'gsr.portal_eligible',
                'gsr.legal_authority',
                'gsr.ends_on',
                'p.full_name_ar as guardian_name'
            )
            ->orderBy('gsr.created_at')
            ->get();
    }

    public function addRelationship(CreateGuardianStudentRelationship $action): void
    {
        $this->requirePermission('guardian_relationship.manage');
        $this->assertStudentAccessible($this->studentProfileId);

        $this->validate([
            'guardianProfileId' => ['required', 'integer', 'min:1'],
            'relationshipType' => ['required', 'string'],
            'legalAuthority' => ['required', 'string'],
        ]);

        // Load Eloquent models — actions require model instances, not IDs.
        $studentProfileClass = 'Modules\\Students\\Models\\StudentProfile';
        $guardianProfileClass = 'Modules\\Students\\Models\\GuardianProfile';

        $student = $studentProfileClass::findOrFail($this->studentProfileId);
        $guardian = $guardianProfileClass::findOrFail($this->guardianProfileId);

        try {
            $action(
                $student,
                $guardian,
                RelationshipType::from($this->relationshipType),
                LegalAuthorityStatus::from($this->legalAuthority),
                null,  // startsOn (defaults to today inside action)
                null,  // contactPriority
                $this->isEmergencyContact,
            );

            session()->flash('success', __('ui.relationship_added', [], null, 'Relationship added.'));
            $this->showAddForm = false;
            $this->reset(['guardianProfileId', 'relationshipType', 'isEmergencyContact']);
            $this->legalAuthority = 'unknown';
        } catch (\Throwable $e) {
            $this->addError('addRelationship', $e->getMessage());
        }
    }

    public function verify(int $relationshipId, VerifyRelationship $action): void
    {
        $this->requirePermission('guardian_relationship.verify');
        $this->assertStudentAccessible($this->studentProfileId);

        // Load the relationship model — action requires the model, not an ID.
        $relClass = 'Modules\\Students\\Models\\GuardianStudentRelationship';
        $relationship = $relClass::where('id', $relationshipId)
            ->where('student_profile_id', $this->studentProfileId)
            ->firstOrFail();

        try {
            $action($relationship, $this->staffActorReference());
            session()->flash('success', __('ui.relationship_verified', [], null, 'Relationship verified.'));
        } catch (\Throwable $e) {
            $this->addError('verify', $e->getMessage());
        }
    }

    public function confirmEnd(int $relationshipId): void
    {
        $this->endingRelationshipId = $relationshipId;
        $this->endReason = '';
    }

    public function cancelEnd(): void
    {
        $this->endingRelationshipId = null;
        $this->endReason = '';
    }

    public function endRelationship(EndRelationship $action): void
    {
        $this->requirePermission('guardian_relationship.manage');
        $this->assertStudentAccessible($this->studentProfileId);

        if ($this->endingRelationshipId === null) {
            return;
        }

        $relClass = 'Modules\\Students\\Models\\GuardianStudentRelationship';
        $relationship = $relClass::where('id', $this->endingRelationshipId)
            ->where('student_profile_id', $this->studentProfileId)
            ->firstOrFail();

        try {
            $action(
                $relationship,
                $this->staffActorReference(),
                null,                                  // endsOn → defaults to today
                $this->endReason ?: null,
            );

            session()->flash('success', __('ui.relationship_ended', [], null, 'Relationship ended.'));
            $this->endingRelationshipId = null;
            $this->endReason = '';
        } catch (\Throwable $e) {
            $this->addError('endRelationship', $e->getMessage());
        }
    }

    public function student(): ?object
    {
        return DB::table('student_profiles as sp')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->where('sp.id', $this->studentProfileId)
            ->select('sp.id', 'p.full_name_ar')
            ->first();
    }

    public function render(): View
    {
        return view('livewire.staff.students.guardian-relationships', [
            'student' => $this->student(),
            'relationships' => $this->relationships(),
            'relationshipTypes' => RelationshipType::cases(),
            'legalAuthorityOptions' => LegalAuthorityStatus::cases(),
            'canManage' => $this->staffCan('guardian_relationship.manage'),
            'canVerify' => $this->staffCan('guardian_relationship.verify'),
        ])->layout('layouts.staff');
    }
}

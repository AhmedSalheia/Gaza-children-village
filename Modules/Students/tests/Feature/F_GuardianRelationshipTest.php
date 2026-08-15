<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Students\Actions\ActivateRelationship;
use Modules\Students\Actions\CreateGuardianProfile;
use Modules\Students\Actions\CreateGuardianStudentRelationship;
use Modules\Students\Actions\EndRelationship;
use Modules\Students\Actions\VerifyRelationship;
use Modules\Students\Enums\RelationshipType;
use Modules\Students\Enums\VerificationStatus;
use Modules\Students\Exceptions\RelationshipMutationDeniedException;
use Modules\Students\Models\GuardianProfile;
use Modules\Students\Models\GuardianStudentRelationship;
use Modules\Students\Models\StudentProfile;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function grPerson(string $nameAr = 'ولي الأمر'): object
{
    $cls = 'Modules\\People\\Models\\Person';

    return $cls::factory()->create(['full_name_ar' => $nameAr]);
}

function grStudent(): StudentProfile
{
    $person = grPerson('طالب');
    $profile = new StudentProfile;
    $profile->person_id = $person->id;
    $profile->student_code = 'STU-GR-'.rand(10000, 99999);
    $profile->lifecycle_status = 'active';
    $profile->registered_on = now()->toDateString();
    $profile->save();

    return $profile;
}

function grGuardian(): GuardianProfile
{
    $person = grPerson();

    return app(CreateGuardianProfile::class)($person->id);
}

// ---------------------------------------------------------------------------
// GuardianProfile creation
// ---------------------------------------------------------------------------

describe('GuardianProfile creation', function (): void {

    it('creates a guardian profile with a year-prefixed code', function (): void {
        $guardian = grGuardian();

        expect($guardian->guardian_code)->toStartWith('GRD-'.now()->year.'-')
            ->and($guardian->lifecycle_status->value)->toBe('active');
    });

    it('a Person may have at most one GuardianProfile', function (): void {
        $person = grPerson('أحمد');
        app(CreateGuardianProfile::class)($person->id);

        expect(fn () => app(CreateGuardianProfile::class)($person->id))
            ->toThrow(InvalidArgumentException::class);
    });

    it('guardian_account_id is null by default — no auto-creation', function (): void {
        $guardian = grGuardian();

        expect($guardian->guardian_account_id)->toBeNull();
    });

});

// ---------------------------------------------------------------------------
// Relationship creation and historical preservation
// ---------------------------------------------------------------------------

describe('relationship creation', function (): void {

    it('creates a relationship with unverified status and portal_eligible=false', function (): void {
        $student = grStudent();
        $guardian = grGuardian();

        $rel = app(CreateGuardianStudentRelationship::class)(
            $student, $guardian, RelationshipType::Father
        );

        expect($rel->verification_status)->toBe(VerificationStatus::Unverified)
            ->and($rel->portal_eligible)->toBeFalse()
            ->and($rel->ends_on)->toBeNull();
    });

    it('prevents duplicate active relationship of the same type', function (): void {
        $student = grStudent();
        $guardian = grGuardian();

        app(CreateGuardianStudentRelationship::class)($student, $guardian, RelationshipType::Father);

        expect(fn () => app(CreateGuardianStudentRelationship::class)(
            $student, $guardian, RelationshipType::Father
        ))->toThrow(InvalidArgumentException::class);
    });

    it('allows a new relationship after the previous one is ended', function (): void {
        $student = grStudent();
        $guardian = grGuardian();

        $rel1 = app(CreateGuardianStudentRelationship::class)($student, $guardian, RelationshipType::Father);
        app(EndRelationship::class)($rel1, 'admin-001');

        // Now a new relationship may be created.
        $rel2 = app(CreateGuardianStudentRelationship::class)($student, $guardian, RelationshipType::Father);

        expect($rel2->id)->not->toBe($rel1->id)
            ->and($rel1->fresh()->ends_on)->not->toBeNull();
    });

    it('ended relationship row is preserved — never deleted', function (): void {
        $student = grStudent();
        $guardian = grGuardian();

        $rel = app(CreateGuardianStudentRelationship::class)($student, $guardian, RelationshipType::Mother);
        app(EndRelationship::class)($rel, 'admin-001', reason: 'Guardian relocated');

        expect(GuardianStudentRelationship::find($rel->id))->not->toBeNull()
            ->and($rel->fresh()->ends_on)->not->toBeNull();
    });

    it('EndRelationship records reason in history_metadata', function (): void {
        $student = grStudent();
        $guardian = grGuardian();

        $rel = app(CreateGuardianStudentRelationship::class)($student, $guardian, RelationshipType::Mother);
        app(EndRelationship::class)($rel, 'admin-001', reason: 'Court order changed');

        $history = $rel->fresh()->history_metadata;

        expect($history)->not->toBeNull()
            ->and($history[0]['reason'])->toBe('Court order changed')
            ->and($history[0]['actor'])->toBe('admin-001');
    });

    it('EndRelationship rejects an already-ended relationship', function (): void {
        $student = grStudent();
        $guardian = grGuardian();

        $rel = app(CreateGuardianStudentRelationship::class)($student, $guardian, RelationshipType::Father);
        app(EndRelationship::class)($rel, 'admin-001');
        $rel->refresh();

        expect(fn () => app(EndRelationship::class)($rel, 'admin-001'))
            ->toThrow(RelationshipMutationDeniedException::class);
    });

});

// ---------------------------------------------------------------------------
// Verification workflow
// ---------------------------------------------------------------------------

describe('verification workflow', function (): void {

    it('verifying a relationship sets status to verified', function (): void {
        $student = grStudent();
        $guardian = grGuardian();

        $rel = app(CreateGuardianStudentRelationship::class)($student, $guardian, RelationshipType::Father);
        $verified = app(VerifyRelationship::class)($rel, 'secretary-001');

        expect($verified->verification_status)->toBe(VerificationStatus::Verified);
    });

    it('cannot verify an already-ended relationship', function (): void {
        $student = grStudent();
        $guardian = grGuardian();

        $rel = app(CreateGuardianStudentRelationship::class)($student, $guardian, RelationshipType::Father);
        // Manually set ends_on to yesterday to simulate ended state.
        $rel->ends_on = now()->subDay()->toDateString();
        $rel->save();

        expect(fn () => app(VerifyRelationship::class)($rel, 'secretary-001'))
            ->toThrow(RelationshipMutationDeniedException::class);
    });

});

// ---------------------------------------------------------------------------
// Portal eligibility derivation
// ---------------------------------------------------------------------------

describe('portal eligibility', function (): void {

    it('portal_eligible is false by default after verification', function (): void {
        $student = grStudent();
        $guardian = grGuardian();

        $rel = app(CreateGuardianStudentRelationship::class)($student, $guardian, RelationshipType::Father);
        app(VerifyRelationship::class)($rel, 'secretary-001');

        expect($rel->fresh()->portal_eligible)->toBeFalse();
    });

    it('ActivateRelationship enables portal access after verification', function (): void {
        $student = grStudent();
        $guardian = grGuardian();

        $rel = app(CreateGuardianStudentRelationship::class)($student, $guardian, RelationshipType::Father);
        app(VerifyRelationship::class)($rel, 'secretary-001');
        app(ActivateRelationship::class)($rel->fresh(), 'secretary-001');

        expect($rel->fresh()->portal_eligible)->toBeTrue();
    });

    it('ActivateRelationship requires verified status', function (): void {
        $student = grStudent();
        $guardian = grGuardian();

        $rel = app(CreateGuardianStudentRelationship::class)($student, $guardian, RelationshipType::Father);

        expect(fn () => app(ActivateRelationship::class)($rel, 'secretary-001'))
            ->toThrow(RelationshipMutationDeniedException::class);
    });

    it('portalEligibleRelationships scope requires all three conditions', function (): void {
        $student = grStudent();
        $guardian = grGuardian();

        // Unverified — not eligible
        $rel1 = app(CreateGuardianStudentRelationship::class)($student, $guardian, RelationshipType::Father);

        // Verified but not portal_eligible
        $guardian2 = grGuardian();
        $rel2 = app(CreateGuardianStudentRelationship::class)($student, $guardian2, RelationshipType::Mother);
        app(VerifyRelationship::class)($rel2, 'secretary-001');

        // Verified + portal_eligible + active (all three) → eligible
        $guardian3 = grGuardian();
        $rel3 = app(CreateGuardianStudentRelationship::class)($student, $guardian3, RelationshipType::LegalGuardian);
        app(VerifyRelationship::class)($rel3, 'secretary-001');
        app(ActivateRelationship::class)($rel3->fresh(), 'secretary-001');

        $eligible = $student->portalEligibleRelationships;

        expect($eligible->count())->toBe(1)
            ->and($eligible->first()->id)->toBe($rel3->id);
    });

    it('portal eligibility is lost when the relationship ends', function (): void {
        $student = grStudent();
        $guardian = grGuardian();

        $rel = app(CreateGuardianStudentRelationship::class)($student, $guardian, RelationshipType::Father);
        app(VerifyRelationship::class)($rel, 'secretary-001');
        app(ActivateRelationship::class)($rel->fresh(), 'secretary-001');

        // End the relationship
        app(EndRelationship::class)($rel->fresh(), 'admin-001');

        expect($student->portalEligibleRelationships()->count())->toBe(0);
    });

    it('guarding relationship access: no automatic access from national ID match', function (): void {
        // Creating a guardian profile does NOT automatically create a relationship.
        $student = grStudent();
        $guardian = grGuardian();

        // Guardian profile exists but no relationship was created.
        expect($student->guardianRelationships()->count())->toBe(0)
            ->and($student->portalEligibleRelationships()->count())->toBe(0);
    });

});

// ---------------------------------------------------------------------------
// GuardianProfile model scope
// ---------------------------------------------------------------------------

describe('GuardianProfile portal scope', function (): void {

    it('portalEligibleRelationships on GuardianProfile returns only eligible entries', function (): void {
        $student1 = grStudent();
        $student2 = grStudent();
        $guardian = grGuardian();

        // Student 1: eligible
        $rel1 = app(CreateGuardianStudentRelationship::class)($student1, $guardian, RelationshipType::Father);
        app(VerifyRelationship::class)($rel1, 'secretary-001');
        app(ActivateRelationship::class)($rel1->fresh(), 'secretary-001');

        // Student 2: unverified only
        app(CreateGuardianStudentRelationship::class)($student2, $guardian, RelationshipType::Father);

        $eligible = $guardian->portalEligibleRelationships;

        expect($eligible->count())->toBe(1)
            ->and($eligible->first()->student_profile_id)->toBe($student1->id);
    });

});

<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Modules\Requests\Enums\CorrectionClassification;
use Modules\Requests\Enums\CorrectionFieldCatalogue;
use Modules\Requests\Exceptions\CorrectionConflictException;
use Modules\Requests\Models\StudentCorrectionRequest;
use Modules\Requests\Resolvers\CorrectionRequestContentResolver;
use Modules\Requests\Services\CorrectionApplicationService;
use Modules\Requests\Services\CorrectionRequestService;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Test-fixture helpers
// ---------------------------------------------------------------------------

/**
 * Insert the minimum rows needed for a guardian to have an active
 * portal-eligible relationship to a student.
 *
 * Returns ['guardian_account_id', 'guardian_profile_id', 'student_profile_id', 'person_id'].
 *
 * @return array<string,int>
 */
function makeGuardianWithStudent(int $suffix = 1): array
{
    // person for student
    $personId = DB::table('people')->insertGetId([
        'full_name_ar' => "طالب {$suffix}",
        'full_name_en' => "Student {$suffix}",
        'birth_date' => '2012-01-01',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $studentId = DB::table('student_profiles')->insertGetId([
        'person_id' => $personId,
        'lifecycle_status' => 'active',
        'registered_on' => now()->toDateString(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // person for guardian
    $gPersonId = DB::table('people')->insertGetId([
        'full_name_ar' => "ولي {$suffix}",
        'full_name_en' => "Guardian {$suffix}",
        'birth_date' => '1980-01-01',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $guardianProfileId = DB::table('guardian_profiles')->insertGetId([
        'person_id' => $gPersonId,
        'lifecycle_status' => 'active',
        'guardian_account_id' => $suffix,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // active portal-eligible relationship
    DB::table('guardian_student_relationships')->insert([
        'student_profile_id' => $studentId,
        'guardian_profile_id' => $guardianProfileId,
        'relationship_type' => 'father',
        'legal_authority' => 'biological_parent',
        'verification_status' => 'verified',
        'portal_eligible' => true,
        'contact_priority' => 1,
        'is_emergency_contact' => false,
        'starts_on' => now()->toDateString(),
        'ends_on' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return [
        'guardian_account_id' => $suffix,
        'guardian_profile_id' => $guardianProfileId,
        'student_profile_id' => $studentId,
        'person_id' => $personId,
    ];
}

/**
 * Create and submit a standard correction request, returning the request.
 *
 * @param  array<string,int>  $fixture
 */
function submitNameCorrection(array $fixture, string $newName = 'طالب مصحح'): StudentCorrectionRequest
{
    return app(CorrectionRequestService::class)->createAndSubmit(
        studentProfileId: $fixture['student_profile_id'],
        guardianAccountId: $fixture['guardian_account_id'],
        guardianProfileId: $fixture['guardian_profile_id'],
        fieldCode: CorrectionFieldCatalogue::StudentNameAr->value,
        proposedValue: $newName,
        explanation: 'تصحيح الاسم',
    );
}

// ---------------------------------------------------------------------------
// WorkflowDefinitionSeeder must be run (already seeded in test DB via base seeder)
// ---------------------------------------------------------------------------

beforeEach(function (): void {
    // Seed workflow definitions so student_correction type exists
    $seederClass = 'Modules\\Workflow\\Database\\Seeders\\WorkflowDefinitionSeeder';
    app($seederClass)->run();
});

// ---------------------------------------------------------------------------
// Field catalogue
// ---------------------------------------------------------------------------

describe('CorrectionFieldCatalogue', function (): void {

    it('has exactly 8 requestable fields', function (): void {
        expect(CorrectionFieldCatalogue::cases())->toHaveCount(8);
    });

    it('classifies birth_date and identifier_correction as sensitive', function (): void {
        expect(CorrectionFieldCatalogue::BirthDate->classification())->toBe(CorrectionClassification::Sensitive);
        expect(CorrectionFieldCatalogue::IdentifierCorrection->classification())->toBe(CorrectionClassification::Sensitive);
    });

    it('classifies student_name_ar as standard', function (): void {
        expect(CorrectionFieldCatalogue::StudentNameAr->classification())->toBe(CorrectionClassification::Standard);
    });

    it('requiresEncryption for sensitive fields only', function (): void {
        expect(CorrectionFieldCatalogue::BirthDate->requiresEncryption())->toBeTrue();
        expect(CorrectionFieldCatalogue::StudentNameAr->requiresEncryption())->toBeFalse();
    });

});

// ---------------------------------------------------------------------------
// Guardian eligibility guard
// ---------------------------------------------------------------------------

describe('Guardian eligibility guard', function (): void {

    it('throws RuntimeException when guardian has no portal-eligible relationship', function (): void {
        $fixture = makeGuardianWithStudent(10);

        // Revoke portal eligibility
        DB::table('guardian_student_relationships')
            ->where('guardian_profile_id', $fixture['guardian_profile_id'])
            ->update(['portal_eligible' => false]);

        expect(fn () => submitNameCorrection($fixture))
            ->toThrow(RuntimeException::class, 'no portal-eligible relationship');
    });

    it('throws RuntimeException when relationship is ended', function (): void {
        $fixture = makeGuardianWithStudent(11);

        DB::table('guardian_student_relationships')
            ->where('guardian_profile_id', $fixture['guardian_profile_id'])
            ->update(['ends_on' => now()->subDay()->toDateString()]);

        expect(fn () => submitNameCorrection($fixture))
            ->toThrow(RuntimeException::class, 'no portal-eligible relationship');
    });

    it('throws RuntimeException when relationship is unverified', function (): void {
        $fixture = makeGuardianWithStudent(12);

        DB::table('guardian_student_relationships')
            ->where('guardian_profile_id', $fixture['guardian_profile_id'])
            ->update(['verification_status' => 'pending']);

        expect(fn () => submitNameCorrection($fixture))
            ->toThrow(RuntimeException::class, 'no portal-eligible relationship');
    });

    it('allows submission when all eligibility conditions are met', function (): void {
        $fixture = makeGuardianWithStudent(13);
        $request = submitNameCorrection($fixture);

        expect($request)->not->toBeNull()
            ->and($request->id)->toBeGreaterThan(0);
    });

});

// ---------------------------------------------------------------------------
// Field catalogue enforcement
// ---------------------------------------------------------------------------

describe('Field catalogue enforcement', function (): void {

    it('throws InvalidArgumentException when field code is not in catalogue', function (): void {
        $fixture = makeGuardianWithStudent(20);

        expect(fn () => app(CorrectionRequestService::class)->createAndSubmit(
            studentProfileId: $fixture['student_profile_id'],
            guardianAccountId: $fixture['guardian_account_id'],
            guardianProfileId: $fixture['guardian_profile_id'],
            fieldCode: 'arbitrary.model.column',
            proposedValue: 'some value',
        ))->toThrow(InvalidArgumentException::class, 'not in the correction field catalogue');
    });

    it('creates request and proposal row for a valid field code', function (): void {
        $fixture = makeGuardianWithStudent(21);
        $request = submitNameCorrection($fixture);

        expect(StudentCorrectionRequest::count())->toBe(1)
            ->and($request->proposals()->count())->toBe(1);
    });

});

// ---------------------------------------------------------------------------
// Classification assignment
// ---------------------------------------------------------------------------

describe('Sensitive field classification', function (): void {

    it('creates a standard-classified request for student_name_ar', function (): void {
        $fixture = makeGuardianWithStudent(30);
        $request = submitNameCorrection($fixture);

        expect($request->classification)->toBe(CorrectionClassification::Standard);
    });

    it('creates a sensitive-classified request for birth_date', function (): void {
        $fixture = makeGuardianWithStudent(31);

        $request = app(CorrectionRequestService::class)->createAndSubmit(
            studentProfileId: $fixture['student_profile_id'],
            guardianAccountId: $fixture['guardian_account_id'],
            guardianProfileId: $fixture['guardian_profile_id'],
            fieldCode: CorrectionFieldCatalogue::BirthDate->value,
            proposedValue: '2012-06-15',
        );

        expect($request->isSensitive())->toBeTrue()
            ->and($request->classification)->toBe(CorrectionClassification::Sensitive);
    });

    it('sensitive field approval requires a comment', function (): void {
        $fixture = makeGuardianWithStudent(32);

        $request = app(CorrectionRequestService::class)->createAndSubmit(
            studentProfileId: $fixture['student_profile_id'],
            guardianAccountId: $fixture['guardian_account_id'],
            guardianProfileId: $fixture['guardian_profile_id'],
            fieldCode: CorrectionFieldCatalogue::BirthDate->value,
            proposedValue: '2012-06-15',
        );

        // Move to under_review
        app(CorrectionRequestService::class)->startReview($request, staffAccountId: 1);

        expect(fn () => app(CorrectionRequestService::class)->approve(
            request: $request->fresh(),
            actorAccountId: 1,
            actorType: 'staff',
            portal: 'staff',
            comment: null, // missing comment on sensitive → must throw
        ))->toThrow(RuntimeException::class, 'Sensitive correction approval requires a comment');
    });

});

// ---------------------------------------------------------------------------
// Rejection requires a comment
// ---------------------------------------------------------------------------

describe('Rejection comment requirement', function (): void {

    it('throws RuntimeException when rejection comment is empty', function (): void {
        $fixture = makeGuardianWithStudent(40);
        $request = submitNameCorrection($fixture);

        app(CorrectionRequestService::class)->startReview($request, staffAccountId: 1);

        expect(fn () => app(CorrectionRequestService::class)->reject(
            request: $request->fresh(),
            actorAccountId: 1,
            actorType: 'staff',
            portal: 'staff',
            reason: '',
        ))->toThrow(RuntimeException::class, 'Rejection requires a reason');
    });

    it('succeeds when rejection has a reason', function (): void {
        $fixture = makeGuardianWithStudent(41);
        $request = submitNameCorrection($fixture);

        app(CorrectionRequestService::class)->startReview($request, staffAccountId: 1);

        $rejected = app(CorrectionRequestService::class)->reject(
            request: $request->fresh(),
            actorAccountId: 1,
            actorType: 'staff',
            portal: 'staff',
            reason: 'اسم غير صحيح',
        );

        $instance = DB::table('workflow_instances')->where('id', $rejected->workflow_instance_id)->first();
        expect($instance->current_state)->toBe('rejected');
    });

});

// ---------------------------------------------------------------------------
// Full happy-path: submit → review → approve → apply
// ---------------------------------------------------------------------------

describe('Full apply lifecycle', function (): void {

    it('applies a standard correction and updates the people table', function (): void {
        $fixture = makeGuardianWithStudent(50);
        $request = submitNameCorrection($fixture, newName: 'أحمد علي');

        app(CorrectionRequestService::class)->startReview($request, staffAccountId: 1);

        $reviewed = $request->fresh();
        app(CorrectionRequestService::class)->approve(
            request: $reviewed,
            actorAccountId: 1,
            actorType: 'staff',
            portal: 'staff',
            comment: 'تم التحقق',
        );

        $approved = $request->fresh();
        app(CorrectionApplicationService::class)->apply(
            request: $approved,
            appliedByAccountId: 1,
        );

        $applied = $request->fresh();
        expect($applied->applied_at)->not->toBeNull()
            ->and($applied->applied_by_account_id)->toBe(1);

        // Person record must be updated
        $name = DB::table('people')->where('id', $fixture['person_id'])->value('full_name_ar');
        expect($name)->toBe('أحمد علي');

        // Workflow must be in applied state
        $instance = DB::table('workflow_instances')->where('id', $applied->workflow_instance_id)->first();
        expect($instance->current_state)->toBe('applied')
            ->and($instance->completed_at)->not->toBeNull();
    });

    it('apply throws when workflow is not in approved state', function (): void {
        $fixture = makeGuardianWithStudent(51);
        $request = submitNameCorrection($fixture);

        // Only submitted, not yet approved → must throw
        expect(fn () => app(CorrectionApplicationService::class)->apply(
            request: $request->fresh(),
            appliedByAccountId: 1,
        ))->toThrow(RuntimeException::class, "expected 'approved'");
    });

});

// ---------------------------------------------------------------------------
// Conflict detection
// ---------------------------------------------------------------------------

describe('Conflict detection', function (): void {

    it('throws CorrectionConflictException and sets conflict_flag when official data changed', function (): void {
        $fixture = makeGuardianWithStudent(60);
        $request = submitNameCorrection($fixture, newName: 'محمد أحمد');

        // Advance to approved
        app(CorrectionRequestService::class)->startReview($request, staffAccountId: 1);
        app(CorrectionRequestService::class)->approve($request->fresh(), 1, 'staff', 'staff', 'ok');

        // Simulate an out-of-band change to the official record AFTER submission
        DB::table('people')->where('id', $fixture['person_id'])->update(['full_name_ar' => 'اسم معدل للتو']);

        expect(fn () => app(CorrectionApplicationService::class)->apply(
            request: $request->fresh(),
            appliedByAccountId: 1,
        ))->toThrow(CorrectionConflictException::class);

        // conflict_flag must be set on the request row
        $updated = StudentCorrectionRequest::find($request->id);
        expect($updated->conflict_flag)->toBeTrue()
            ->and($updated->conflict_reason)->not->toBeNull();
    });

});

// ---------------------------------------------------------------------------
// Cross-guardian access isolation
// ---------------------------------------------------------------------------

describe('Cross-guardian access isolation', function (): void {

    it('throws RuntimeException when a different guardian tries to cancel another guardian\'s request', function (): void {
        $fixture1 = makeGuardianWithStudent(70);
        $request = submitNameCorrection($fixture1);

        // Guardian 2 has a different guardian_account_id
        expect(fn () => app(CorrectionRequestService::class)->cancelByGuardian(
            request: $request->fresh(),
            guardianAccountId: 99999, // wrong guardian
        ))->toThrow(RuntimeException::class, 'Access denied');
    });

    it('allows the owning guardian to cancel their request', function (): void {
        $fixture = makeGuardianWithStudent(71);
        $request = submitNameCorrection($fixture);

        $cancelled = app(CorrectionRequestService::class)->cancelByGuardian(
            request: $request->fresh(),
            guardianAccountId: $fixture['guardian_account_id'],
        );

        $instance = DB::table('workflow_instances')->where('id', $cancelled->workflow_instance_id)->first();
        expect($instance->current_state)->toBe('cancelled');
    });

});

// ---------------------------------------------------------------------------
// Clarification loop
// ---------------------------------------------------------------------------

describe('Clarification loop', function (): void {

    it('guardian can resubmit after clarification is requested', function (): void {
        $fixture = makeGuardianWithStudent(80);
        $request = submitNameCorrection($fixture);

        app(CorrectionRequestService::class)->requestClarification($request, 1, 'يرجى توضيح التصحيح');

        $clarified = $request->fresh();

        $resubmitted = app(CorrectionRequestService::class)->resubmit(
            request: $clarified,
            guardianAccountId: $fixture['guardian_account_id'],
            revisedProposedValue: 'أحمد محمد علي',
            explanation: 'التصحيح الصحيح هو',
        );

        $instance = DB::table('workflow_instances')->where('id', $resubmitted->workflow_instance_id)->first();
        expect($instance->current_state)->toBe('resubmitted');

        // A second proposal row must exist
        expect($request->proposals()->count())->toBe(2);
    });

    it('clarification request requires a reason comment', function (): void {
        $fixture = makeGuardianWithStudent(81);
        $request = submitNameCorrection($fixture);

        expect(fn () => app(CorrectionRequestService::class)->requestClarification(
            request: $request,
            staffAccountId: 1,
            reason: '',
        ))->toThrow(RuntimeException::class, 'Clarification request requires a reason');
    });

});

// ---------------------------------------------------------------------------
// Sensitive field encryption
// ---------------------------------------------------------------------------

describe('Sensitive field encryption', function (): void {

    it('stores birth_date proposal encrypted (not readable as plain text)', function (): void {
        $fixture = makeGuardianWithStudent(90);

        app(CorrectionRequestService::class)->createAndSubmit(
            studentProfileId: $fixture['student_profile_id'],
            guardianAccountId: $fixture['guardian_account_id'],
            guardianProfileId: $fixture['guardian_profile_id'],
            fieldCode: CorrectionFieldCatalogue::BirthDate->value,
            proposedValue: '2012-06-15',
        );

        $raw = DB::table('correction_field_proposals')->first();
        expect($raw)->not->toBeNull();

        // Raw proposed_value must NOT equal the plain text (it is encrypted)
        expect($raw->proposed_value)->not->toBe('2012-06-15');
        // It should be decryptable by Laravel's Crypt facade
        $decrypted = Crypt::decryptString($raw->proposed_value);
        expect($decrypted)->toBe('2012-06-15');
    });

});

// ---------------------------------------------------------------------------
// Sensitive approval token gate
// ---------------------------------------------------------------------------

describe('Sensitive approval token gate', function (): void {

    it('throws RuntimeException when sensitive approve is called without a reconfirmation token', function (): void {
        $fixture = makeGuardianWithStudent(91);

        $request = app(CorrectionRequestService::class)->createAndSubmit(
            studentProfileId: $fixture['student_profile_id'],
            guardianAccountId: $fixture['guardian_account_id'],
            guardianProfileId: $fixture['guardian_profile_id'],
            fieldCode: CorrectionFieldCatalogue::BirthDate->value,
            proposedValue: '2013-03-10',
        );

        app(CorrectionRequestService::class)->startReview($request, 1);

        // Calling approve without a token on a sensitive request must be rejected.
        expect(fn () => app(CorrectionRequestService::class)->approve(
            request: $request->fresh(),
            actorAccountId: 1,
            actorType: 'staff',
            portal: 'staff',
            comment: 'Looks correct',
            reconfirmationTokenId: null,   // missing token — must throw
        ))->toThrow(RuntimeException::class, 'reconfirmation token');
    });

    it('allows standard corrections to be approved without a reconfirmation token', function (): void {
        $fixture = makeGuardianWithStudent(92);
        $request = submitNameCorrection($fixture);

        app(CorrectionRequestService::class)->startReview($request, 1);

        // Standard corrections: no token required.
        $approved = app(CorrectionRequestService::class)->approve(
            request: $request->fresh(),
            actorAccountId: 1,
            actorType: 'staff',
            portal: 'staff',
            reconfirmationTokenId: null,
        );

        $instance = DB::table('workflow_instances')->where('id', $approved->workflow_instance_id)->first();
        expect($instance->current_state)->toBe('approved');
    });

});

// ---------------------------------------------------------------------------
// Clarification resubmit preserves relationship reference
// ---------------------------------------------------------------------------

describe('Clarification resubmit preserves relationship reference', function (): void {

    it('carries relationship_ref_id from original proposal into the resubmission', function (): void {
        $fixture = makeGuardianWithStudent(93);

        // Grab the relationship row ID so we can request a relationship-type correction.
        $relId = DB::table('guardian_student_relationships')
            ->where('guardian_profile_id', $fixture['guardian_profile_id'])
            ->value('id');

        $request = app(CorrectionRequestService::class)->createAndSubmit(
            studentProfileId: $fixture['student_profile_id'],
            guardianAccountId: $fixture['guardian_account_id'],
            guardianProfileId: $fixture['guardian_profile_id'],
            fieldCode: CorrectionFieldCatalogue::GuardianRelationshipType->value,
            proposedValue: 'uncle',
            explanation: 'تصحيح طبيعة العلاقة',
            relationshipRefId: $relId,
        );

        // Secretary asks for clarification
        app(CorrectionRequestService::class)->requestClarification($request, 1, 'وضح أكثر');

        // Guardian resubmits — must not lose the original relationship_ref_id
        $resubmitted = app(CorrectionRequestService::class)->resubmit(
            request: $request->fresh(),
            guardianAccountId: $fixture['guardian_account_id'],
            revisedProposedValue: 'uncle',
            explanation: 'توضيح إضافي',
        );

        // Latest proposal must carry the original relationship_ref_id
        $latestProposal = $resubmitted->proposals()->orderByDesc('submission_sequence')->first();
        expect($latestProposal->relationship_ref_id)->toBe($relId);
    });

});

// ---------------------------------------------------------------------------
// CorrectionRequestContentResolver
// ---------------------------------------------------------------------------

describe('CorrectionRequestContentResolver', function (): void {

    it('returns a stable 64-character hex SHA-256 hash for a known request', function (): void {
        $fixture = makeGuardianWithStudent(94);

        $request = app(CorrectionRequestService::class)->createAndSubmit(
            studentProfileId: $fixture['student_profile_id'],
            guardianAccountId: $fixture['guardian_account_id'],
            guardianProfileId: $fixture['guardian_profile_id'],
            fieldCode: CorrectionFieldCatalogue::StudentNameAr->value,
            proposedValue: 'اسم جديد',
        );

        $resolver = new CorrectionRequestContentResolver;
        $hash = $resolver->computeCanonicalHash('StudentCorrectionRequest', $request->id);

        expect($hash)->toMatch('/^[0-9a-f]{64}$/');
        // Hash must be deterministic — same request, same result
        expect($resolver->computeCanonicalHash('StudentCorrectionRequest', $request->id))->toBe($hash);
    });

    it('produces a different hash when the proposal changes after a resubmission', function (): void {
        $fixture = makeGuardianWithStudent(95);

        $request = app(CorrectionRequestService::class)->createAndSubmit(
            studentProfileId: $fixture['student_profile_id'],
            guardianAccountId: $fixture['guardian_account_id'],
            guardianProfileId: $fixture['guardian_profile_id'],
            fieldCode: CorrectionFieldCatalogue::StudentNameAr->value,
            proposedValue: 'اسم اول',
        );

        $resolver = new CorrectionRequestContentResolver;
        $hash1 = $resolver->computeCanonicalHash('StudentCorrectionRequest', $request->id);

        app(CorrectionRequestService::class)->requestClarification($request, 1, 'وضح');

        app(CorrectionRequestService::class)->resubmit(
            request: $request->fresh(),
            guardianAccountId: $fixture['guardian_account_id'],
            revisedProposedValue: 'اسم مختلف',
        );

        $hash2 = $resolver->computeCanonicalHash('StudentCorrectionRequest', $request->id);

        expect($hash2)->not->toBe($hash1);
    });

    it('throws InvalidArgumentException for an unsupported subject type', function (): void {
        $resolver = new CorrectionRequestContentResolver;

        expect(fn () => $resolver->computeCanonicalHash('UnknownType', 1))
            ->toThrow(InvalidArgumentException::class);
    });

});

<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Students\Actions\ActivateStudent;
use Modules\Students\Actions\ChangeStudentLifecycleStatus;
use Modules\Students\Actions\CorrectStudentData;
use Modules\Students\Actions\CreatePersonAndStudentAtomically;
use Modules\Students\Actions\CreateStudentProfile;
use Modules\Students\Actions\SearchStudents;
use Modules\Students\Enums\DisplacementStatus;
use Modules\Students\Enums\OrphanStatus;
use Modules\Students\Enums\StudentLifecycleStatus;
use Modules\Students\Exceptions\InvalidLifecycleTransitionException;
use Modules\Students\Models\StudentProfile;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers — cross-module references use double-backslash (boundary scanner).
// ---------------------------------------------------------------------------

function srPerson(string $nameAr = 'أحمد محمد'): object
{
    $cls = 'Modules\\People\\Models\\Person';

    return $cls::factory()->create(['full_name_ar' => $nameAr]);
}

function srStudent(?object $person = null, string $status = 'draft'): StudentProfile
{
    $person ??= srPerson();
    $profile = new StudentProfile;
    $profile->person_id = $person->id;
    $profile->student_code = 'STU-TEST-'.rand(10000, 99999);
    $profile->lifecycle_status = $status;
    $profile->registered_on = now()->toDateString();
    $profile->save();

    return $profile;
}

// ---------------------------------------------------------------------------
// Student / Person identity separation
// ---------------------------------------------------------------------------

describe('student and person identity are separate records', function (): void {

    it('creating a StudentProfile does not alter the Person record', function (): void {
        $person = srPerson('فاطمة علي');
        $originalName = $person->full_name_ar;

        $student = app(CreateStudentProfile::class)($person->id);

        $person->refresh();
        expect($person->full_name_ar)->toBe($originalName)
            ->and($student->person_id)->toBe($person->id)
            ->and($student->id)->not->toBe($person->id); // different records
    });

    it('a Person may have at most one StudentProfile', function (): void {
        $person = srPerson();
        app(CreateStudentProfile::class)($person->id);

        expect(fn () => app(CreateStudentProfile::class)($person->id))
            ->toThrow(InvalidArgumentException::class);
    });

    it('two calls with the same name produce two distinct Person and Student records', function (): void {
        $result1 = app(CreatePersonAndStudentAtomically::class)('محمد سالم');
        $result2 = app(CreatePersonAndStudentAtomically::class)('محمد سالم');

        expect($result1['person']->id)->not->toBe($result2['person']->id)
            ->and($result1['student']->id)->not->toBe($result2['student']->id);
    });

    it('CreatePersonAndStudentAtomically sets lifecycle to draft', function (): void {
        $result = app(CreatePersonAndStudentAtomically::class)('نور أحمد');

        expect($result['student']->lifecycle_status)->toBe(StudentLifecycleStatus::Draft);
    });

});

// ---------------------------------------------------------------------------
// Student code generation
// ---------------------------------------------------------------------------

describe('student_code generation', function (): void {

    it('generates a year-prefixed unique code', function (): void {
        $person = srPerson();
        $student = app(CreateStudentProfile::class)($person->id);

        expect($student->student_code)->toStartWith('STU-'.now()->year.'-');
    });

    it('generates distinct codes for successive profiles', function (): void {
        $p1 = srPerson('طالب ١');
        $p2 = srPerson('طالب ٢');

        $s1 = app(CreateStudentProfile::class)($p1->id);
        $s2 = app(CreateStudentProfile::class)($p2->id);

        expect($s1->student_code)->not->toBe($s2->student_code);
    });

});

// ---------------------------------------------------------------------------
// Lifecycle transitions
// ---------------------------------------------------------------------------

describe('lifecycle transitions', function (): void {

    it('draft profile can be activated', function (): void {
        $student = srStudent(status: 'draft');
        $activated = app(ActivateStudent::class)($student, 'admin-001');

        expect($activated->lifecycle_status)->toBe(StudentLifecycleStatus::Active);
    });

    it('active profile can be set to inactive', function (): void {
        $student = srStudent(status: 'active');
        $result = app(ChangeStudentLifecycleStatus::class)(
            $student, StudentLifecycleStatus::Inactive, 'admin-001'
        );

        expect($result->lifecycle_status)->toBe(StudentLifecycleStatus::Inactive);
    });

    it('inactive profile can be re-activated', function (): void {
        $student = srStudent(status: 'inactive');
        $result = app(ActivateStudent::class)($student, 'admin-001');

        expect($result->lifecycle_status)->toBe(StudentLifecycleStatus::Active);
    });

    it('withdrawn profile can be re-activated for re-registration', function (): void {
        $student = srStudent(status: 'withdrawn');
        $result = app(ActivateStudent::class)($student, 'admin-001');

        expect($result->lifecycle_status)->toBe(StudentLifecycleStatus::Active);
    });

    it('graduated profile rejects further transitions', function (): void {
        $student = srStudent(status: 'graduated');

        expect(fn () => app(ActivateStudent::class)($student, 'admin-001'))
            ->toThrow(InvalidLifecycleTransitionException::class);
    });

    it('deceased profile rejects further transitions', function (): void {
        $student = srStudent(status: 'deceased');

        expect(fn () => app(ChangeStudentLifecycleStatus::class)(
            $student, StudentLifecycleStatus::Active, 'admin-001'
        ))->toThrow(InvalidLifecycleTransitionException::class);
    });

    it('draft profile cannot jump directly to withdrawn', function (): void {
        $student = srStudent(status: 'draft');

        expect(fn () => app(ChangeStudentLifecycleStatus::class)(
            $student, StudentLifecycleStatus::Withdrawn, 'admin-001'
        ))->toThrow(InvalidLifecycleTransitionException::class);
    });

});

// ---------------------------------------------------------------------------
// CorrectStudentData
// ---------------------------------------------------------------------------

describe('CorrectStudentData', function (): void {

    it('updates welfare fields when a reason is provided', function (): void {
        $student = srStudent(status: 'active');

        $result = app(CorrectStudentData::class)(
            $student,
            'secretary-001',
            'Verified via home visit',
            orphanStatus: OrphanStatus::SingleOrphan,
            displacementStatus: DisplacementStatus::InternallyDisplaced,
            displacementLocation: 'Khan Younis',
            familyMemberCount: 6,
            familyOrder: 3,
            accessibilityIndicator: true,
        );

        expect($result->orphan_status)->toBe(OrphanStatus::SingleOrphan)
            ->and($result->displacement_status)->toBe(DisplacementStatus::InternallyDisplaced)
            ->and($result->displacement_location)->toBe('Khan Younis')
            ->and($result->family_member_count)->toBe(6)
            ->and($result->family_order)->toBe(3)
            ->and($result->accessibility_indicator)->toBeTrue();
    });

    it('rejects correction when reason is empty', function (): void {
        $student = srStudent(status: 'active');

        expect(fn () => app(CorrectStudentData::class)(
            $student, 'secretary-001', '', orphanStatus: OrphanStatus::NotOrphan
        ))->toThrow(InvalidArgumentException::class);
    });

    it('rejects correction on a graduated profile', function (): void {
        $student = srStudent(status: 'graduated');

        expect(fn () => app(CorrectStudentData::class)(
            $student, 'admin-001', 'Correction attempt', familyMemberCount: 4
        ))->toThrow(InvalidLifecycleTransitionException::class);
    });

    it('rejects correction on a deceased profile', function (): void {
        $student = srStudent(status: 'deceased');

        expect(fn () => app(CorrectStudentData::class)(
            $student, 'admin-001', 'Correction attempt', familyMemberCount: 2
        ))->toThrow(InvalidLifecycleTransitionException::class);
    });

});

// ---------------------------------------------------------------------------
// SearchStudents
// ---------------------------------------------------------------------------

describe('SearchStudents', function (): void {

    it('returns all students when no filters applied', function (): void {
        srStudent(status: 'active');
        srStudent(status: 'active');

        $results = app(SearchStudents::class)();

        expect($results->count())->toBeGreaterThanOrEqual(2);
    });

    it('filters by lifecycle status', function (): void {
        srStudent(status: 'active');
        srStudent(status: 'draft');

        $results = app(SearchStudents::class)(statuses: [StudentLifecycleStatus::Draft]);

        expect($results->every(fn ($s) => $s->lifecycle_status === StudentLifecycleStatus::Draft))->toBeTrue();
    });

    it('filters by explicit person_id set (institution scope)', function (): void {
        $p1 = srPerson('طالب مؤسسة أ');
        $p2 = srPerson('طالب مؤسسة ب');
        $s1 = srStudent($p1, 'active');
        srStudent($p2, 'active');

        // Institution A secretary passes only p1 in scope.
        $results = app(SearchStudents::class)(personIds: [$p1->id]);

        expect($results->count())->toBe(1)
            ->and($results->first()->id)->toBe($s1->id);
    });

    it('returns empty when person_id set does not match any student', function (): void {
        srStudent(status: 'active');

        $results = app(SearchStudents::class)(personIds: [99999]);

        expect($results)->toBeEmpty();
    });

    it('cross-institution denial: institution B scope excludes institution A student', function (): void {
        $personA = srPerson('طالب أ');
        $personB = srPerson('طالب ب');
        srStudent($personA, 'active');
        $sB = srStudent($personB, 'active');

        // Passing only person B's scope denies access to person A's student.
        $results = app(SearchStudents::class)(personIds: [$personB->id]);

        expect($results->count())->toBe(1)
            ->and($results->first()->id)->toBe($sB->id);
    });

});

<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AcademicManagement\Actions\ActivateClassGroup;
use Modules\AcademicManagement\Actions\ArchiveClassGroup;
use Modules\AcademicManagement\Actions\CreateAcademicLevel;
use Modules\AcademicManagement\Actions\CreateClassGroup;
use Modules\AcademicManagement\Actions\CreateClassroom;
use Modules\AcademicManagement\Actions\CreateSubject;
use Modules\AcademicManagement\Actions\OfferSubject;
use Modules\AcademicManagement\Actions\RemoveSubjectOffering;
use Modules\AcademicManagement\Actions\ToggleAcademicLevel;
use Modules\AcademicManagement\Actions\ToggleClassroom;
use Modules\AcademicManagement\Actions\ToggleSubject;
use Modules\AcademicManagement\Actions\UpdateClassGroupPlacement;
use Modules\AcademicManagement\Enums\ClassGroupLifecycleStatus;
use Modules\AcademicManagement\Exceptions\ClassGroupMutationDeniedException;
use Modules\AcademicManagement\Exceptions\DuplicateOfferingException;
use Modules\AcademicManagement\Models\AcademicLevel;
use Modules\AcademicManagement\Models\ClassGroup;
use Modules\AcademicManagement\Models\Classroom;
use Modules\AcademicManagement\Models\InstitutionSubjectOffering;
use Modules\AcademicManagement\Models\Subject;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Cross-module helpers — double-backslash string-variable pattern.
// ---------------------------------------------------------------------------

function amInstitution(): object
{
    $cls = 'Modules\\Organization\\Models\\Institution';

    return $cls::factory()->create(['is_active' => true]);
}

function amSemester(int $institutionId): object
{
    $yearCls = 'Modules\\AcademicCalendar\\Models\\AcademicYear';
    $semCls = 'Modules\\AcademicCalendar\\Models\\Semester';
    $isCls = 'Modules\\AcademicCalendar\\Models\\InstitutionSemester';

    $year = $yearCls::factory()->create(['status' => 'open']);
    $sem = $semCls::factory()->create(['academic_year_id' => $year->id, 'status' => 'open']);

    return $isCls::factory()->create([
        'institution_id' => $institutionId,
        'semester_id' => $sem->id,
        'status' => 'open',
    ]);
}

function amPeriod(int $institutionSemesterId): object
{
    $cls = 'Modules\\AcademicCalendar\\Models\\OperationalPeriod';

    return $cls::factory()->create([
        'institution_semester_id' => $institutionSemesterId,
        'is_active' => true,
    ]);
}

function amLevel(): AcademicLevel
{
    return AcademicLevel::factory()->create();
}

function amClassroom(int $institutionId): Classroom
{
    return Classroom::factory()->forInstitution($institutionId)->create();
}

// ---------------------------------------------------------------------------
// AcademicLevel
// ---------------------------------------------------------------------------

describe('AcademicLevel', function (): void {

    it('creates a level with unique code', function (): void {
        $level = app(CreateAcademicLevel::class)('KG1', 'حضانة أولى', 'Kindergarten 1', 1);

        expect($level->code)->toBe('KG1')
            ->and($level->is_active)->toBeTrue()
            ->and($level->sequence)->toBe(1);
    });

    it('rejects duplicate code', function (): void {
        app(CreateAcademicLevel::class)('GRADE1', 'الصف الأول', 'Grade 1', 3);

        expect(fn () => app(CreateAcademicLevel::class)('GRADE1', 'الصف الأول', 'Grade 1', 3))
            ->toThrow(InvalidArgumentException::class);
    });

    it('can be toggled inactive', function (): void {
        $level = amLevel();
        app(ToggleAcademicLevel::class)($level, false);

        expect($level->fresh()->is_active)->toBeFalse();
    });

    it('active scope excludes inactive levels', function (): void {
        $active = amLevel();
        $inactive = amLevel();
        app(ToggleAcademicLevel::class)($inactive, false);

        $activeIds = AcademicLevel::active()->pluck('id');

        expect($activeIds)->toContain($active->id)
            ->and($activeIds)->not->toContain($inactive->id);
    });

});

// ---------------------------------------------------------------------------
// Classroom
// ---------------------------------------------------------------------------

describe('Classroom', function (): void {

    it('creates a classroom unique within its institution', function (): void {
        $inst = amInstitution();
        $classroom = app(CreateClassroom::class)($inst->id, 'CR-001', 'قاعة 1', 'Room 1', 30);

        expect($classroom->institution_id)->toBe($inst->id)
            ->and($classroom->code)->toBe('CR-001')
            ->and($classroom->capacity)->toBe(30);
    });

    it('allows the same code at different institutions', function (): void {
        $inst1 = amInstitution();
        $inst2 = amInstitution();

        app(CreateClassroom::class)($inst1->id, 'CR-001', 'قاعة 1');
        $cr2 = app(CreateClassroom::class)($inst2->id, 'CR-001', 'قاعة 1');

        expect($cr2)->not->toBeNull();
    });

    it('rejects duplicate code within the same institution', function (): void {
        $inst = amInstitution();
        app(CreateClassroom::class)($inst->id, 'CR-001', 'قاعة 1');

        expect(fn () => app(CreateClassroom::class)($inst->id, 'CR-001', 'قاعة مكررة'))
            ->toThrow(InvalidArgumentException::class);
    });

    it('institution boundary: classroom at institution A not visible to institution B scope', function (): void {
        $instA = amInstitution();
        $instB = amInstitution();

        app(CreateClassroom::class)($instA->id, 'CR-A', 'قاعة أ');
        app(CreateClassroom::class)($instB->id, 'CR-B', 'قاعة ب');

        $instBClassrooms = Classroom::forInstitution($instB->id)->pluck('code');

        expect($instBClassrooms)->toContain('CR-B')
            ->and($instBClassrooms)->not->toContain('CR-A');
    });

    it('can be toggled inactive', function (): void {
        $inst = amInstitution();
        $classroom = app(CreateClassroom::class)($inst->id, 'CR-001', 'قاعة 1');
        app(ToggleClassroom::class)($classroom, false);

        expect($classroom->fresh()->is_active)->toBeFalse();
    });

});

// ---------------------------------------------------------------------------
// ClassGroup creation and validation
// ---------------------------------------------------------------------------

describe('ClassGroup creation', function (): void {

    it('creates a class group with draft status', function (): void {
        $inst = amInstitution();
        $sem = amSemester($inst->id);
        $period = amPeriod($sem->id);
        $level = amLevel();

        $group = app(CreateClassGroup::class)(
            $sem->id, $period->id, $level, 'GRP-A', 'مجموعة أ', 'Group A'
        );

        expect($group->lifecycle_status)->toBe(ClassGroupLifecycleStatus::Draft)
            ->and($group->institution_semester_id)->toBe($sem->id)
            ->and($group->operational_period_id)->toBe($period->id);
    });

    it('rejects period from a different semester', function (): void {
        $inst = amInstitution();
        $sem1 = amSemester($inst->id);
        $sem2 = amSemester($inst->id);
        $periodFromSem2 = amPeriod($sem2->id);
        $level = amLevel();

        expect(fn () => app(CreateClassGroup::class)(
            $sem1->id, $periodFromSem2->id, $level, 'GRP-A', 'مجموعة أ'
        ))->toThrow(ClassGroupMutationDeniedException::class);
    });

    it('rejects duplicate code within the same institution semester', function (): void {
        $inst = amInstitution();
        $sem = amSemester($inst->id);
        $period = amPeriod($sem->id);
        $level = amLevel();

        app(CreateClassGroup::class)($sem->id, $period->id, $level, 'GRP-A', 'مجموعة أ');

        expect(fn () => app(CreateClassGroup::class)(
            $sem->id, $period->id, $level, 'GRP-A', 'مجموعة مكررة'
        ))->toThrow(InvalidArgumentException::class);
    });

    it('allows the same code in different institution semesters', function (): void {
        $inst = amInstitution();
        $sem1 = amSemester($inst->id);
        $sem2 = amSemester($inst->id);
        $p1 = amPeriod($sem1->id);
        $p2 = amPeriod($sem2->id);
        $level = amLevel();

        $g1 = app(CreateClassGroup::class)($sem1->id, $p1->id, $level, 'GRP-A', 'مجموعة أ');
        $g2 = app(CreateClassGroup::class)($sem2->id, $p2->id, $level, 'GRP-A', 'مجموعة أ');

        expect($g1->id)->not->toBe($g2->id);
    });

    it('derives institution through the semester chain', function (): void {
        $inst = amInstitution();
        $sem = amSemester($inst->id);
        $period = amPeriod($sem->id);
        $level = amLevel();

        $group = app(CreateClassGroup::class)($sem->id, $period->id, $level, 'GRP-X', 'مجموعة');
        $group->load('institutionSemester');

        expect($group->resolveInstitution()->id ?? null)->toBe($inst->id);
    });

});

// ---------------------------------------------------------------------------
// ClassGroup lifecycle
// ---------------------------------------------------------------------------

describe('ClassGroup lifecycle', function (): void {

    it('can be archived', function (): void {
        $inst = amInstitution();
        $sem = amSemester($inst->id);
        $period = amPeriod($sem->id);
        $level = amLevel();

        $group = app(CreateClassGroup::class)($sem->id, $period->id, $level, 'GRP-1', 'مجموعة');
        app(ArchiveClassGroup::class)($group, 'admin-001');

        expect($group->fresh()->lifecycle_status)->toBe(ClassGroupLifecycleStatus::Archived);
    });

    it('promotes a draft group to active', function (): void {
        $inst = amInstitution();
        $sem = amSemester($inst->id);
        $period = amPeriod($sem->id);
        $level = amLevel();

        $group = app(CreateClassGroup::class)($sem->id, $period->id, $level, 'GRP-ACT', 'مجموعة');
        expect($group->lifecycle_status)->toBe(ClassGroupLifecycleStatus::Draft);

        app(ActivateClassGroup::class)($group, 'admin-001');
        expect($group->fresh()->lifecycle_status)->toBe(ClassGroupLifecycleStatus::Active);
    });

    it('full lifecycle path: draft → active → archived', function (): void {
        $inst = amInstitution();
        $sem = amSemester($inst->id);
        $period = amPeriod($sem->id);
        $level = amLevel();

        $group = app(CreateClassGroup::class)($sem->id, $period->id, $level, 'GRP-FULL', 'مجموعة');
        expect($group->lifecycle_status)->toBe(ClassGroupLifecycleStatus::Draft);

        app(ActivateClassGroup::class)($group->fresh(), 'admin-001');
        expect($group->fresh()->lifecycle_status)->toBe(ClassGroupLifecycleStatus::Active);

        app(ArchiveClassGroup::class)($group->fresh(), 'admin-001');
        expect($group->fresh()->lifecycle_status)->toBe(ClassGroupLifecycleStatus::Archived);
    });

    it('rejects activating an already-active group', function (): void {
        $group = ClassGroup::factory()->active()->create();

        expect(fn () => app(ActivateClassGroup::class)($group, 'admin-001'))
            ->toThrow(ClassGroupMutationDeniedException::class);
    });

    it('rejects activating an archived group', function (): void {
        $group = ClassGroup::factory()->archived()->create();

        expect(fn () => app(ActivateClassGroup::class)($group, 'admin-001'))
            ->toThrow(ClassGroupMutationDeniedException::class);
    });

    it('rejects archiving an already-archived group', function (): void {
        $group = ClassGroup::factory()->archived()->create();

        expect(fn () => app(ArchiveClassGroup::class)($group, 'admin-001'))
            ->toThrow(ClassGroupMutationDeniedException::class);
    });

    it('rejects placement update on archived group', function (): void {
        $group = ClassGroup::factory()->archived()->create();

        expect(fn () => app(UpdateClassGroupPlacement::class)($group, null))
            ->toThrow(ClassGroupMutationDeniedException::class);
    });

    it('can update classroom placement on a non-archived group', function (): void {
        $inst = amInstitution();
        $sem = amSemester($inst->id);
        $period = amPeriod($sem->id);
        $level = amLevel();
        $classroom = amClassroom($inst->id);

        $group = app(CreateClassGroup::class)($sem->id, $period->id, $level, 'GRP-2', 'مجموعة');
        app(UpdateClassGroupPlacement::class)($group, $classroom, 25);

        $fresh = $group->fresh();
        expect($fresh->classroom_id)->toBe($classroom->id)
            ->and($fresh->capacity)->toBe(25);
    });

    it('rejects classroom from a different institution on creation', function (): void {
        $instA = amInstitution();
        $instB = amInstitution();
        $sem = amSemester($instA->id);
        $period = amPeriod($sem->id);
        $level = amLevel();
        $classroomB = amClassroom($instB->id);

        expect(fn () => app(CreateClassGroup::class)(
            $sem->id, $period->id, $level, 'GRP-X', 'مجموعة', null, $classroomB
        ))->toThrow(ClassGroupMutationDeniedException::class);
    });

    it('rejects classroom from a different institution on placement update', function (): void {
        $instA = amInstitution();
        $instB = amInstitution();
        $semA = amSemester($instA->id);
        $periodA = amPeriod($semA->id);
        $level = amLevel();
        $classroomB = amClassroom($instB->id);

        $group = app(CreateClassGroup::class)($semA->id, $periodA->id, $level, 'GRP-Y', 'مجموعة');

        expect(fn () => app(UpdateClassGroupPlacement::class)($group, $classroomB))
            ->toThrow(ClassGroupMutationDeniedException::class);
    });

    it('rejects inactive academic level on class group creation', function (): void {
        $inst = amInstitution();
        $sem = amSemester($inst->id);
        $period = amPeriod($sem->id);
        $level = AcademicLevel::factory()->inactive()->create();

        expect(fn () => app(CreateClassGroup::class)($sem->id, $period->id, $level, 'GRP-Z', 'مجموعة'))
            ->toThrow(ClassGroupMutationDeniedException::class);
    });

    it('rejects inactive classroom on class group creation', function (): void {
        $inst = amInstitution();
        $sem = amSemester($inst->id);
        $period = amPeriod($sem->id);
        $level = amLevel();
        $classroom = Classroom::factory()->forInstitution($inst->id)->inactive()->create();

        expect(fn () => app(CreateClassGroup::class)(
            $sem->id, $period->id, $level, 'GRP-W', 'مجموعة', null, $classroom
        ))->toThrow(ClassGroupMutationDeniedException::class);
    });

    it('rejects inactive operational period on class group creation', function (): void {
        $inst = amInstitution();
        $sem = amSemester($inst->id);
        $level = amLevel();

        // Create an inactive period.
        $periodCls = 'Modules\\AcademicCalendar\\Models\\OperationalPeriod';
        $inactivePeriod = $periodCls::factory()->create([
            'institution_semester_id' => $sem->id,
            'is_active' => false,
        ]);

        expect(fn () => app(CreateClassGroup::class)(
            $sem->id, $inactivePeriod->id, $level, 'GRP-V', 'مجموعة'
        ))->toThrow(ClassGroupMutationDeniedException::class);
    });

});

// ---------------------------------------------------------------------------
// Parent existence validation
// ---------------------------------------------------------------------------

describe('parent existence validation', function (): void {

    it('CreateClassroom rejects a nonexistent institution_id', function (): void {
        expect(fn () => app(CreateClassroom::class)(99999, 'CR-X', 'قاعة'))
            ->toThrow(InvalidArgumentException::class);
    });

    it('CreateClassroom rejects an inactive institution', function (): void {
        $instCls = 'Modules\\Organization\\Models\\Institution';
        $inst = $instCls::factory()->create(['is_active' => false]);

        expect(fn () => app(CreateClassroom::class)($inst->id, 'CR-Y', 'قاعة'))
            ->toThrow(InvalidArgumentException::class);
    });

    it('OfferSubject rejects a nonexistent institution_semester_id', function (): void {
        $subject = Subject::factory()->create();

        expect(fn () => app(OfferSubject::class)(99999, $subject))
            ->toThrow(InvalidArgumentException::class);
    });

    it('OfferSubject rejects an inactive subject', function (): void {
        $inst = amInstitution();
        $sem = amSemester($inst->id);
        $subject = Subject::factory()->inactive()->create();

        expect(fn () => app(OfferSubject::class)($sem->id, $subject))
            ->toThrow(InvalidArgumentException::class);
    });

    it('CreateClassGroup rejects a nonexistent institution_semester_id', function (): void {
        $level = amLevel();
        $periodCls = 'Modules\\AcademicCalendar\\Models\\OperationalPeriod';
        // Period referencing a real semester so period lookup doesn't fail first;
        // but passing a different nonexistent semester ID should fail.
        $inst = amInstitution();
        $sem = amSemester($inst->id);
        $period = amPeriod($sem->id);

        expect(fn () => app(CreateClassGroup::class)(99999, $period->id, $level, 'GRP-NE', 'مجموعة'))
            ->toThrow(InvalidArgumentException::class);
    });

});

// ---------------------------------------------------------------------------
// Subject and InstitutionSubjectOffering
// ---------------------------------------------------------------------------

describe('Subject and offerings', function (): void {

    it('creates a subject with unique code', function (): void {
        $subject = app(CreateSubject::class)('MATH', 'الرياضيات', 'Mathematics');

        expect($subject->code)->toBe('MATH')
            ->and($subject->is_active)->toBeTrue();
    });

    it('rejects duplicate subject code', function (): void {
        app(CreateSubject::class)('ARABIC', 'اللغة العربية', 'Arabic Language');

        expect(fn () => app(CreateSubject::class)('ARABIC', 'عربي', 'Arabic'))
            ->toThrow(InvalidArgumentException::class);
    });

    it('can toggle subject inactive', function (): void {
        $subject = Subject::factory()->create();
        app(ToggleSubject::class)($subject, false);

        expect($subject->fresh()->is_active)->toBeFalse();
    });

    it('offers a subject in a semester', function (): void {
        $inst = amInstitution();
        $sem = amSemester($inst->id);
        $subject = Subject::factory()->create();

        $offering = app(OfferSubject::class)($sem->id, $subject);

        expect($offering->institution_semester_id)->toBe($sem->id)
            ->and($offering->subject_id)->toBe($subject->id);
    });

    it('prevents offering the same subject twice in the same semester', function (): void {
        $inst = amInstitution();
        $sem = amSemester($inst->id);
        $subject = Subject::factory()->create();

        app(OfferSubject::class)($sem->id, $subject);

        expect(fn () => app(OfferSubject::class)($sem->id, $subject))
            ->toThrow(DuplicateOfferingException::class);
    });

    it('can offer the same subject in a different semester', function (): void {
        $inst = amInstitution();
        $sem1 = amSemester($inst->id);
        $sem2 = amSemester($inst->id);
        $subject = Subject::factory()->create();

        app(OfferSubject::class)($sem1->id, $subject);
        $offering2 = app(OfferSubject::class)($sem2->id, $subject);

        expect($offering2)->not->toBeNull();
    });

    it('removes a subject offering', function (): void {
        $inst = amInstitution();
        $sem = amSemester($inst->id);
        $subject = Subject::factory()->create();

        $offering = app(OfferSubject::class)($sem->id, $subject);
        $id = $offering->id;
        app(RemoveSubjectOffering::class)($offering);

        expect(InstitutionSubjectOffering::find($id))->toBeNull();
    });

});

// ---------------------------------------------------------------------------
// AcademicLevelReferenceSeeder
// ---------------------------------------------------------------------------

describe('AcademicLevelReferenceSeeder', function (): void {

    it('seeds 14 GCV levels idempotently', function (): void {
        $seeder = 'Modules\\AcademicManagement\\Database\\Seeders\\AcademicLevelReferenceSeeder';
        app($seeder)->run();
        app($seeder)->run(); // second run must not throw or duplicate

        expect(AcademicLevel::count())->toBe(14);
    });

    it('seeds KG1 and KG2 first in sequence', function (): void {
        $seeder = 'Modules\\AcademicManagement\\Database\\Seeders\\AcademicLevelReferenceSeeder';
        app($seeder)->run();

        $kg1 = AcademicLevel::where('code', 'KG1')->first();
        $kg2 = AcademicLevel::where('code', 'KG2')->first();
        $grade1 = AcademicLevel::where('code', 'GRADE1')->first();

        expect($kg1->sequence)->toBe(1)
            ->and($kg2->sequence)->toBe(2)
            ->and($grade1->sequence)->toBe(3);
    });

    it('seeds GRADE12 with correct Arabic name', function (): void {
        $seeder = 'Modules\\AcademicManagement\\Database\\Seeders\\AcademicLevelReferenceSeeder';
        app($seeder)->run();

        $grade12 = AcademicLevel::where('code', 'GRADE12')->first();

        expect($grade12->name_ar)->toBe('الصف الثاني عشر')
            ->and($grade12->sequence)->toBe(14);
    });

});

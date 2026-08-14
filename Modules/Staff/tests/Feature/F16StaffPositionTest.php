<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\People\Actions\CreatePerson;
use Modules\Staff\Actions\AssignPosition;
use Modules\Staff\Actions\CreateStaffProfile;
use Modules\Staff\Actions\DeterminesPeriodCoverage;
use Modules\Staff\Actions\EndPosition;
use Modules\Staff\Actions\ListPositionHistory;
use Modules\Staff\Actions\ReplacePositionScopes;
use Modules\Staff\Actions\ResolveActivePositionsForDate;
use Modules\Staff\Actions\StartAssignment;
use Modules\Staff\Enums\PositionDefinition;
use Modules\Staff\Exceptions\PositionMutationDeniedException;
use Modules\Staff\Exceptions\PositionOverlapException;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers — cross-module references use double-backslash (boundary scanner).
// ---------------------------------------------------------------------------

function f16Institution(bool $active = true): object
{
    $cls = 'Modules\\Organization\\Models\\Institution';

    return $cls::factory()->create(['is_active' => $active]);
}

function f16Semester(int $institutionId, string $status = 'draft'): object
{
    // Need a global Semester and AcademicYear first.
    $yearCls = 'Modules\\AcademicCalendar\\Models\\AcademicYear';
    $semCls = 'Modules\\AcademicCalendar\\Models\\Semester';
    $isCls = 'Modules\\AcademicCalendar\\Models\\InstitutionSemester';

    $year = $yearCls::factory()->create(['status' => 'open']);
    $globalSem = $semCls::factory()->create([
        'academic_year_id' => $year->id,
        'status' => 'open',
    ]);

    return $isCls::factory()->create([
        'institution_id' => $institutionId,
        'semester_id' => $globalSem->id,
        'status' => $status,
    ]);
}

function f16Period(int $institutionSemesterId): object
{
    $cls = 'Modules\\AcademicCalendar\\Models\\OperationalPeriod';

    return $cls::factory()->create([
        'institution_semester_id' => $institutionSemesterId,
        'is_active' => true,
    ]);
}

function f16Profile(): object
{
    $person = app(CreatePerson::class)('محمد أحمد');

    return app(CreateStaffProfile::class)($person->id, 'F16-'.rand(1000, 9999));
}

function f16Assignment(object $profile, int $institutionId): object
{
    return app(StartAssignment::class)(
        $profile,
        $institutionId,
        new DateTime('2026-09-01'),
    );
}

// ---------------------------------------------------------------------------
// Basic position assignment
// ---------------------------------------------------------------------------

describe('basic position assignment', function (): void {

    it('creates a position for an active profile with a valid assignment', function (): void {
        $inst = f16Institution();
        $semester = f16Semester($inst->id);
        $profile = f16Profile();
        f16Assignment($profile, $inst->id);

        $position = app(AssignPosition::class)(
            $profile,
            $inst->id,
            PositionDefinition::Teacher,
            new DateTime('2026-09-01'),
            'admin',
            $semester->id,
        );

        expect($position->id)->toBeGreaterThan(0);
        expect($position->position_definition)->toBe(PositionDefinition::Teacher);
        expect($position->institution_semester_id)->toBe($semester->id);
        expect($position->isOpen())->toBeTrue();
    });

    it('creates a non-academic position without InstitutionSemester', function (): void {
        $inst = f16Institution();
        $profile = f16Profile();
        f16Assignment($profile, $inst->id);

        $position = app(AssignPosition::class)(
            $profile,
            $inst->id,
            PositionDefinition::Guard,
            new DateTime('2026-09-01'),
            'admin',
        );

        expect($position->institution_semester_id)->toBeNull();
        expect($position->position_definition)->toBe(PositionDefinition::Guard);
    });

    it('creating a position never creates a StaffAccount', function (): void {
        $inst = f16Institution();
        $profile = f16Profile();
        f16Assignment($profile, $inst->id);

        $accountCls = 'Modules\\Accounts\\Models\\StaffAccount';
        $before = $accountCls::count();

        app(AssignPosition::class)(
            $profile, $inst->id, PositionDefinition::Guard,
            new DateTime('2026-09-01'), 'admin',
        );

        expect($accountCls::count())->toBe($before);
    });

    it('fails without an active assignment at the given institution', function (): void {
        $inst1 = f16Institution();
        $inst2 = f16Institution();
        $profile = f16Profile();
        f16Assignment($profile, $inst1->id);

        expect(fn () => app(AssignPosition::class)(
            $profile, $inst2->id, PositionDefinition::Teacher,
            new DateTime('2026-09-01'), 'admin',
        ))->toThrow(InvalidArgumentException::class);
    });

});

// ---------------------------------------------------------------------------
// Overlap and mutual exclusion
// ---------------------------------------------------------------------------

describe('overlap detection', function (): void {

    it('rejects a duplicate same-definition overlap', function (): void {
        $inst = f16Institution();
        $semester = f16Semester($inst->id);
        $profile = f16Profile();
        f16Assignment($profile, $inst->id);

        app(AssignPosition::class)(
            $profile, $inst->id, PositionDefinition::Teacher,
            new DateTime('2026-09-01'), 'admin', $semester->id,
        );

        expect(fn () => app(AssignPosition::class)(
            $profile, $inst->id, PositionDefinition::Teacher,
            new DateTime('2026-10-01'), 'admin', $semester->id,
        ))->toThrow(PositionOverlapException::class);
    });

    it('allows the same definition after ending the first', function (): void {
        $inst = f16Institution();
        $semester = f16Semester($inst->id);
        $profile = f16Profile();
        f16Assignment($profile, $inst->id);

        $pos1 = app(AssignPosition::class)(
            $profile, $inst->id, PositionDefinition::Teacher,
            new DateTime('2026-09-01'), 'admin', $semester->id,
        );

        app(EndPosition::class)($pos1, new DateTime('2026-09-30'), 'semester_end', 'admin');

        $pos2 = app(AssignPosition::class)(
            $profile, $inst->id, PositionDefinition::Teacher,
            new DateTime('2026-10-01'), 'admin', $semester->id,
        );

        expect($pos2->id)->toBeGreaterThan($pos1->id);
    });

    it('allows different definitions to coexist', function (): void {
        $inst = f16Institution();
        $semester = f16Semester($inst->id);
        $profile = f16Profile();
        f16Assignment($profile, $inst->id);

        app(AssignPosition::class)(
            $profile, $inst->id, PositionDefinition::Teacher,
            new DateTime('2026-09-01'), 'admin', $semester->id,
        );
        $pos2 = app(AssignPosition::class)(
            $profile, $inst->id, PositionDefinition::Counselor,
            new DateTime('2026-09-01'), 'admin', $semester->id,
        );

        expect($pos2->id)->toBeGreaterThan(0);
    });

    it('rejects principal when deputy_principal is active (mutual exclusion)', function (): void {
        $inst = f16Institution();
        $semester = f16Semester($inst->id);
        $profile = f16Profile();
        f16Assignment($profile, $inst->id);

        app(AssignPosition::class)(
            $profile, $inst->id, PositionDefinition::DeputyPrincipal,
            new DateTime('2026-09-01'), 'admin', $semester->id,
        );

        expect(fn () => app(AssignPosition::class)(
            $profile, $inst->id, PositionDefinition::Principal,
            new DateTime('2026-09-01'), 'admin', $semester->id,
        ))->toThrow(PositionOverlapException::class);
    });

    it('allows principal and deputy_principal at the same institution for different people', function (): void {
        $inst = f16Institution();
        $semester = f16Semester($inst->id);

        $profile1 = f16Profile();
        f16Assignment($profile1, $inst->id);

        $profile2 = f16Profile();
        f16Assignment($profile2, $inst->id);

        app(AssignPosition::class)(
            $profile1, $inst->id, PositionDefinition::Principal,
            new DateTime('2026-09-01'), 'admin', $semester->id,
        );

        $pos = app(AssignPosition::class)(
            $profile2, $inst->id, PositionDefinition::DeputyPrincipal,
            new DateTime('2026-09-01'), 'admin', $semester->id,
        );

        expect($pos->id)->toBeGreaterThan(0);
    });

});

// ---------------------------------------------------------------------------
// Closed / archived semester rejection
// ---------------------------------------------------------------------------

describe('semester lifecycle guards', function (): void {

    it('rejects position assignment on a closed semester', function (): void {
        $inst = f16Institution();
        $semester = f16Semester($inst->id, 'closed');
        $profile = f16Profile();
        f16Assignment($profile, $inst->id);

        expect(fn () => app(AssignPosition::class)(
            $profile, $inst->id, PositionDefinition::Teacher,
            new DateTime('2026-09-01'), 'admin', $semester->id,
        ))->toThrow(PositionMutationDeniedException::class);
    });

    it('rejects position assignment on an archived semester', function (): void {
        $inst = f16Institution();
        $semester = f16Semester($inst->id, 'archived');
        $profile = f16Profile();
        f16Assignment($profile, $inst->id);

        expect(fn () => app(AssignPosition::class)(
            $profile, $inst->id, PositionDefinition::Teacher,
            new DateTime('2026-09-01'), 'admin', $semester->id,
        ))->toThrow(PositionMutationDeniedException::class);
    });

    it('allows position assignment on an open semester', function (): void {
        $inst = f16Institution();
        $semester = f16Semester($inst->id, 'open');
        $profile = f16Profile();
        f16Assignment($profile, $inst->id);

        $pos = app(AssignPosition::class)(
            $profile, $inst->id, PositionDefinition::Secretary,
            new DateTime('2026-09-01'), 'admin', $semester->id,
        );

        expect($pos->id)->toBeGreaterThan(0);
    });

});

// ---------------------------------------------------------------------------
// Ending positions
// ---------------------------------------------------------------------------

describe('ending positions', function (): void {

    it('can end an active position', function (): void {
        $inst = f16Institution();
        $profile = f16Profile();
        f16Assignment($profile, $inst->id);

        $pos = app(AssignPosition::class)(
            $profile, $inst->id, PositionDefinition::Guard,
            new DateTime('2026-09-01'), 'admin',
        );

        app(EndPosition::class)($pos, new DateTime('2026-12-31'), 'end_of_deployment', 'admin');

        $pos->refresh();
        expect($pos->ended_on->format('Y-m-d'))->toBe('2026-12-31');
        expect($pos->isOpen())->toBeFalse();
    });

    it('cannot end an already-ended position', function (): void {
        $inst = f16Institution();
        $profile = f16Profile();
        f16Assignment($profile, $inst->id);

        $pos = app(AssignPosition::class)(
            $profile, $inst->id, PositionDefinition::Guard,
            new DateTime('2026-09-01'), 'admin',
        );

        app(EndPosition::class)($pos, new DateTime('2026-12-31'), 'end', 'admin');

        expect(fn () => app(EndPosition::class)($pos, new DateTime('2026-12-31'), 'end', 'admin'))
            ->toThrow(InvalidArgumentException::class);
    });

    it('historical positions remain readable after ending', function (): void {
        $inst = f16Institution();
        $profile = f16Profile();
        f16Assignment($profile, $inst->id);

        $pos = app(AssignPosition::class)(
            $profile, $inst->id, PositionDefinition::Guard,
            new DateTime('2026-09-01'), 'admin',
        );

        app(EndPosition::class)($pos, new DateTime('2026-12-31'), 'end', 'admin');

        $history = app(ListPositionHistory::class)($profile);
        expect($history->count())->toBe(1);
        expect($history->first()->ended_on)->not->toBeNull();
    });

});

// ---------------------------------------------------------------------------
// Period scopes
// ---------------------------------------------------------------------------

describe('period scopes', function (): void {

    it('cannot add period links to a non-academic position', function (): void {
        $inst = f16Institution();
        $profile = f16Profile();
        f16Assignment($profile, $inst->id);

        $pos = app(AssignPosition::class)(
            $profile, $inst->id, PositionDefinition::Guard,
            new DateTime('2026-09-01'), 'admin',
        );

        expect(fn () => app(ReplacePositionScopes::class)($pos, [1, 2]))
            ->toThrow(PositionMutationDeniedException::class);
    });

    it('assigns explicit period links to a secretary position', function (): void {
        $inst = f16Institution();
        $semester = f16Semester($inst->id);
        $profile = f16Profile();
        f16Assignment($profile, $inst->id);

        $period1 = f16Period($semester->id);
        $period2 = f16Period($semester->id);

        $pos = app(AssignPosition::class)(
            $profile, $inst->id, PositionDefinition::Secretary,
            new DateTime('2026-09-01'), 'admin', $semester->id,
        );

        app(ReplacePositionScopes::class)($pos, [$period1->id]);

        // One-period secretary: covers period1, not period2
        expect(app(DeterminesPeriodCoverage::class)($pos, $period1->id))->toBeTrue();
        expect(app(DeterminesPeriodCoverage::class)($pos, $period2->id))->toBeFalse();
    });

    it('all-periods must be represented by explicit links', function (): void {
        $inst = f16Institution();
        $semester = f16Semester($inst->id);
        $profile = f16Profile();
        f16Assignment($profile, $inst->id);

        $period1 = f16Period($semester->id);
        $period2 = f16Period($semester->id);
        $period3 = f16Period($semester->id);

        $pos = app(AssignPosition::class)(
            $profile, $inst->id, PositionDefinition::Secretary,
            new DateTime('2026-09-01'), 'admin', $semester->id,
        );

        app(ReplacePositionScopes::class)($pos, [$period1->id, $period2->id, $period3->id]);

        expect(app(DeterminesPeriodCoverage::class)($pos, $period1->id))->toBeTrue();
        expect(app(DeterminesPeriodCoverage::class)($pos, $period2->id))->toBeTrue();
        expect(app(DeterminesPeriodCoverage::class)($pos, $period3->id))->toBeTrue();
    });

    it('adding a new period does not silently expand access', function (): void {
        $inst = f16Institution();
        $semester = f16Semester($inst->id);
        $profile = f16Profile();
        f16Assignment($profile, $inst->id);

        $period1 = f16Period($semester->id);

        $pos = app(AssignPosition::class)(
            $profile, $inst->id, PositionDefinition::Secretary,
            new DateTime('2026-09-01'), 'admin', $semester->id,
        );

        app(ReplacePositionScopes::class)($pos, [$period1->id]);

        // A new period added to the semester later
        $period2 = f16Period($semester->id);

        // Secretary's access does NOT automatically expand
        expect(app(DeterminesPeriodCoverage::class)($pos, $period2->id))->toBeFalse();
    });

    it('position with no period links returns null from DeterminesPeriodCoverage', function (): void {
        $inst = f16Institution();
        $semester = f16Semester($inst->id);
        $profile = f16Profile();
        f16Assignment($profile, $inst->id);

        $period = f16Period($semester->id);

        $pos = app(AssignPosition::class)(
            $profile, $inst->id, PositionDefinition::Teacher,
            new DateTime('2026-09-01'), 'admin', $semester->id,
        );

        // No links → null (caller decides default behavior)
        expect(app(DeterminesPeriodCoverage::class)($pos, $period->id))->toBeNull();
    });

    it('period links must belong to the position institution semester', function (): void {
        $inst = f16Institution();
        $semester1 = f16Semester($inst->id);
        $semester2 = f16Semester($inst->id);

        $periodFromOtherSemester = f16Period($semester2->id);

        $profile = f16Profile();
        f16Assignment($profile, $inst->id);

        $pos = app(AssignPosition::class)(
            $profile, $inst->id, PositionDefinition::Secretary,
            new DateTime('2026-09-01'), 'admin', $semester1->id,
        );

        expect(fn () => app(ReplacePositionScopes::class)($pos, [$periodFromOtherSemester->id]))
            ->toThrow(InvalidArgumentException::class);
    });

    it('replacing scopes atomically replaces all links', function (): void {
        $inst = f16Institution();
        $semester = f16Semester($inst->id);
        $profile = f16Profile();
        f16Assignment($profile, $inst->id);

        $period1 = f16Period($semester->id);
        $period2 = f16Period($semester->id);
        $period3 = f16Period($semester->id);

        $pos = app(AssignPosition::class)(
            $profile, $inst->id, PositionDefinition::Secretary,
            new DateTime('2026-09-01'), 'admin', $semester->id,
        );

        app(ReplacePositionScopes::class)($pos, [$period1->id, $period2->id]);

        // Now replace with just period3
        app(ReplacePositionScopes::class)($pos, [$period3->id]);

        expect(app(DeterminesPeriodCoverage::class)($pos, $period1->id))->toBeFalse();
        expect(app(DeterminesPeriodCoverage::class)($pos, $period2->id))->toBeFalse();
        expect(app(DeterminesPeriodCoverage::class)($pos, $period3->id))->toBeTrue();
    });

});

// ---------------------------------------------------------------------------
// Active position resolution
// ---------------------------------------------------------------------------

describe('resolve active positions for date', function (): void {

    it('resolves positions on the start date', function (): void {
        $inst = f16Institution();
        $semester = f16Semester($inst->id);
        $profile = f16Profile();
        f16Assignment($profile, $inst->id);

        app(AssignPosition::class)(
            $profile, $inst->id, PositionDefinition::Teacher,
            new DateTime('2026-09-01'), 'admin', $semester->id,
        );

        $active = app(ResolveActivePositionsForDate::class)($profile, new DateTime('2026-09-01'));
        expect($active->count())->toBe(1);
    });

    it('does not resolve positions before start date', function (): void {
        $inst = f16Institution();
        $semester = f16Semester($inst->id);
        $profile = f16Profile();
        f16Assignment($profile, $inst->id);

        app(AssignPosition::class)(
            $profile, $inst->id, PositionDefinition::Teacher,
            new DateTime('2026-09-01'), 'admin', $semester->id,
        );

        $active = app(ResolveActivePositionsForDate::class)($profile, new DateTime('2026-08-31'));
        expect($active->count())->toBe(0);
    });

    it('does not resolve ended positions', function (): void {
        $inst = f16Institution();
        $profile = f16Profile();
        f16Assignment($profile, $inst->id);

        $pos = app(AssignPosition::class)(
            $profile, $inst->id, PositionDefinition::Guard,
            new DateTime('2026-09-01'), 'admin',
        );

        app(EndPosition::class)($pos, new DateTime('2026-09-30'), 'end', 'admin');

        $active = app(ResolveActivePositionsForDate::class)($profile, new DateTime('2026-10-01'));
        expect($active->count())->toBe(0);
    });

});

// ---------------------------------------------------------------------------
// Non-login staff (guards)
// ---------------------------------------------------------------------------

describe('non-login staff — guards', function (): void {

    it('guard position can be created without a StaffAccount', function (): void {
        $inst = f16Institution();
        $profile = f16Profile();
        f16Assignment($profile, $inst->id);

        $pos = app(AssignPosition::class)(
            $profile, $inst->id, PositionDefinition::Guard,
            new DateTime('2026-09-01'), 'admin',
        );

        expect($pos->id)->toBeGreaterThan(0);
        expect($pos->position_definition)->toBe(PositionDefinition::Guard);
    });

    it('guard has complete position history', function (): void {
        $inst = f16Institution();
        $profile = f16Profile();
        f16Assignment($profile, $inst->id);

        $pos1 = app(AssignPosition::class)(
            $profile, $inst->id, PositionDefinition::Guard,
            new DateTime('2026-09-01'), 'admin',
        );

        app(EndPosition::class)($pos1, new DateTime('2026-09-30'), 'rotation', 'admin');

        $pos2 = app(AssignPosition::class)(
            $profile, $inst->id, PositionDefinition::Guard,
            new DateTime('2026-10-01'), 'admin',
        );

        $history = app(ListPositionHistory::class)($profile);
        expect($history->count())->toBe(2);
        expect($history[0]->ended_on)->not->toBeNull();
        expect($history[1]->isOpen())->toBeTrue();
    });

});

// ---------------------------------------------------------------------------
// No inferred teaching access
// ---------------------------------------------------------------------------

describe('no inferred teaching access from position', function (): void {

    it('teacher position holds no class, course, subject or student reference columns', function (): void {
        $inst = f16Institution();
        $semester = f16Semester($inst->id);
        $profile = f16Profile();
        f16Assignment($profile, $inst->id);

        $pos = app(AssignPosition::class)(
            $profile, $inst->id, PositionDefinition::Teacher,
            new DateTime('2026-09-01'), 'admin', $semester->id,
        );

        $posArray = $pos->toArray();

        foreach (['class_id', 'subject_id', 'student_id', 'course_id', 'mark_id'] as $col) {
            expect($posArray)->not->toHaveKey($col);
        }
    });

});

// ---------------------------------------------------------------------------
// Position history
// ---------------------------------------------------------------------------

describe('position history', function (): void {

    it('lists full position history in chronological order', function (): void {
        $inst = f16Institution();
        $semester = f16Semester($inst->id);
        $profile = f16Profile();
        f16Assignment($profile, $inst->id);

        $pos1 = app(AssignPosition::class)(
            $profile, $inst->id, PositionDefinition::Teacher,
            new DateTime('2026-09-01'), 'admin', $semester->id,
        );

        app(EndPosition::class)($pos1, new DateTime('2026-09-30'), 'end', 'admin');

        app(AssignPosition::class)(
            $profile, $inst->id, PositionDefinition::Counselor,
            new DateTime('2026-10-01'), 'admin', $semester->id,
        );

        $history = app(ListPositionHistory::class)($profile);
        expect($history->count())->toBe(2);
        expect($history[0]->position_definition)->toBe(PositionDefinition::Teacher);
        expect($history[1]->position_definition)->toBe(PositionDefinition::Counselor);
    });

    it('historical positions remain readable after semester closes', function (): void {
        $inst = f16Institution();
        $semesterCls = 'Modules\\AcademicCalendar\\Models\\InstitutionSemester';
        $semester = f16Semester($inst->id, 'open');
        $profile = f16Profile();
        f16Assignment($profile, $inst->id);

        app(AssignPosition::class)(
            $profile, $inst->id, PositionDefinition::Secretary,
            new DateTime('2026-09-01'), 'admin', $semester->id,
        );

        // Close the semester (bypassing business action for test simplicity)
        $semesterCls::where('id', $semester->id)->update(['status' => 'closed']);

        // History is still readable
        $history = app(ListPositionHistory::class)($profile);
        expect($history->count())->toBe(1);

        // Resolving active positions still works
        $active = app(ResolveActivePositionsForDate::class)($profile, new DateTime('2026-09-15'));
        expect($active->count())->toBe(1);
    });

});

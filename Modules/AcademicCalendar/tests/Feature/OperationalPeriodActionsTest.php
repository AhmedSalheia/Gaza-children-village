<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AcademicCalendar\Actions\AddOperationalPeriod;
use Modules\AcademicCalendar\Actions\DeactivateOperationalPeriod;
use Modules\AcademicCalendar\Actions\ListOperationalPeriods;
use Modules\AcademicCalendar\Actions\UpdateOperationalPeriod;
use Modules\AcademicCalendar\Data\CreateOperationalPeriodData;
use Modules\AcademicCalendar\Data\UpdateOperationalPeriodData;
use Modules\AcademicCalendar\Database\Factories\InstitutionSemesterFactory;
use Modules\AcademicCalendar\Database\Factories\OperationalPeriodFactory;
use Modules\AcademicCalendar\Models\InstitutionSemester;
use Modules\AcademicCalendar\Models\OperationalPeriod;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function draftIs(): InstitutionSemester
{
    return InstitutionSemesterFactory::new()->draft()->create();
}

function addPeriod(InstitutionSemester $is, string $code, int $seq, string $start, string $end): OperationalPeriod
{
    return (new AddOperationalPeriod)->execute($is, new CreateOperationalPeriodData(
        code: $code,
        nameEn: "Period {$code}",
        nameAr: null,
        sequence: $seq,
        startsAt: $start,
        endsAt: $end,
    ));
}

// ---------------------------------------------------------------------------
// Creation
// ---------------------------------------------------------------------------

it('adds an operational period to a draft institution semester', function (): void {
    $is = draftIs();

    $period = addPeriod($is, 'MORNING', 1, '08:00:00', '12:00:00');

    expect($period->id)->toBeInt()
        ->and($period->code)->toBe('MORNING')
        ->and($period->sequence)->toBe(1)
        ->and($period->starts_at)->toBe('08:00:00')
        ->and($period->ends_at)->toBe('12:00:00')
        ->and($period->is_active)->toBeTrue();
});

it('rejects period creation when institution semester is not draft', function (): void {
    $is = InstitutionSemesterFactory::new()->open()->create();

    expect(fn () => addPeriod($is, 'MORNING', 1, '08:00:00', '12:00:00'))
        ->toThrow(RuntimeException::class);
});

it('rejects period creation with invalid time order', function (): void {
    $is = draftIs();

    expect(fn () => addPeriod($is, 'MORNING', 1, '12:00:00', '08:00:00'))
        ->toThrow(RuntimeException::class);
});

it('rejects period creation with equal start and end times', function (): void {
    $is = draftIs();

    expect(fn () => addPeriod($is, 'MORNING', 1, '08:00:00', '08:00:00'))
        ->toThrow(RuntimeException::class);
});

it('rejects period creation with negative sequence', function (): void {
    $is = draftIs();

    expect(fn () => (new AddOperationalPeriod)->execute($is, new CreateOperationalPeriodData(
        code: 'BAD', nameEn: 'Bad', nameAr: null, sequence: -1,
        startsAt: '08:00:00', endsAt: '12:00:00',
    )))->toThrow(RuntimeException::class);
});

it('rejects period creation with zero sequence', function (): void {
    $is = draftIs();

    expect(fn () => (new AddOperationalPeriod)->execute($is, new CreateOperationalPeriodData(
        code: 'BAD', nameEn: 'Bad', nameAr: null, sequence: 0,
        startsAt: '08:00:00', endsAt: '12:00:00',
    )))->toThrow(RuntimeException::class);
});

it('rejects duplicate code within the same institution semester', function (): void {
    $is = draftIs();
    addPeriod($is, 'MORNING', 1, '08:00:00', '12:00:00');

    expect(fn () => addPeriod($is, 'MORNING', 2, '13:00:00', '17:00:00'))
        ->toThrow(RuntimeException::class);
});

it('rejects duplicate sequence within the same institution semester', function (): void {
    $is = draftIs();
    addPeriod($is, 'MORNING', 1, '08:00:00', '12:00:00');

    expect(fn () => addPeriod($is, 'AFTERNOON', 1, '13:00:00', '17:00:00'))
        ->toThrow(RuntimeException::class);
});

it('allows three or more periods without a two-period assumption', function (): void {
    $is = draftIs();

    addPeriod($is, 'P1', 1, '07:00:00', '09:00:00');
    addPeriod($is, 'P2', 2, '09:00:00', '11:00:00');
    addPeriod($is, 'P3', 3, '11:00:00', '13:00:00');
    addPeriod($is, 'P4', 4, '13:00:00', '15:00:00');

    expect(OperationalPeriod::where('institution_semester_id', $is->id)->count())->toBe(4);
});

// ---------------------------------------------------------------------------
// Overlap enforcement
// ---------------------------------------------------------------------------

it('rejects overlapping periods', function (): void {
    $is = draftIs();
    addPeriod($is, 'MORNING', 1, '08:00:00', '12:00:00');

    expect(fn () => addPeriod($is, 'OVERLAP', 2, '10:00:00', '14:00:00'))
        ->toThrow(RuntimeException::class);
});

it('rejects period that starts before existing period ends', function (): void {
    $is = draftIs();
    addPeriod($is, 'MORNING', 1, '08:00:00', '12:00:00');

    expect(fn () => addPeriod($is, 'OVERLAP', 2, '11:59:00', '16:00:00'))
        ->toThrow(RuntimeException::class);
});

it('allows adjacent period boundaries', function (): void {
    $is = draftIs();
    addPeriod($is, 'MORNING', 1, '08:00:00', '12:00:00');
    $afternoon = addPeriod($is, 'AFTERNOON', 2, '12:00:00', '16:00:00');

    expect($afternoon->id)->toBeInt();
});

it('inactive periods are excluded from overlap checks', function (): void {
    $is = draftIs();
    $morning = addPeriod($is, 'MORNING', 1, '08:00:00', '12:00:00');

    (new DeactivateOperationalPeriod)->execute($morning);

    // This overlaps with the deactivated period but should now be allowed.
    $replacement = addPeriod($is, 'REPLACEMENT', 2, '08:00:00', '12:00:00');

    expect($replacement->id)->toBeInt();
});

it('overlapping periods in different institution semesters are allowed', function (): void {
    $is1 = draftIs();
    $is2 = draftIs();

    addPeriod($is1, 'MORNING', 1, '08:00:00', '12:00:00');
    $p2 = addPeriod($is2, 'MORNING', 1, '08:00:00', '12:00:00');

    expect($p2->id)->toBeInt();
});

// ---------------------------------------------------------------------------
// Update
// ---------------------------------------------------------------------------

it('updates an operational period name while draft', function (): void {
    $is = draftIs();
    $period = addPeriod($is, 'MORNING', 1, '08:00:00', '12:00:00');

    (new UpdateOperationalPeriod)->execute($period, new UpdateOperationalPeriodData(
        nameEn: 'Updated Name',
    ));

    expect($period->name_en)->toBe('Updated Name');
});

it('updates operational period times while draft', function (): void {
    $is = draftIs();
    $period = addPeriod($is, 'MORNING', 1, '08:00:00', '12:00:00');

    (new UpdateOperationalPeriod)->execute($period, new UpdateOperationalPeriodData(
        startsAt: '07:30:00',
        endsAt: '11:30:00',
    ));

    expect($period->starts_at)->toBe('07:30:00')
        ->and($period->ends_at)->toBe('11:30:00');
});

it('rejects time update that causes overlap with active sibling', function (): void {
    $is = draftIs();
    $morning = addPeriod($is, 'MORNING', 1, '08:00:00', '12:00:00');
    addPeriod($is, 'AFTERNOON', 2, '13:00:00', '17:00:00');

    expect(fn () => (new UpdateOperationalPeriod)->execute(
        $morning,
        new UpdateOperationalPeriodData(endsAt: '14:00:00')
    ))->toThrow(RuntimeException::class);
});

it('rejects update when institution semester is open', function (): void {
    $is = InstitutionSemesterFactory::new()->open()->create();
    $period = OperationalPeriodFactory::new()->forInstitutionSemester($is)->create();

    expect(fn () => (new UpdateOperationalPeriod)->execute(
        $period,
        new UpdateOperationalPeriodData(nameEn: 'Blocked')
    ))->toThrow(RuntimeException::class);
});

it('rejects update on a deactivated period', function (): void {
    $is = draftIs();
    $period = addPeriod($is, 'MORNING', 1, '08:00:00', '12:00:00');
    (new DeactivateOperationalPeriod)->execute($period);

    expect(fn () => (new UpdateOperationalPeriod)->execute(
        $period,
        new UpdateOperationalPeriodData(nameEn: 'Blocked')
    ))->toThrow(RuntimeException::class);
});

// ---------------------------------------------------------------------------
// Deactivation
// ---------------------------------------------------------------------------

it('deactivates an active period in a draft institution semester', function (): void {
    $is = draftIs();
    $period = addPeriod($is, 'MORNING', 1, '08:00:00', '12:00:00');

    (new DeactivateOperationalPeriod)->execute($period);

    expect($period->is_active)->toBeFalse();
});

it('deactivated period remains queryable', function (): void {
    $is = draftIs();
    $period = addPeriod($is, 'MORNING', 1, '08:00:00', '12:00:00');
    (new DeactivateOperationalPeriod)->execute($period);

    $found = OperationalPeriod::find($period->id);

    expect($found)->not->toBeNull()
        ->and($found->is_active)->toBeFalse();
});

it('rejects deactivating an already-deactivated period', function (): void {
    $is = draftIs();
    $period = addPeriod($is, 'MORNING', 1, '08:00:00', '12:00:00');
    (new DeactivateOperationalPeriod)->execute($period);

    expect(fn () => (new DeactivateOperationalPeriod)->execute($period))
        ->toThrow(RuntimeException::class);
});

it('rejects deactivation when institution semester is not draft', function (): void {
    $is = InstitutionSemesterFactory::new()->open()->create();
    $period = OperationalPeriodFactory::new()->forInstitutionSemester($is)->create();

    expect(fn () => (new DeactivateOperationalPeriod)->execute($period))
        ->toThrow(RuntimeException::class);
});

// ---------------------------------------------------------------------------
// Listing
// ---------------------------------------------------------------------------

it('lists all periods for an institution semester ordered by sequence', function (): void {
    $is = draftIs();
    addPeriod($is, 'P3', 3, '13:00:00', '15:00:00');
    addPeriod($is, 'P1', 1, '07:00:00', '09:00:00');
    addPeriod($is, 'P2', 2, '09:00:00', '11:00:00');

    $periods = (new ListOperationalPeriods)->execute($is);

    expect($periods)->toHaveCount(3)
        ->and($periods[0]->sequence)->toBe(1)
        ->and($periods[1]->sequence)->toBe(2)
        ->and($periods[2]->sequence)->toBe(3);
});

it('includes inactive periods in the default listing', function (): void {
    $is = draftIs();
    $morning = addPeriod($is, 'MORNING', 1, '08:00:00', '12:00:00');
    (new DeactivateOperationalPeriod)->execute($morning);
    addPeriod($is, 'AFTERNOON', 2, '13:00:00', '17:00:00');

    $all = (new ListOperationalPeriods)->execute($is);
    $activeOnly = (new ListOperationalPeriods)->execute($is, activeOnly: true);

    expect($all)->toHaveCount(2)
        ->and($activeOnly)->toHaveCount(1)
        ->and($activeOnly[0]->code)->toBe('AFTERNOON');
});

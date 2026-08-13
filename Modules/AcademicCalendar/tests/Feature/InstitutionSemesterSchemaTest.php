<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\AcademicCalendar\Database\Factories\AcademicYearFactory;
use Modules\AcademicCalendar\Database\Factories\InstitutionSemesterFactory;
use Modules\AcademicCalendar\Database\Factories\OperationalPeriodFactory;
use Modules\AcademicCalendar\Database\Factories\SemesterFactory;
use Modules\AcademicCalendar\Enums\AcademicStatus;
use Modules\AcademicCalendar\Models\InstitutionSemester;
use Modules\AcademicCalendar\Models\OperationalPeriod;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// institution_semesters schema
// ---------------------------------------------------------------------------

it('institution_semesters table has expected columns', function (): void {
    expect(Schema::hasColumns('institution_semesters', [
        'id', 'institution_id', 'semester_id', 'status', 'copied_from_id',
        'created_at', 'updated_at',
    ]))->toBeTrue();
});

it('institution_semesters has no soft-delete column', function (): void {
    expect(Schema::hasColumn('institution_semesters', 'deleted_at'))->toBeFalse();
});

it('institution_semesters has no actor-audit columns', function (): void {
    expect(Schema::hasColumn('institution_semesters', 'created_by'))->toBeFalse();
    expect(Schema::hasColumn('institution_semesters', 'updated_by'))->toBeFalse();
});

it('institution_id + semester_id combination is unique', function (): void {
    $is = InstitutionSemesterFactory::new()->create();

    expect(fn () => InstitutionSemester::create([
        'institution_id' => $is->institution_id,
        'semester_id' => $is->semester_id,
        'status' => AcademicStatus::Draft->value,
    ]))->toThrow(Exception::class);
});

it('institution_semester FK to institutions restricts delete', function (): void {
    $is = InstitutionSemesterFactory::new()->create();

    $institutionClass = 'Modules\\Organization\\Models\\Institution';
    $institution = $institutionClass::withoutGlobalScopes()->find($is->institution_id);

    expect(fn () => $institution->delete())->toThrow(Exception::class);
});

it('institution_semester FK to semesters restricts delete', function (): void {
    $year = AcademicYearFactory::new()->create([
        'starts_on' => '2027-09-01',
        'ends_on' => '2028-06-30',
    ]);

    $semester = SemesterFactory::new()->create([
        'academic_year_id' => $year->id,
        'starts_on' => '2027-09-01',
        'ends_on' => '2028-06-30',
    ]);

    InstitutionSemesterFactory::new()->forSemester($semester)->create();

    expect(fn () => $semester->delete())->toThrow(Exception::class);
});

it('copied_from_id self-reference restricts delete of source', function (): void {
    $source = InstitutionSemesterFactory::new()->create();
    InstitutionSemesterFactory::new()->copiedFrom($source)->create();

    expect(fn () => $source->delete())->toThrow(Exception::class);
});

// ---------------------------------------------------------------------------
// operational_periods schema
// ---------------------------------------------------------------------------

it('operational_periods table has expected columns', function (): void {
    expect(Schema::hasColumns('operational_periods', [
        'id', 'institution_semester_id', 'code', 'name_en', 'name_ar',
        'sequence', 'starts_at', 'ends_at', 'is_active',
        'created_at', 'updated_at',
    ]))->toBeTrue();
});

it('operational_periods has no soft-delete column', function (): void {
    expect(Schema::hasColumn('operational_periods', 'deleted_at'))->toBeFalse();
});

it('operational_periods code is unique within an institution semester', function (): void {
    $is = InstitutionSemesterFactory::new()->create();

    OperationalPeriodFactory::new()->forInstitutionSemester($is)->withCode('MORNING')->withSequence(1)->create();

    expect(function () use ($is): void {
        $dup = new OperationalPeriod;
        $dup->institution_semester_id = $is->id;
        $dup->code = 'MORNING';
        $dup->name_en = 'Duplicate';
        $dup->sequence = 2;
        $dup->starts_at = '13:00:00';
        $dup->ends_at = '17:00:00';
        $dup->is_active = true;
        $dup->save();
    })->toThrow(Exception::class);
});

it('operational_periods sequence is unique within an institution semester', function (): void {
    $is = InstitutionSemesterFactory::new()->create();

    OperationalPeriodFactory::new()->forInstitutionSemester($is)->withCode('MORNING')->withSequence(1)->create();

    expect(function () use ($is): void {
        $dup = new OperationalPeriod;
        $dup->institution_semester_id = $is->id;
        $dup->code = 'AFTERNOON';
        $dup->name_en = 'Duplicate sequence';
        $dup->sequence = 1;
        $dup->starts_at = '13:00:00';
        $dup->ends_at = '17:00:00';
        $dup->is_active = true;
        $dup->save();
    })->toThrow(Exception::class);
});

it('operational_periods code uniqueness is scoped to the institution semester', function (): void {
    $is1 = InstitutionSemesterFactory::new()->create();
    $is2 = InstitutionSemesterFactory::new()->create();

    OperationalPeriodFactory::new()->forInstitutionSemester($is1)->withCode('MORNING')->withSequence(1)->create();

    // Same code in a different institution semester must be allowed.
    $period = new OperationalPeriod;
    $period->institution_semester_id = $is2->id;
    $period->code = 'MORNING';
    $period->name_en = 'Morning';
    $period->sequence = 1;
    $period->starts_at = '08:00:00';
    $period->ends_at = '12:00:00';
    $period->is_active = true;
    $period->save();

    expect($period->id)->toBeInt();
});

it('operational_periods FK to institution_semesters restricts delete', function (): void {
    $is = InstitutionSemesterFactory::new()->create();
    OperationalPeriodFactory::new()->forInstitutionSemester($is)->create();

    expect(fn () => $is->delete())->toThrow(Exception::class);
});

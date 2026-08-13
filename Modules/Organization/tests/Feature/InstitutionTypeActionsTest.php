<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Organization\Actions\ActivateInstitutionType;
use Modules\Organization\Actions\ChangeInstitutionTypeName;
use Modules\Organization\Actions\CreateInstitutionType;
use Modules\Organization\Actions\DeactivateInstitutionType;
use Modules\Organization\Data\ChangeInstitutionTypeNameData;
use Modules\Organization\Data\CreateInstitutionTypeData;
use Modules\Organization\Database\Factories\InstitutionTypeFactory;
use Modules\Organization\Models\InstitutionType;

uses(RefreshDatabase::class);

it('creates an institution type with a stable code and english name', function (): void {
    $type = (new CreateInstitutionType)->execute(new CreateInstitutionTypeData(
        code: 'test_type',
        nameEn: 'Test Type',
    ));

    expect($type)->toBeInstanceOf(InstitutionType::class)
        ->and($type->exists)->toBeTrue()
        ->and($type->code)->toBe('test_type')
        ->and($type->name_en)->toBe('Test Type')
        ->and($type->name_ar)->toBeNull()
        ->and($type->is_active)->toBeTrue();
});

it('creates an institution type with an optional arabic name', function (): void {
    $type = (new CreateInstitutionType)->execute(new CreateInstitutionTypeData(
        code: 'test_type_ar',
        nameEn: 'Test Type',
        nameAr: 'نوع اختباري',
    ));

    expect($type->name_ar)->toBe('نوع اختباري');
});

it('rejects a duplicate institution-type code', function (): void {
    (new CreateInstitutionType)->execute(new CreateInstitutionTypeData(
        code: 'dup-type',
        nameEn: 'First Type',
    ));

    expect(fn () => (new CreateInstitutionType)->execute(new CreateInstitutionTypeData(
        code: 'dup-type',
        nameEn: 'Second Type',
    )))->toThrow(RuntimeException::class);
});

it('changes an institution type english name without touching the stable code', function (): void {
    $type = InstitutionTypeFactory::new()->create(['code' => 'immutable-type-code']);
    $originalCode = $type->code;

    $updated = (new ChangeInstitutionTypeName)->execute(
        $type,
        new ChangeInstitutionTypeNameData(nameEn: 'Updated Name')
    );

    expect($updated->code)->toBe($originalCode)
        ->and($updated->name_en)->toBe('Updated Name');
});

it('changes an institution type arabic name', function (): void {
    $type = InstitutionTypeFactory::new()->create();

    $updated = (new ChangeInstitutionTypeName)->execute(
        $type,
        new ChangeInstitutionTypeNameData(nameEn: 'Name', nameAr: 'اسم')
    );

    expect($updated->name_ar)->toBe('اسم');
});

it('clears an institution type arabic name when set to null', function (): void {
    $type = InstitutionTypeFactory::new()->withArabicName()->create();

    $updated = (new ChangeInstitutionTypeName)->execute(
        $type,
        new ChangeInstitutionTypeNameData(nameEn: 'Name', nameAr: null)
    );

    expect($updated->name_ar)->toBeNull();
});

it('activates an inactive institution type', function (): void {
    $type = InstitutionTypeFactory::new()->inactive()->create();

    $activated = (new ActivateInstitutionType)->execute($type);

    expect($activated->is_active)->toBeTrue()
        ->and(InstitutionType::find($type->id)?->is_active)->toBeTrue();
});

it('deactivates an active institution type without deleting it', function (): void {
    $type = InstitutionTypeFactory::new()->create();

    $deactivated = (new DeactivateInstitutionType)->execute($type);

    expect($deactivated->is_active)->toBeFalse()
        ->and(InstitutionType::withoutGlobalScopes()->find($type->id))->not->toBeNull()
        ->and(InstitutionType::withoutGlobalScopes()->find($type->id)?->is_active)->toBeFalse();
});

it('preserves a deactivated institution type for historical reference', function (): void {
    $type = InstitutionTypeFactory::new()->create(['code' => 'historical-type']);

    (new DeactivateInstitutionType)->execute($type);

    $found = InstitutionType::where('code', 'historical-type')->first();

    expect($found)->not->toBeNull()
        ->and($found->is_active)->toBeFalse();
});

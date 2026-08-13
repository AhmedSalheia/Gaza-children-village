<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Organization\Actions\ActivateInstitution;
use Modules\Organization\Actions\ChangeInstitutionName;
use Modules\Organization\Actions\CreateInstitution;
use Modules\Organization\Actions\DeactivateInstitution;
use Modules\Organization\Data\ChangeInstitutionNameData;
use Modules\Organization\Data\CreateInstitutionData;
use Modules\Organization\Database\Factories\InstitutionFactory;
use Modules\Organization\Database\Factories\InstitutionTypeFactory;
use Modules\Organization\Database\Factories\OrganizationFactory;
use Modules\Organization\Models\Institution;

uses(RefreshDatabase::class);

it('creates an institution with a stable code and english name', function (): void {
    $org = OrganizationFactory::new()->create();
    $type = InstitutionTypeFactory::new()->create();

    $institution = (new CreateInstitution)->execute(new CreateInstitutionData(
        code: 'test_inst',
        organizationId: $org->id,
        institutionTypeId: $type->id,
        nameEn: 'Test Institution',
    ));

    expect($institution)->toBeInstanceOf(Institution::class)
        ->and($institution->exists)->toBeTrue()
        ->and($institution->code)->toBe('test_inst')
        ->and($institution->organization_id)->toBe($org->id)
        ->and($institution->institution_type_id)->toBe($type->id)
        ->and($institution->name_en)->toBe('Test Institution')
        ->and($institution->name_ar)->toBeNull()
        ->and($institution->is_active)->toBeTrue();
});

it('creates an institution with an optional arabic name', function (): void {
    $org = OrganizationFactory::new()->create();
    $type = InstitutionTypeFactory::new()->create();

    $institution = (new CreateInstitution)->execute(new CreateInstitutionData(
        code: 'test_inst_ar',
        organizationId: $org->id,
        institutionTypeId: $type->id,
        nameEn: 'Test Institution',
        nameAr: 'مؤسسة اختبارية',
    ));

    expect($institution->name_ar)->toBe('مؤسسة اختبارية');
});

it('rejects a duplicate institution code', function (): void {
    $org = OrganizationFactory::new()->create();
    $type = InstitutionTypeFactory::new()->create();

    (new CreateInstitution)->execute(new CreateInstitutionData(
        code: 'dup-inst',
        organizationId: $org->id,
        institutionTypeId: $type->id,
        nameEn: 'First Institution',
    ));

    expect(fn () => (new CreateInstitution)->execute(new CreateInstitutionData(
        code: 'dup-inst',
        organizationId: $org->id,
        institutionTypeId: $type->id,
        nameEn: 'Second Institution',
    )))->toThrow(RuntimeException::class);
});

it('changes an institution english name without touching the stable code', function (): void {
    $institution = InstitutionFactory::new()->create(['code' => 'immutable-inst-code']);
    $originalCode = $institution->code;

    $updated = (new ChangeInstitutionName)->execute(
        $institution,
        new ChangeInstitutionNameData(nameEn: 'Updated Name')
    );

    expect($updated->code)->toBe($originalCode)
        ->and($updated->name_en)->toBe('Updated Name');
});

it('changes an institution arabic name', function (): void {
    $institution = InstitutionFactory::new()->create();

    $updated = (new ChangeInstitutionName)->execute(
        $institution,
        new ChangeInstitutionNameData(nameEn: 'Name', nameAr: 'اسم')
    );

    expect($updated->name_ar)->toBe('اسم');
});

it('clears an institution arabic name when set to null', function (): void {
    $institution = InstitutionFactory::new()->withArabicName()->create();

    $updated = (new ChangeInstitutionName)->execute(
        $institution,
        new ChangeInstitutionNameData(nameEn: 'Name', nameAr: null)
    );

    expect($updated->name_ar)->toBeNull();
});

it('activates an inactive institution', function (): void {
    $institution = InstitutionFactory::new()->inactive()->create();

    $activated = (new ActivateInstitution)->execute($institution);

    expect($activated->is_active)->toBeTrue()
        ->and(Institution::find($institution->id)?->is_active)->toBeTrue();
});

it('deactivates an active institution without deleting it', function (): void {
    $institution = InstitutionFactory::new()->create();

    $deactivated = (new DeactivateInstitution)->execute($institution);

    expect($deactivated->is_active)->toBeFalse()
        ->and(Institution::withoutGlobalScopes()->find($institution->id))->not->toBeNull()
        ->and(Institution::withoutGlobalScopes()->find($institution->id)?->is_active)->toBeFalse();
});

it('preserves a deactivated institution for historical reference', function (): void {
    $institution = InstitutionFactory::new()->create(['code' => 'historical-inst']);

    (new DeactivateInstitution)->execute($institution);

    $found = Institution::where('code', 'historical-inst')->first();

    expect($found)->not->toBeNull()
        ->and($found->is_active)->toBeFalse();
});

it('resolves the organization relationship', function (): void {
    $org = OrganizationFactory::new()->create(['name_en' => 'Gaza Children Village']);
    $institution = InstitutionFactory::new()->forOrganization($org)->create();

    expect($institution->organization->id)->toBe($org->id)
        ->and($institution->organization->name_en)->toBe('Gaza Children Village');
});

it('resolves the institution type relationship', function (): void {
    $type = InstitutionTypeFactory::new()->create(['code' => 'academy', 'name_en' => 'Academy']);
    $institution = InstitutionFactory::new()->ofType($type)->create();

    expect($institution->institutionType->id)->toBe($type->id)
        ->and($institution->institutionType->code)->toBe('academy');
});

it('scope forOrganization returns only institutions belonging to that organization', function (): void {
    $orgA = OrganizationFactory::new()->create();
    $orgB = OrganizationFactory::new()->create();

    $instA1 = InstitutionFactory::new()->forOrganization($orgA)->create();
    $instA2 = InstitutionFactory::new()->forOrganization($orgA)->create();
    InstitutionFactory::new()->forOrganization($orgB)->create();

    $result = Institution::forOrganization($orgA)->get();

    expect($result)->toHaveCount(2)
        ->and($result->pluck('id')->sort()->values()->all())
        ->toBe(collect([$instA1->id, $instA2->id])->sort()->values()->all());
});

it('scope forOrganization returns an empty collection when the organization has no institutions', function (): void {
    $emptyOrg = OrganizationFactory::new()->create();
    InstitutionFactory::new()->create();

    $result = Institution::forOrganization($emptyOrg)->get();

    expect($result)->toHaveCount(0);
});

it('scope ofType returns only institutions of that type', function (): void {
    $typeA = InstitutionTypeFactory::new()->create();
    $typeB = InstitutionTypeFactory::new()->create();

    $instA1 = InstitutionFactory::new()->ofType($typeA)->create();
    $instA2 = InstitutionFactory::new()->ofType($typeA)->create();
    InstitutionFactory::new()->ofType($typeB)->create();

    $result = Institution::ofType($typeA)->get();

    expect($result)->toHaveCount(2)
        ->and($result->pluck('id')->sort()->values()->all())
        ->toBe(collect([$instA1->id, $instA2->id])->sort()->values()->all());
});

it('scope ofType returns an empty collection when no institutions have that type', function (): void {
    $unusedType = InstitutionTypeFactory::new()->create();
    InstitutionFactory::new()->create();

    $result = Institution::ofType($unusedType)->get();

    expect($result)->toHaveCount(0);
});

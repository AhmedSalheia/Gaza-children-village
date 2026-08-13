<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Organization\Actions\ActivateOrganization;
use Modules\Organization\Actions\ChangeOrganizationName;
use Modules\Organization\Actions\CreateOrganization;
use Modules\Organization\Actions\DeactivateOrganization;
use Modules\Organization\Data\ChangeOrganizationNameData;
use Modules\Organization\Data\CreateOrganizationData;
use Modules\Organization\Database\Factories\OrganizationFactory;
use Modules\Organization\Models\Organization;

uses(RefreshDatabase::class);

it('creates an organization with a stable code and english name', function (): void {
    $action = new CreateOrganization;

    $org = $action->execute(new CreateOrganizationData(
        code: 'test-org',
        nameEn: 'Test Organization',
    ));

    expect($org)->toBeInstanceOf(Organization::class)
        ->and($org->exists)->toBeTrue()
        ->and($org->code)->toBe('test-org')
        ->and($org->name_en)->toBe('Test Organization')
        ->and($org->name_ar)->toBeNull()
        ->and($org->is_active)->toBeTrue();
});

it('creates an organization with an optional arabic name', function (): void {
    $action = new CreateOrganization;

    $org = $action->execute(new CreateOrganizationData(
        code: 'test-org-ar',
        nameEn: 'Test Organization',
        nameAr: 'منظمة اختبارية',
    ));

    expect($org->name_ar)->toBe('منظمة اختبارية');
});

it('rejects a duplicate organization code', function (): void {
    (new CreateOrganization)->execute(new CreateOrganizationData(
        code: 'dup-code',
        nameEn: 'First Organization',
    ));

    expect(fn () => (new CreateOrganization)->execute(new CreateOrganizationData(
        code: 'dup-code',
        nameEn: 'Second Organization',
    )))->toThrow(RuntimeException::class);
});

it('changes an organization english name without touching the stable code', function (): void {
    $org = OrganizationFactory::new()->create(['code' => 'immutable-code']);
    $originalCode = $org->code;

    $updated = (new ChangeOrganizationName)->execute(
        $org,
        new ChangeOrganizationNameData(nameEn: 'Updated Name')
    );

    expect($updated->code)->toBe($originalCode)
        ->and($updated->name_en)->toBe('Updated Name')
        ->and($updated->name_ar)->toBeNull();
});

it('changes an organization arabic name', function (): void {
    $org = OrganizationFactory::new()->create();

    $updated = (new ChangeOrganizationName)->execute(
        $org,
        new ChangeOrganizationNameData(nameEn: 'Name', nameAr: 'اسم')
    );

    expect($updated->name_ar)->toBe('اسم');
});

it('clears an organization arabic name when set to null', function (): void {
    $org = OrganizationFactory::new()->withArabicName()->create();

    $updated = (new ChangeOrganizationName)->execute(
        $org,
        new ChangeOrganizationNameData(nameEn: 'Name', nameAr: null)
    );

    expect($updated->name_ar)->toBeNull();
});

it('activates an inactive organization', function (): void {
    $org = OrganizationFactory::new()->inactive()->create();

    $activated = (new ActivateOrganization)->execute($org);

    expect($activated->is_active)->toBeTrue()
        ->and(Organization::find($org->id)?->is_active)->toBeTrue();
});

it('deactivates an active organization without deleting it', function (): void {
    $org = OrganizationFactory::new()->create();

    $deactivated = (new DeactivateOrganization)->execute($org);

    expect($deactivated->is_active)->toBeFalse()
        ->and(Organization::withoutGlobalScopes()->find($org->id))->not->toBeNull()
        ->and(Organization::withoutGlobalScopes()->find($org->id)?->is_active)->toBeFalse();
});

it('preserves a deactivated organization record for historical reference', function (): void {
    $org = OrganizationFactory::new()->create(['code' => 'historical-org']);

    (new DeactivateOrganization)->execute($org);

    $found = Organization::where('code', 'historical-org')->first();

    expect($found)->not->toBeNull()
        ->and($found->is_active)->toBeFalse();
});

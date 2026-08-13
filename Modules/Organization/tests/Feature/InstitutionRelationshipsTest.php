<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Organization\Database\Factories\InstitutionFactory;
use Modules\Organization\Database\Factories\InstitutionTypeFactory;
use Modules\Organization\Database\Factories\OrganizationFactory;
use Modules\Organization\Models\Institution;
use Modules\Organization\Models\InstitutionType;
use Modules\Organization\Models\Organization;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Organization → institutions()
// ---------------------------------------------------------------------------

it('returns institutions belonging to an organization', function (): void {
    $org = OrganizationFactory::new()->create();
    InstitutionFactory::new()->forOrganization($org)->count(3)->create();

    $institutions = $org->institutions;

    expect($institutions)->toHaveCount(3)
        ->each->toBeInstanceOf(Institution::class);
});

it('does not return institutions from a different organization', function (): void {
    $org1 = OrganizationFactory::new()->create();
    $org2 = OrganizationFactory::new()->create();

    InstitutionFactory::new()->forOrganization($org1)->count(2)->create();
    InstitutionFactory::new()->forOrganization($org2)->count(1)->create();

    expect($org1->institutions)->toHaveCount(2)
        ->and($org2->institutions)->toHaveCount(1);
});

it('eager-loads institutions on organization', function (): void {
    $org = OrganizationFactory::new()->create();
    InstitutionFactory::new()->forOrganization($org)->count(2)->create();

    $loaded = Organization::with('institutions')->find($org->id);

    expect($loaded)->not->toBeNull()
        ->and($loaded->relationLoaded('institutions'))->toBeTrue()
        ->and($loaded->institutions)->toHaveCount(2);
});

it('can chain constraints on organization institutions', function (): void {
    $org = OrganizationFactory::new()->create();
    InstitutionFactory::new()->forOrganization($org)->count(2)->create();
    InstitutionFactory::new()->forOrganization($org)->inactive()->count(1)->create();

    $active = $org->institutions()->where('is_active', true)->get();

    expect($active)->toHaveCount(2)
        ->each->toBeInstanceOf(Institution::class);
});

// ---------------------------------------------------------------------------
// InstitutionType → institutions()
// ---------------------------------------------------------------------------

it('returns institutions of a given type', function (): void {
    $type = InstitutionTypeFactory::new()->create();
    InstitutionFactory::new()->ofType($type)->count(2)->create();

    $institutions = $type->institutions;

    expect($institutions)->toHaveCount(2)
        ->each->toBeInstanceOf(Institution::class);
});

it('does not return institutions from a different type', function (): void {
    $type1 = InstitutionTypeFactory::new()->create();
    $type2 = InstitutionTypeFactory::new()->create();

    InstitutionFactory::new()->ofType($type1)->count(3)->create();
    InstitutionFactory::new()->ofType($type2)->count(1)->create();

    expect($type1->institutions)->toHaveCount(3)
        ->and($type2->institutions)->toHaveCount(1);
});

it('eager-loads institutions on institution type', function (): void {
    $type = InstitutionTypeFactory::new()->create();
    InstitutionFactory::new()->ofType($type)->count(2)->create();

    $loaded = InstitutionType::with('institutions')->find($type->id);

    expect($loaded)->not->toBeNull()
        ->and($loaded->relationLoaded('institutions'))->toBeTrue()
        ->and($loaded->institutions)->toHaveCount(2);
});

it('can chain constraints on type institutions', function (): void {
    $type = InstitutionTypeFactory::new()->create();
    InstitutionFactory::new()->ofType($type)->count(3)->create();
    InstitutionFactory::new()->ofType($type)->inactive()->count(1)->create();

    $active = $type->institutions()->where('is_active', true)->get();

    expect($active)->toHaveCount(3)
        ->each->toBeInstanceOf(Institution::class);
});

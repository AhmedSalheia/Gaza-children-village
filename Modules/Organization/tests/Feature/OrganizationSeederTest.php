<?php

declare(strict_types=1);

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Organization\Database\Factories\OrganizationFactory;
use Modules\Organization\Database\Seeders\OrganizationReferenceSeeder;
use Modules\Organization\Models\Organization;

uses(RefreshDatabase::class);

it('seeds the gcv organization idempotently', function (): void {
    (new OrganizationReferenceSeeder)->run();
    (new OrganizationReferenceSeeder)->run();

    expect(Organization::where('code', 'gcv')->count())->toBe(1);
});

it('seeds the gcv organization with the approved english name', function (): void {
    (new OrganizationReferenceSeeder)->run();

    $gcv = Organization::where('code', 'gcv')->firstOrFail();

    expect($gcv->name_en)->toBe('Gaza Children Village')
        ->and($gcv->is_active)->toBeTrue();
});

it('seeds the gcv record with the stakeholder-approved arabic name', function (): void {
    (new OrganizationReferenceSeeder)->run();

    $gcv = Organization::where('code', 'gcv')->firstOrFail();

    expect($gcv->name_ar)->toBe('قرية أطفال غزة');
});

it('does not overwrite administrator-edited display names on repeated seeding', function (): void {
    (new OrganizationReferenceSeeder)->run();

    Organization::where('code', 'gcv')->update([
        'name_en' => 'Custom English Name',
        'name_ar' => 'اسم عربي مخصص',
    ]);

    (new OrganizationReferenceSeeder)->run();

    $gcv = Organization::where('code', 'gcv')->firstOrFail();

    expect($gcv->name_en)->toBe('Custom English Name')
        ->and($gcv->name_ar)->toBe('اسم عربي مخصص');
});

it('does not overwrite administrator-edited lifecycle state on repeated seeding', function (): void {
    (new OrganizationReferenceSeeder)->run();

    Organization::where('code', 'gcv')->update(['is_active' => false]);

    (new OrganizationReferenceSeeder)->run();

    $gcv = Organization::where('code', 'gcv')->firstOrFail();

    expect($gcv->is_active)->toBeFalse();
});

it('permits a future second organization to be represented', function (): void {
    (new OrganizationReferenceSeeder)->run();

    $second = OrganizationFactory::new()->create([
        'code' => 'other-org',
        'name_en' => 'Another Organization (Test)',
    ]);

    expect(Organization::count())->toBe(2)
        ->and($second->code)->toBe('other-org');
});

it('rejects a duplicate organization code', function (): void {
    (new OrganizationReferenceSeeder)->run();

    expect(fn () => OrganizationFactory::new()->create(['code' => 'gcv']))
        ->toThrow(UniqueConstraintViolationException::class);
});

it('makes inactive organizations remain queryable', function (): void {
    (new OrganizationReferenceSeeder)->run();

    Organization::where('code', 'gcv')->update(['is_active' => false]);

    $inactive = Organization::where('code', 'gcv')->where('is_active', false)->first();

    expect($inactive)->not->toBeNull()
        ->and($inactive->code)->toBe('gcv');
});

<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Organization\Database\Seeders\InstitutionReferenceSeeder;
use Modules\Organization\Database\Seeders\InstitutionTypeReferenceSeeder;
use Modules\Organization\Database\Seeders\OrganizationReferenceSeeder;
use Modules\Organization\Models\Institution;
use Modules\Organization\Models\InstitutionType;
use Modules\Organization\Models\Organization;

uses(RefreshDatabase::class);

/**
 * Run the prerequisite seeders then the institution seeder.
 */
function seedInstitutions(): void
{
    (new OrganizationReferenceSeeder)->run();
    (new InstitutionTypeReferenceSeeder)->run();
    (new InstitutionReferenceSeeder)->run();
}

it('seeds all 19 known GCV institutions', function (): void {
    seedInstitutions();

    expect(Institution::count())->toBe(19);
});

it('seeds 8 academies of hope', function (): void {
    seedInstitutions();

    $type = InstitutionType::where('code', 'academy')->first();
    expect(Institution::where('institution_type_id', $type->id)->count())->toBe(8);
});

it('seeds 2 university spaces', function (): void {
    seedInstitutions();

    $type = InstitutionType::where('code', 'university_space')->first();
    expect(Institution::where('institution_type_id', $type->id)->count())->toBe(2);
});

it('seeds 2 medical points', function (): void {
    seedInstitutions();

    $type = InstitutionType::where('code', 'medical_point')->first();
    expect(Institution::where('institution_type_id', $type->id)->count())->toBe(2);
});

it('seeds 2 womens centers', function (): void {
    seedInstitutions();

    $type = InstitutionType::where('code', 'womens_center')->first();
    expect(Institution::where('institution_type_id', $type->id)->count())->toBe(2);
});

it('seeds 5 storage units', function (): void {
    seedInstitutions();

    $type = InstitutionType::where('code', 'storage_unit')->first();
    expect(Institution::where('institution_type_id', $type->id)->count())->toBe(5);
});

it('seeds all institutions under the gcv organization', function (): void {
    seedInstitutions();

    $org = Organization::where('code', 'gcv')->first();
    expect(Institution::where('organization_id', $org->id)->count())->toBe(19);
});

it('seeds institutions with null arabic names', function (): void {
    seedInstitutions();

    expect(Institution::whereNotNull('name_ar')->count())->toBe(0);
});

it('seeds institutions as active by default', function (): void {
    seedInstitutions();

    expect(Institution::where('is_active', false)->count())->toBe(0);
});

it('is idempotent when run multiple times', function (): void {
    seedInstitutions();
    seedInstitutions();

    expect(Institution::count())->toBe(19);
});

it('preserves an administrator-edited name on subsequent runs', function (): void {
    seedInstitutions();

    Institution::where('code', 'academy_1')->update(['name_en' => 'Custom Academy Name']);

    (new InstitutionReferenceSeeder)->run();

    $inst = Institution::where('code', 'academy_1')->first();
    expect($inst->name_en)->toBe('Custom Academy Name');
});

it('preserves lifecycle state on subsequent runs', function (): void {
    seedInstitutions();

    Institution::where('code', 'storage_unit_1')->update(['is_active' => false]);

    (new InstitutionReferenceSeeder)->run();

    $inst = Institution::where('code', 'storage_unit_1')->first();
    expect($inst->is_active)->toBeFalse();
});

it('remains queryable after deactivation', function (): void {
    seedInstitutions();

    Institution::where('code', 'medical_point_1')->update(['is_active' => false]);

    $found = Institution::where('code', 'medical_point_1')->first();
    expect($found)->not->toBeNull()
        ->and($found->is_active)->toBeFalse();
});

it('does nothing when gcv organization does not exist', function (): void {
    (new InstitutionTypeReferenceSeeder)->run();
    (new InstitutionReferenceSeeder)->run();

    expect(Institution::count())->toBe(0);
});

it('skips an institution when its type does not exist', function (): void {
    (new OrganizationReferenceSeeder)->run();
    // Only seed 'academy' type; other types are missing
    $org = Organization::where('code', 'gcv')->first();
    $type = new InstitutionType;
    $type->code = 'academy';
    $type->name_en = 'Academy';
    $type->is_active = true;
    $type->save();

    (new InstitutionReferenceSeeder)->run();

    expect(Institution::count())->toBe(8);
});

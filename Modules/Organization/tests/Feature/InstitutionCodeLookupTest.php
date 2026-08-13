<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Organization\Database\Factories\InstitutionFactory;
use Modules\Organization\Models\Institution;

uses(RefreshDatabase::class);

it('withCode scope returns the matching institution', function (): void {
    InstitutionFactory::new()->create(['code' => 'gcv_academy_1']);
    InstitutionFactory::new()->create(['code' => 'gcv_academy_2']);

    $found = Institution::withCode('gcv_academy_1')->first();

    expect($found)->not->toBeNull()
        ->and($found->code)->toBe('gcv_academy_1');
});

it('withCode scope returns null via first() when no institution matches', function (): void {
    InstitutionFactory::new()->create(['code' => 'existing_inst']);

    $result = Institution::withCode('nonexistent_code')->first();

    expect($result)->toBeNull();
});

it('withCode scope throws via firstOrFail() when no institution matches', function (): void {
    expect(fn () => Institution::withCode('nonexistent_code')->firstOrFail())
        ->toThrow(ModelNotFoundException::class);
});

it('withCode scope is composable with other scopes', function (): void {
    $inst = InstitutionFactory::new()->create(['code' => 'gcv_med_1', 'is_active' => true]);
    InstitutionFactory::new()->create(['code' => 'gcv_med_2', 'is_active' => false]);

    $found = Institution::withCode('gcv_med_1')
        ->where('is_active', true)
        ->first();

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($inst->id);
});

it('withCode scope returns only the institution with the exact code', function (): void {
    InstitutionFactory::new()->create(['code' => 'academy']);
    InstitutionFactory::new()->create(['code' => 'academy_1']);
    InstitutionFactory::new()->create(['code' => 'gcv_academy']);

    $result = Institution::withCode('academy')->get();

    expect($result)->toHaveCount(1)
        ->and($result->first()->code)->toBe('academy');
});

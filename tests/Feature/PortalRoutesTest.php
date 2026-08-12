<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

it('registers isolated placeholder routes for each portal', function (string $portal, string $uri): void {
    $route = Route::getRoutes()->getByName($portal.'.placeholder');

    expect($route)->not->toBeNull()
        ->and($route?->uri())->toBe($uri)
        ->and($route?->middleware())->toContain('web')
        ->not->toContain('auth');

    $this->get('/'.$uri)->assertNoContent();
})->with([
    'admin portal' => ['admin', 'admin'],
    'staff portal' => ['staff', 'staff'],
    'guardian portal' => ['guardian', 'guardian'],
]);

it('does not share portal route names or uris', function (): void {
    $routes = collect(['admin', 'staff', 'guardian'])
        ->map(fn (string $portal) => Route::getRoutes()->getByName($portal.'.placeholder'));

    expect($routes->pluck('uri')->unique())->toHaveCount(3)
        ->and($routes->pluck('action.as')->unique())->toHaveCount(3);
});

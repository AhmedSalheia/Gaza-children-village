<?php

declare(strict_types=1);

use Nwidart\Modules\Contracts\RepositoryInterface;

it('discovers exactly the registered module shells', function (): void {
    $expected = config('module-boundaries.registered_modules');
    $discovered = collect(app(RepositoryInterface::class)->all())
        ->map(fn ($module) => $module->getName())
        ->values()
        ->all();

    sort($expected);
    sort($discovered);

    expect($discovered)->toBe($expected);
});

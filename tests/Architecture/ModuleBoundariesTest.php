<?php

declare(strict_types=1);

require_once __DIR__.'/ModuleBoundaryScanner.php';

it('keeps domain models out of the root App Models namespace', function (): void {
    $rootModels = collect(glob(app_path('Models/*.php')) ?: [])
        ->map(fn (string $path) => basename($path))
        ->reject(fn (string $file) => $file === 'User.php');

    expect($rootModels)->toBeEmpty();

    foreach (modulePhpFiles() as $path) {
        expect(file_get_contents($path))->not->toContain('App\\Models\\');
    }
});

it('prevents modules from depending on portal controllers or ui components', function (): void {
    $forbidden = [
        'App\\Http\\Admin\\',
        'App\\Http\\Staff\\',
        'App\\Http\\Guardian\\',
        'App\\Livewire\\',
        'App\\View\\Components\\',
    ];

    foreach (modulePhpFiles() as $path) {
        $contents = file_get_contents($path);

        foreach ($forbidden as $namespace) {
            expect($contents)->not->toContain($namespace);
        }
    }
});

it('allows cross-module references only through approved public surfaces and directions', function (): void {
    $boundaries = config('module-boundaries');

    expect($boundaries['dependencies'])->toHaveCount(7)
        ->and($boundaries['public_namespaces'])->not->toBeEmpty();

    foreach (modulePhpFiles() as $path) {
        $source = moduleNameFromPath($path);
        $contents = file_get_contents($path);
        preg_match_all('/(?<![A-Za-z0-9_\\\\])Modules\\\\([A-Z][A-Za-z0-9]*)\\\\([A-Z][A-Za-z0-9]*)/', $contents, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            [$reference, $target, $surface] = $match;

            if ($target === $source) {
                continue;
            }

            expect(in_array($target, $boundaries['dependencies'][$source] ?? []))
                ->toBeTrue("$source may not depend on $target in $path");

            expect(in_array($surface, $boundaries['public_namespaces']))
                ->toBeTrue("$reference is not a public cross-module namespace");
        }
    }
});

it('prevents modules from reaching into another module internal http layer', function (): void {
    expect(modulePhpFiles())->not->toBeEmpty();

    foreach (modulePhpFiles() as $path) {
        $source = moduleNameFromPath($path);
        $contents = file_get_contents($path);

        preg_match_all('/(?<![A-Za-z0-9_\\\\])Modules\\\\([A-Z][A-Za-z0-9]*)\\\\(?:app\\\\)?Http\\\\/', $contents, $matches);

        foreach ($matches[1] ?? [] as $target) {
            expect($target)->toBe($source, "$source reaches into $target's internal HTTP layer in $path");
        }
    }
});

it('contains no later-release module directories', function (): void {
    $expected = config('module-boundaries.foundation_modules');
    $actual = collect(glob(base_path('Modules/*'), GLOB_ONLYDIR) ?: [])
        ->map(fn (string $path) => basename($path))
        ->values()
        ->all();

    sort($expected);
    sort($actual);

    expect($actual)->toBe($expected);
});

it('does not use the obsolete root Modules composer autoload mapping', function (): void {
    $composer = json_decode(file_get_contents(base_path('composer.json')), true, flags: JSON_THROW_ON_ERROR);

    expect($composer['autoload']['psr-4'])->not->toHaveKey('Modules\\')
        ->and($composer['extra']['merge-plugin']['include'])
        ->toContain('Modules/*/composer.json');
});

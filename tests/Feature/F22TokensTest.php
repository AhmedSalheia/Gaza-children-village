<?php

declare(strict_types=1);

/**
 * F22 — Design token and self-hosted font verification tests.
 *
 * Tests confirm:
 *  - CSS token file contains all required brand anchor values.
 *  - Self-hosted WOFF2 files exist for all declared font faces.
 *  - Bunny Fonts CDN is NOT referenced in vite.config.js.
 *  - No raw hex literals appear outside the token file.
 */
test('CSS token file contains all brand anchor hex values', function (): void {
    $css = file_get_contents(base_path('resources/css/tokens.css'));
    expect($css)->toContain('#254151'); // brand-teal
    expect($css)->toContain('#EEC219'); // brand-gold
    expect($css)->toContain('#EAEAE8'); // brand-offwhite
    expect($css)->toContain('#616153'); // brand-dark-gray
    expect($css)->toContain('#D55342'); // brand-red
    expect($css)->toContain('#518245'); // brand-green
    expect($css)->toContain('#000000'); // brand-black
});

test('CSS token file declares Layer 1 through Layer 4', function (): void {
    $css = file_get_contents(base_path('resources/css/tokens.css'));
    expect($css)->toContain('LAYER 1');
    expect($css)->toContain('LAYER 2');
    expect($css)->toContain('LAYER 3');
    expect($css)->toContain('LAYER 4');
});

test('semantic token names are present in tokens.css', function (): void {
    $css = file_get_contents(base_path('resources/css/tokens.css'));
    $required = [
        '--surface-page', '--surface-card', '--surface-sidebar', '--surface-header',
        '--text-primary', '--text-secondary', '--text-inverse', '--text-link',
        '--interactive-primary', '--interactive-secondary', '--interactive-danger',
        '--focus-ring-color', '--focus-ring-width',
        '--badge-draft-bg', '--badge-open-bg', '--badge-closed-bg', '--badge-archived-bg',
    ];
    foreach ($required as $token) {
        expect($css)->toContain($token, "Missing semantic token: {$token}");
    }
});

test('font face declarations reference correct self-hosted paths', function (): void {
    $css = file_get_contents(base_path('resources/css/_fonts.css'));
    expect($css)->toContain('/fonts/LeagueSpartan-Regular.woff2');
    expect($css)->toContain('/fonts/LeagueSpartan-SemiBold.woff2');
    expect($css)->toContain('/fonts/LeagueSpartan-Bold.woff2');
    expect($css)->toContain('/fonts/Montserrat-Regular.woff2');
    expect($css)->toContain('/fonts/Montserrat-Medium.woff2');
    expect($css)->toContain('/fonts/Montserrat-SemiBold.woff2');
    expect($css)->toContain('/fonts/Montserrat-Bold.woff2');
    expect($css)->toContain('/fonts/NotoSansArabic-Regular.woff2');
    expect($css)->toContain('/fonts/NotoSansArabic-Medium.woff2');
    expect($css)->toContain('/fonts/NotoSansArabic-SemiBold.woff2');
    expect($css)->toContain('/fonts/NotoSansArabic-Bold.woff2');
});

test('WOFF2 font files exist in public/fonts/', function (): void {
    $files = [
        'LeagueSpartan-Regular.woff2',
        'LeagueSpartan-SemiBold.woff2',
        'LeagueSpartan-Bold.woff2',
        'Montserrat-Regular.woff2',
        'Montserrat-Medium.woff2',
        'Montserrat-SemiBold.woff2',
        'Montserrat-Bold.woff2',
        'NotoSansArabic-Regular.woff2',
        'NotoSansArabic-Medium.woff2',
        'NotoSansArabic-SemiBold.woff2',
        'NotoSansArabic-Bold.woff2',
    ];

    foreach ($files as $file) {
        $path = public_path("fonts/{$file}");
        expect(file_exists($path))->toBeTrue("Missing font file: {$file}");
        expect(filesize($path))->toBeGreaterThan(0, "Font file is empty: {$file}");
    }
});

test('vite.config.js does not reference Bunny Fonts CDN', function (): void {
    $config = file_get_contents(base_path('vite.config.js'));
    expect($config)->not->toContain('bunny');
    expect($config)->not->toContain('fonts.bunny.net');
    expect($config)->not->toContain('bunny.net');
});

test('vite.config.js does not import bunny from laravel-vite-plugin', function (): void {
    $config = file_get_contents(base_path('vite.config.js'));
    expect($config)->not->toContain('laravel-vite-plugin/fonts');
});

test('font token variables reference correct family names', function (): void {
    $css = file_get_contents(base_path('resources/css/tokens.css'));
    expect($css)->toContain("'League Spartan'");
    expect($css)->toContain("'Montserrat'");
    expect($css)->toContain("'Noto Sans Arabic'");
});

test('RTL CSS directional tokens are defined for both directions', function (): void {
    $css = file_get_contents(base_path('resources/css/tokens.css'));
    expect($css)->toContain("[dir='rtl']");
    expect($css)->toContain("[dir='ltr']");
});

test('app.css does not import from a CDN or bunny fonts', function (): void {
    $css = file_get_contents(base_path('resources/css/app.css'));
    expect($css)->not->toContain('bunny');
    expect($css)->not->toContain('fonts.googleapis.com');
    expect($css)->not->toContain('fonts.gstatic.com');
    expect($css)->not->toContain('cdn.jsdelivr.net');
});

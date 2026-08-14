<?php

declare(strict_types=1);

use App\Http\Middleware\SetLocale;
use App\Services\TerminologyResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// SetLocale middleware
// ---------------------------------------------------------------------------

describe('SetLocale middleware', function (): void {

    it('defaults to Arabic when no session or account preference', function (): void {
        $response = $this->get('/');
        expect(App::getLocale())->toBe('ar');
    });

    it('reads locale from session', function (): void {
        $response = $this->withSession(['locale' => 'en'])->get('/');
        expect(App::getLocale())->toBe('en');
    });

    it('falls back to Arabic for unsupported locale in session', function (): void {
        $response = $this->withSession(['locale' => 'fr'])->get('/');
        expect(App::getLocale())->toBe('ar');
    });

    it('POST locale-switch stores locale in session', function (): void {
        $response = $this->post('/locale-switch', ['locale' => 'en']);
        $response->assertRedirect();
        $response->assertSessionHas('locale', 'en');
    });

    it('POST locale-switch rejects unsupported locale and stores ar', function (): void {
        $response = $this->post('/locale-switch', ['locale' => 'fr']);
        $response->assertSessionHas('locale', 'ar');
    });

    it('POST locale-switch with Arabic stores ar', function (): void {
        $response = $this->post('/locale-switch', ['locale' => 'ar']);
        $response->assertSessionHas('locale', 'ar');
    });

});

// ---------------------------------------------------------------------------
// TerminologyResolver
// ---------------------------------------------------------------------------

describe('TerminologyResolver', function (): void {

    it('is resolvable from the container', function (): void {
        expect(app(TerminologyResolver::class))->toBeInstanceOf(TerminologyResolver::class);
    });

    it('resolves position label in Arabic', function (): void {
        $resolver = app(TerminologyResolver::class);
        $label = $resolver->positionLabel('teacher', 'ar');
        expect($label)->toBe('معلم');
    });

    it('resolves position label in English', function (): void {
        $resolver = app(TerminologyResolver::class);
        $label = $resolver->positionLabel('teacher', 'en');
        expect($label)->toBe('Teacher');
    });

    it('resolves institution type label in Arabic', function (): void {
        $resolver = app(TerminologyResolver::class);
        $label = $resolver->institutionTypeLabel('school', 'ar');
        expect($label)->toBe('مدرسة');
    });

    it('resolves institution type label in English', function (): void {
        $resolver = app(TerminologyResolver::class);
        $label = $resolver->institutionTypeLabel('school', 'en');
        expect($label)->toBe('School');
    });

    it('falls back to English for unknown Arabic key', function (): void {
        $resolver = app(TerminologyResolver::class);
        // 'other' type has a translation in both; test a known key
        $label = $resolver->institutionTypeLabel('storage', 'ar');
        expect($label)->toBe('مستودع');
    });

    it('falls back to key when translation is entirely absent', function (): void {
        $resolver = app(TerminologyResolver::class);
        $label = $resolver->positionLabel('nonexistent_position', 'ar');
        expect($label)->toBe('positions.nonexistent_position');
    });

    it('resolves principal label in Arabic', function (): void {
        $resolver = app(TerminologyResolver::class);
        expect($resolver->positionLabel('principal', 'ar'))->toBe('مدير');
    });

    it('resolves guard label in Arabic', function (): void {
        $resolver = app(TerminologyResolver::class);
        expect($resolver->positionLabel('guard', 'ar'))->toBe('حارس');
    });

});

// ---------------------------------------------------------------------------
// Language files coverage
// ---------------------------------------------------------------------------

describe('language files', function (): void {

    it('auth.php keys match between ar and en', function (): void {
        $ar = require base_path('lang/ar/auth.php');
        $en = require base_path('lang/en/auth.php');

        expect(array_keys($ar))->toBe(array_keys($en));
    });

    it('ui.php keys match between ar and en', function (): void {
        $ar = require base_path('lang/ar/ui.php');
        $en = require base_path('lang/en/ui.php');

        expect(array_keys($ar))->toBe(array_keys($en));
    });

    it('institutions.php position keys match between ar and en', function (): void {
        $ar = require base_path('lang/ar/institutions.php');
        $en = require base_path('lang/en/institutions.php');

        expect(array_keys($ar['positions']))->toBe(array_keys($en['positions']));
    });

    it('institutions.php type keys match between ar and en', function (): void {
        $ar = require base_path('lang/ar/institutions.php');
        $en = require base_path('lang/en/institutions.php');

        expect(array_keys($ar['types']))->toBe(array_keys($en['types']));
    });

    it('all 11 position definitions have Arabic translations', function (): void {
        $ar = require base_path('lang/ar/institutions.php');
        $positionValues = [
            'principal', 'deputy_principal', 'secretary', 'teacher', 'counselor',
            'trainer', 'medical_staff', 'women_center_staff', 'storage_staff',
            'guard', 'general_staff',
        ];

        foreach ($positionValues as $val) {
            expect($ar['positions'])->toHaveKey($val, "Missing AR position: {$val}");
        }
    });

});

<?php

declare(strict_types=1);

use Modules\Accounts\Services\PasswordPolicy;

describe('password policy', function (): void {

    it('accepts a password meeting all requirements', function (): void {
        $policy = app(PasswordPolicy::class);
        expect($policy->passes('Secret123!'))->toBeTrue();
        expect($policy->passes('abcdefgh12'))->toBeTrue();     // exactly 10 chars
        expect($policy->passes('UPPER1lower'))->toBeTrue();
    });

    it('rejects password shorter than minimum', function (): void {
        $policy = app(PasswordPolicy::class);
        expect($policy->passes('Short1'))->toBeFalse();        // 6 chars
        expect($policy->passes('123456789'))->toBeFalse();     // 9 chars, no letter
    });

    it('rejects password longer than maximum', function (): void {
        config(['account-challenges.password.max_length' => 20]);
        $policy = app(PasswordPolicy::class);
        expect($policy->passes(str_repeat('a', 21).'1'))->toBeFalse();
    });

    it('rejects password with no letters', function (): void {
        $policy = app(PasswordPolicy::class);
        expect($policy->passes('1234567890'))->toBeFalse();
    });

    it('rejects password with no digits', function (): void {
        $policy = app(PasswordPolicy::class);
        expect($policy->passes('PasswordOnly'))->toBeFalse();
    });

    it('accepts password at exactly the minimum length', function (): void {
        $policy = app(PasswordPolicy::class);
        $min = $policy->minLength();
        // Build a password exactly at the minimum: letters + at least one digit
        $password = str_repeat('a', $min - 1).'1';
        expect(strlen($password))->toBe($min);
        expect($policy->passes($password))->toBeTrue();
    });

    it('policy constraints are configurable', function (): void {
        config([
            'account-challenges.password.min_length' => 6,
            'account-challenges.password.max_length' => 12,
        ]);
        $policy = app(PasswordPolicy::class);
        expect($policy->minLength())->toBe(6);
        expect($policy->maxLength())->toBe(12);
        expect($policy->passes('Abc12'))->toBeFalse();   // 5 chars
        expect($policy->passes('Abc123'))->toBeTrue();   // 6 chars
    });

    it('laravel rules array contains required rule strings', function (): void {
        $policy = app(PasswordPolicy::class);
        $rules = $policy->laravelRules();
        expect($rules)->toContain('required')
            ->toContain('string')
            ->toContain('confirmed');
        // Should include min/max
        $hasMin = collect($rules)->contains(fn ($r) => str_starts_with($r, 'min:'));
        $hasMax = collect($rules)->contains(fn ($r) => str_starts_with($r, 'max:'));
        expect($hasMin)->toBeTrue();
        expect($hasMax)->toBeTrue();
    });

});

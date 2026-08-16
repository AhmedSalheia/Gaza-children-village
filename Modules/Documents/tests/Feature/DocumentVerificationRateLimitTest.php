<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

/**
 * Tests that the public verification endpoint applies rate limiting logic.
 *
 * We test the rate limiter configuration rather than hitting the HTTP layer
 * many times (which would be slow and might interfere with global test state).
 */
describe('Document verification rate limiting', function (): void {

    test('rate limiter allows up to 20 attempts per minute', function (): void {
        $key = 'verify:127.0.0.1';

        RateLimiter::clear($key);

        $allowed = 0;

        for ($i = 0; $i < 20; $i++) {
            if (! RateLimiter::tooManyAttempts($key, 20)) {
                RateLimiter::hit($key, 60);
                $allowed++;
            }
        }

        expect($allowed)->toBe(20);

        // Next attempt should be rate limited
        expect(RateLimiter::tooManyAttempts($key, 20))->toBeTrue();

        RateLimiter::clear($key);
    });

    test('verification controller applies rate limiting path', function (): void {
        $key = 'verify:127.0.0.1';

        RateLimiter::clear($key);

        // Exhaust the rate limiter
        for ($i = 0; $i < 21; $i++) {
            RateLimiter::hit($key, 60);
        }

        // Now the endpoint should return rate_limited view
        $unknownCode = str_repeat('a', 64);
        $response = $this->get('/verify/'.$unknownCode);

        $response->assertStatus(200);
        $response->assertSeeText('rate_limit');

        RateLimiter::clear($key);
    });
});

<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Password policy
    |--------------------------------------------------------------------------
    |
    | These constraints are applied when an account sets or resets its password.
    | Passwords must contain at least one letter and one digit in addition to
    | meeting the length limits. Validation input is never logged.
    |
    */

    'password' => [
        'min_length' => (int) env('PASSWORD_MIN_LENGTH', 10),
        'max_length' => (int) env('PASSWORD_MAX_LENGTH', 128),
    ],

    /*
    |--------------------------------------------------------------------------
    | Challenge lifetime and attempt limit
    |--------------------------------------------------------------------------
    |
    | A challenge expires after `lifetime_minutes` from issuance.
    | After `max_attempts` failed verifications the challenge is exhausted and
    | no further attempts are accepted regardless of expiry.
    |
    | `token_bytes` controls how many random bytes are used to generate the
    | plaintext token; the default 32 bytes yields a 64-character hex string.
    |
    */

    'challenge' => [
        'lifetime_minutes' => (int) env('CHALLENGE_LIFETIME_MINUTES', 30),
        'max_attempts' => (int) env('CHALLENGE_MAX_ATTEMPTS', 5),
        'token_bytes' => 32,
    ],

];

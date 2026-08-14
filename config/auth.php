<?php

use App\Models\User;
use Modules\Accounts\Models\AdministrativeAccount;
use Modules\Accounts\Models\GuardianAccount;
use Modules\Accounts\Models\StaffAccount;

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | The default guard is kept as 'web' for framework compatibility.
    | No portal route uses the generic 'web' guard.
    | Portal-specific guards (admin, staff, guardian) protect all portal routes.
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Three portal guards are configured for the three separate authentication
    | experiences. Each guard uses its own lifecycle-aware provider.
    |
    | The generic 'web' guard is retained for framework compatibility but is
    | DEPRECATED as an authentication authority. No protected portal route
    | references it. App\Models\User cannot authenticate through any portal guard.
    |
    */

    'guards' => [
        // DEPRECATED — generic Laravel scaffold. Not used by any portal route.
        // Retained only to avoid breaking the framework session/cookie plumbing
        // that references the default guard. The users table and User model
        // remain for migration compatibility and are not removed or migrated.
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        // Admin Portal guard — authenticates AdministrativeAccount models only.
        'admin' => [
            'driver' => 'session',
            'provider' => 'administrative_accounts',
        ],

        // Staff Portal guard — authenticates StaffAccount models only.
        'staff' => [
            'driver' => 'session',
            'provider' => 'staff_accounts',
        ],

        // Parent/Student Portal guard — authenticates GuardianAccount models only.
        'guardian' => [
            'driver' => 'session',
            'provider' => 'guardian_accounts',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | Three portal providers use the lifecycle-aware 'accounts-eloquent' driver
    | registered by AccountsServiceProvider. Each provider is scoped to exactly
    | one account table; no provider can retrieve another portal's accounts.
    |
    */

    'providers' => [
        // DEPRECATED — generic Laravel scaffold. Not assigned to any portal guard.
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', User::class),
        ],

        // Admin Portal provider — queries administrative_accounts table only.
        'administrative_accounts' => [
            'driver' => 'accounts-eloquent',
            'model' => AdministrativeAccount::class,
        ],

        // Staff Portal provider — queries staff_accounts table only.
        'staff_accounts' => [
            'driver' => 'accounts-eloquent',
            'model' => StaffAccount::class,
        ],

        // Parent/Student Portal provider — queries guardian_accounts table only.
        'guardian_accounts' => [
            'driver' => 'accounts-eloquent',
            'model' => GuardianAccount::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | Three portal password brokers are configured as infrastructure for F11
    | (password setup and recovery). No recovery endpoints or token delivery
    | are implemented in F09. The token tables are created by migration.
    |
    */

    'passwords' => [
        // DEPRECATED — generic Laravel scaffold.
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],

        // Admin Portal password broker (F11 will add recovery endpoints).
        'admin' => [
            'provider' => 'administrative_accounts',
            'table' => 'admin_password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],

        // Staff Portal password broker (F11 will add recovery endpoints).
        'staff' => [
            'provider' => 'staff_accounts',
            'table' => 'staff_password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],

        // Guardian Portal password broker (F11 will add recovery endpoints).
        'guardian' => [
            'provider' => 'guardian_accounts',
            'table' => 'guardian_password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];

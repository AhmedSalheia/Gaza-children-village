<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Portal Authentication Configuration
|--------------------------------------------------------------------------
|
| Configurable thresholds and behaviour for the three portal login flows.
| Defaults are production-safe; override via environment variables in local
| development or in tests (Config::set).
|
| Throttle keys are keyed HMAC fingerprints, never raw identifiers.
| See Modules\Accounts\Actions\BuildLoginThrottleKey.
|
*/

return [

    'throttle' => [

        /*
         * Maximum failed login attempts for a given portal + normalized-identifier
         * fingerprint combination before that specific fingerprint is rate-limited.
         * The counter is cleared on successful login.
         */
        'max_identifier_attempts' => (int) env('LOGIN_MAX_IDENTIFIER_ATTEMPTS', 5),

        /*
         * Maximum failed login attempts for a given portal + IP address combination
         * before requests from that IP are rate-limited for that portal.
         * Successful logins do NOT clear the IP-level counter.
         */
        'max_ip_attempts' => (int) env('LOGIN_MAX_IP_ATTEMPTS', 30),

        /*
         * Rate-limit window in seconds. After this period the counter resets.
         */
        'decay_seconds' => (int) env('LOGIN_THROTTLE_DECAY', 60),

    ],

];

<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Identifier Lookup Key
    |--------------------------------------------------------------------------
    |
    | A dedicated HMAC secret used exclusively for deriving deterministic
    | lookup fingerprints for encrypted PersonIdentifier and ContactPoint
    | values. This key must NOT be the same as APP_KEY.
    |
    | In production, set IDENTIFIER_LOOKUP_KEY to a securely generated random
    | string of at least 32 bytes. If the key is absent in production,
    | fingerprint operations will throw an explicit exception rather than
    | silently falling back to an insecure value.
    |
    | In test environments, a stable test-only value is acceptable.
    |
    */
    'identifier_lookup_key' => env('IDENTIFIER_LOOKUP_KEY'),

];

<?php

declare(strict_types=1);

return [
    /*
     * The database table that holds the Gaza civil registry dataset.
     * Change this if the target schema or table name differs from the default.
     */
    'table' => env('CIVIL_REGISTRY_TABLE', 'gaza_civil_records'),

    /*
     * Streaming chunk size used by the Artisan import command.
     * Keep low enough to avoid OOM on a 1.5M-row dataset.
     */
    'chunk_size' => (int) env('CIVIL_REGISTRY_CHUNK_SIZE', 500),

    /*
     * When false the NullCivilRegistryLookup is always returned, regardless
     * of what is bound in the service provider.  Set to false in testing and
     * to true in production once the dataset is loaded.
     */
    'enabled' => (bool) env('CIVIL_REGISTRY_ENABLED', false),

    /*
     * HMAC key for the civil-registry fingerprint computation.
     * MUST be set via CIVIL_REGISTRY_HMAC_KEY in production.
     * In non-production a stable test fallback is used automatically.
     * Must be different from IDENTIFIER_LOOKUP_KEY and APP_KEY.
     */
    'lookup_hmac_key' => env('CIVIL_REGISTRY_HMAC_KEY'),
];

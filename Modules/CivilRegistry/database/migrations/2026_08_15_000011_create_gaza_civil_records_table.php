<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `gaza_civil_records` table (name configurable via civil-registry.table).
 *
 * This table is loaded from the external Gaza civil-registry dataset and is
 * intentionally NEVER written by the application at runtime. All writes go
 * through the `civil-registry:import` Artisan command.
 *
 * lookup_fingerprint is an HMAC-SHA256 of the normalised 9-digit national_id,
 * keyed by CIVIL_REGISTRY_HMAC_KEY (never plain SHA-256 — 9-digit IDs are
 * fully enumerable against a plain hash). Pre-computed at import time and
 * indexed for constant-time lookup without the raw identifier appearing in
 * query plans or slow-query logs.
 *
 * PRIVACY: No plaintext national_id column is stored. Related-person IDs
 * (father, mother, representative) are stored only as HMAC correlation tokens
 * so they can be compared across registry rows without revealing raw IDs.
 *
 * All demographic columns are nullable because source quality varies.
 *
 * SECURITY: This table must never be FK'd from the People/Students tables.
 * A match is advisory only — it never triggers automatic data writes.
 */
return new class extends Migration
{
    public function up(): void
    {
        $table = config('civil-registry.table', 'gaza_civil_records');

        Schema::create($table, function (Blueprint $table): void {
            $table->id();

            // HMAC-SHA256(normalised_national_id, CIVIL_REGISTRY_HMAC_KEY)
            // Primary lookup index. Unique so upserts can use this as conflict target.
            // No plaintext national_id column — raw IDs are never persisted.
            $table->string('lookup_fingerprint', 64)->unique();

            // Demographic fields — all nullable; source quality varies.
            $table->string('full_name', 255)->nullable();
            $table->string('gender', 16)->nullable();
            $table->string('area', 128)->nullable();
            $table->string('city', 128)->nullable();
            $table->string('street', 255)->nullable();

            // Related-person IDs stored as HMAC correlation tokens only.
            // Allows cross-row correlation without exposing raw national IDs.
            $table->string('father_id_correlation', 64)->nullable();
            $table->string('mother_id_correlation', 64)->nullable();

            $table->date('birth_date')->nullable();
            $table->string('marital_status', 32)->nullable();
            $table->boolean('is_deceased')->nullable()->default(false);
            $table->string('religion', 64)->nullable();
            $table->string('birth_country', 64)->nullable();
            $table->string('representative_id_correlation', 64)->nullable();
            $table->string('representative_relationship', 64)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        $table = config('civil-registry.table', 'gaza_civil_records');
        Schema::dropIfExists($table);
    }
};

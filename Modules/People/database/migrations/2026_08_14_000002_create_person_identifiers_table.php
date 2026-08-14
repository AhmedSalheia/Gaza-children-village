<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `person_identifiers` table.
 *
 * Each row represents one government-issued or institutional identifier
 * linked to a Person. Values are encrypted at rest; lookups use a
 * deterministic HMAC fingerprint derived from the normalized value using
 * the IDENTIFIER_LOOKUP_KEY secret (not APP_KEY).
 *
 * Correction is append-only. Superseded records remain in the table with
 * `is_current = false` so that historical fingerprints stay reserved and
 * re-use is detectable. The unique index covers only current fingerprints
 * to allow historical rows without constraint violation.
 *
 * PostgreSQL note: a partial unique index on (lookup_fingerprint) WHERE is_current = true
 * would be ideal for concurrent-safe uniqueness, but SQLite does not support partial
 * indexes via Laravel's schema builder. We use an application-level check inside a
 * serializable transaction + a standard unique index on lookup_fingerprint to catch
 * most races. On PostgreSQL, add the partial index manually after deployment.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('person_identifiers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();

            // Identifier metadata
            $table->string('type');                          // IdentifierType enum value
            $table->string('country_code', 2)->nullable();  // ISO-3166-1 alpha-2
            $table->string('issuer_name')->nullable();

            // Encrypted storage — never serialized or logged
            $table->text('identifier_encrypted');

            // HMAC-SHA256 of the normalized value, keyed with IDENTIFIER_LOOKUP_KEY.
            // Unique among ALL rows (including superseded) so historical collisions
            // surface for review rather than silently passing.
            $table->string('lookup_fingerprint')->unique();

            // Lifecycle
            $table->boolean('is_current')->default(true);
            $table->foreignId('superseded_by_id')->nullable()->constrained('person_identifiers')->nullOnDelete();
            $table->timestamp('superseded_at')->nullable();

            // Verification
            $table->timestamp('verified_at')->nullable();
            $table->string('verification_source')->nullable();

            // Correction provenance (populated when this record was created to
            // correct/supersede a previous one)
            $table->foreignId('corrects_id')->nullable()->constrained('person_identifiers')->nullOnDelete();
            $table->string('correction_actor')->nullable();
            $table->string('correction_source')->nullable();
            $table->text('correction_reason')->nullable();

            // Effective date range
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_identifiers');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `contact_points` table.
 *
 * A ContactPoint stores one phone or email value linked to a Person.
 * Values are encrypted at rest; lookups use a deterministic HMAC fingerprint
 * keyed with IDENTIFIER_LOOKUP_KEY (same mechanism as PersonIdentifier).
 *
 * Changing a value creates a new row (supersedes the old one); history is preserved.
 * A Person may have multiple contacts of the same type.
 * A phone number may legitimately be shared by family members
 * (no global uniqueness enforced on fingerprint — only per-person per-type de-duplication).
 *
 * Recovery eligibility is separate from verification; explicit marking is required.
 * Unverified or inactive contacts cannot be recovery-eligible.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_points', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();

            $table->string('type');                   // ContactPointType enum: phone | email
            $table->string('ownership');              // ContactOwnership enum: personal | shared_household | organization_managed
            $table->string('lifecycle_state');        // ContactLifecycleState enum: pending | verified | inactive

            // Encrypted value and fingerprint
            $table->text('value_encrypted');          // Never serialized or logged
            $table->string('value_fingerprint');      // HMAC-SHA256 of normalized value

            // Per-person per-type uniqueness of active contacts is enforced at app level.
            // A composite unique index on (person_id, type, value_fingerprint) is NOT added
            // here because historical (superseded/inactive) rows share the same fingerprint.
            // Application logic checks for duplicate active contacts before insert.

            // Verification provenance
            $table->timestamp('verified_at')->nullable();
            $table->string('verification_method')->nullable(); // e.g. 'challenge' | 'manual' | 'admin'
            $table->string('verification_actor')->nullable();  // actor who performed manual verification

            // Recovery eligibility
            $table->boolean('recovery_eligible')->default(false);
            $table->timestamp('recovery_eligible_set_at')->nullable();
            $table->string('recovery_eligible_actor')->nullable();

            // Correction/supersession (append-only correction history)
            $table->boolean('is_current')->default(true);
            $table->foreignId('superseded_by_id')->nullable()->constrained('contact_points')->nullOnDelete();
            $table->timestamp('superseded_at')->nullable();
            $table->foreignId('corrects_id')->nullable()->constrained('contact_points')->nullOnDelete();
            $table->string('correction_actor')->nullable();
            $table->text('correction_reason')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_points');
    }
};

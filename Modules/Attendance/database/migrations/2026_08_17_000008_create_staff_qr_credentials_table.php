<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `staff_qr_credentials` table.
 *
 * One active credential per staff member. Revocation is soft (revoked_at set),
 * so the history of issued credentials is retained for auditing.
 *
 * SECURITY REQUIREMENTS:
 *  - Plaintext token is NEVER stored. Only the HMAC-SHA256 hash is persisted.
 *  - Plaintext is shown once (at generation) and never retrieved from the DB.
 *  - The HMAC uses the application key as the signing secret, so rotating
 *    app.key invalidates all credentials (intentional safety measure).
 *  - token_hash has a unique index so the scan endpoint can look up credentials
 *    in O(1) without iterating all active records.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_qr_credentials', function (Blueprint $table): void {
            $table->id();

            // Cross-module plain int reference (no DB FK)
            $table->unsignedBigInteger('staff_profile_id');

            // HMAC-SHA256 of the plaintext token — unique, deterministic lookup
            $table->string('token_hash', 64)->unique();

            $table->boolean('is_active')->default(true)->index();

            $table->timestamp('issued_at');
            $table->unsignedBigInteger('issued_by_staff_profile_id');

            $table->timestamp('revoked_at')->nullable();
            $table->unsignedBigInteger('revoked_by_staff_profile_id')->nullable();

            $table->timestamps();

            $table->index('staff_profile_id');
            $table->index(['staff_profile_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_qr_credentials');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Account verification challenge log.
 *
 * A challenge is a cryptographically random, short-lived, purpose-bound,
 * account-bound, attempt-limited token used to verify account ownership
 * before password setup or reset. The plaintext token is never stored;
 * only its SHA-256 hash is persisted.
 *
 * Challenge lifecycle:
 *  - Issued: new row created; plaintext delivered to ChallengeDelivery provider.
 *  - Verified: consumed_at set; password change completes.
 *  - Exhausted: attempts >= max_attempts; no further use allowed.
 *  - Expired: expires_at < now; no further use allowed.
 *  - Revoked: revoked_at set; e.g. account state changed or new challenge issued.
 *
 * Privacy: No plaintext tokens, identifiers, or raw contact values are stored.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_verification_challenges', function (Blueprint $table): void {
            $table->id();

            // Portal the challenge belongs to (admin / staff / guardian)
            $table->string('portal', 16)->index();

            // Account reference — not a foreign key to allow cross-table polymorphism
            $table->unsignedBigInteger('account_id')->index();
            $table->string('account_type');   // fully-qualified model class

            // Purpose: 'initial_password_setup' | 'password_reset'
            $table->string('purpose', 32);

            // SHA-256 hex hash of the random plaintext token
            $table->string('token_hash', 64);

            // Attempt tracking
            $table->unsignedTinyInteger('attempts')->default(0);

            // Lifecycle timestamps
            $table->timestamp('expires_at')->index();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('revoked_at')->nullable();

            $table->timestamps();

            // Composite index for active-challenge lookups
            $table->index(['portal', 'account_id', 'account_type', 'purpose'], 'account_verification_challenges_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_verification_challenges');
    }
};

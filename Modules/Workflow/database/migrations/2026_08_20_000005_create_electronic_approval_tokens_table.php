<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `electronic_approval_tokens` table.
 *
 * A token represents a server-side proof that:
 *   1. The actor recently verified their password (via the applicable portal guard).
 *   2. The token is bound to a specific content hash (the canonical subject content
 *      that was displayed to the approver at review-screen load time).
 *   3. The token is single-use and short-lived (TTL ≤ 10 minutes).
 *
 * This table also serves as the rate-limit ledger: `ReconfirmationTokenService`
 * counts tokens issued for an actor within a rolling window and rejects new
 * issuance once the limit is reached.
 *
 * Cross-module references (plain integers — no DB FK):
 *   actor_account_id — Accounts module (disambiguated by actor_type)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('electronic_approval_tokens', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            // Actor identity
            $table->string('actor_type', 32);    // administrative|staff
            $table->string('actor_portal', 32);  // admin|staff
            $table->unsignedBigInteger('actor_account_id');

            // The SHA-256 of the exact subject content shown to the approver.
            // Bound at issuance; verified at consumption.
            $table->char('content_hash', 64);

            // Approval type this token authorises (e.g. 'sensitive_field_correction')
            $table->string('approval_type', 64);

            // Optional: subject reference for additional binding
            $table->string('subject_type', 128)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();

            // Reconfirmation proof
            $table->string('reconfirmation_method', 32)->default('password');

            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Rate-limit index: count unexpired tokens by actor in a time window
            $table->index(['actor_account_id', 'actor_type', 'created_at'], 'eat_actor_rate_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('electronic_approval_tokens');
    }
};

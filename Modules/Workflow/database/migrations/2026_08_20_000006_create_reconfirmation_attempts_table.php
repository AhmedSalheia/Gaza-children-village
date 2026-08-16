<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `reconfirmation_attempts` table.
 *
 * Every reconfirmation attempt — successful or failed — is persisted here.
 * ReconfirmationTokenService checks total attempts in a rolling time window
 * before allowing a new attempt; failed attempts count toward the same limit
 * so that repeated wrong passwords eventually lock the flow for the window.
 *
 * Rows are append-only; no updated_at column.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reconfirmation_attempts', function (Blueprint $table): void {
            $table->id();

            $table->string('actor_type', 32);
            $table->unsignedBigInteger('actor_account_id');
            $table->string('portal', 32);

            $table->boolean('succeeded');

            $table->timestamp('created_at')->useCurrent();

            // Index for rate-limit queries: "count attempts by actor in last N seconds"
            $table->index(['actor_account_id', 'actor_type', 'created_at'], 'ra_actor_window_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconfirmation_attempts');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `reconfirmation_actor_locks` table.
 *
 * Each row is a per-actor sentinel used by ReconfirmationTokenService to
 * serialise the rate-limit check + attempt insertion under a single
 * `SELECT ... FOR UPDATE` lock. One row is upserted per actor on first use.
 *
 * In MySQL this provides a true advisory row-level lock, preventing concurrent
 * requests from the same actor from simultaneously observing a count below the
 * threshold and all proceeding to issue tokens. In SQLite (test environment),
 * writes are serialised at the WAL level so the lock is a no-op but the
 * sequential behaviour is preserved.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reconfirmation_actor_locks', function (Blueprint $table): void {
            $table->id();
            $table->string('actor_type', 32);
            $table->unsignedBigInteger('actor_account_id');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['actor_type', 'actor_account_id'], 'ral_actor_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconfirmation_actor_locks');
    }
};

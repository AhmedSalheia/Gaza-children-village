<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `workflow_actions` table.
 *
 * Append-only: one row per state transition. Rows are never updated or deleted.
 * Together they form the complete, auditable history of each workflow instance.
 *
 * Cross-module column references (plain unsigned integers — no DB FK):
 *   actor_account_id — Accounts module (type in actor_type)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_actions', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('workflow_instance_id')
                ->constrained('workflow_instances')
                ->restrictOnDelete();

            // State transition
            $table->string('previous_state', 64);
            $table->string('new_state', 64);
            $table->string('action_code', 64);

            // Actor who performed the action
            $table->string('actor_type', 32);   // administrative|staff|guardian|system
            $table->string('actor_portal', 32);  // admin|staff|guardian|system
            $table->unsignedBigInteger('actor_account_id')->nullable();

            // Optional decision label (e.g. 'approved'|'rejected') for approval steps
            $table->string('decision', 32)->nullable();

            // Human-readable justification (stored encrypted for sensitive workflows)
            $table->text('comment')->nullable();

            // Non-sensitive supplemental data (e.g. assigned_to, reason_code)
            $table->json('metadata')->nullable();

            // Write-once: only created_at, no updated_at
            $table->timestamp('created_at')->useCurrent();

            // Fast history retrieval
            $table->index('workflow_instance_id', 'wa_instance_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_actions');
    }
};

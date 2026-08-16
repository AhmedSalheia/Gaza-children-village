<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `workflow_instances` table.
 *
 * One instance tracks a single domain subject (e.g. a correction request)
 * through all states of its associated workflow definition.
 *
 * Cross-module column references are plain unsigned integers with no DB-level
 * foreign key constraints (module-boundary contract):
 *   institution_id          — Organization
 *   institution_semester_id — AcademicCalendar
 *   initiating_account_id   — Accounts (type in initiating_actor_type)
 *   subject_id              — any domain model (type in subject_type)
 *   assigned_account_id     — Accounts (type in assigned_actor_type)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_instances', function (Blueprint $table): void {
            $table->id();

            // Definition (module-internal FK — no cross-module boundary here)
            $table->foreignId('workflow_definition_id')
                ->constrained('workflow_definitions')
                ->restrictOnDelete();

            // Polymorphic subject reference
            $table->string('subject_type', 128);
            $table->unsignedBigInteger('subject_id');

            // Current state (matches a value in the definition's transitions)
            $table->string('current_state', 64);

            // Initiating actor (the person who started the workflow)
            $table->string('initiating_actor_type', 32);  // administrative|staff|guardian|system
            $table->string('initiating_actor_portal', 32); // admin|staff|guardian|system
            $table->unsignedBigInteger('initiating_account_id')->nullable();

            // Scope — plain integer references, no DB FK
            $table->unsignedBigInteger('institution_id')->nullable();
            $table->unsignedBigInteger('institution_semester_id')->nullable();

            // Currently assigned actor (who needs to act next)
            $table->string('assigned_actor_type', 32)->nullable();
            $table->unsignedBigInteger('assigned_account_id')->nullable();

            // Optional deadline
            $table->date('due_date')->nullable();

            // Idempotency key: caller-supplied UUID prevents duplicate instances
            $table->uuid('correlation_id')->unique();

            // Set when current_state becomes terminal
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            // Fast lookup by subject
            $table->index(['subject_type', 'subject_id'], 'wi_subject_idx');

            // Fast lookup by institution for admin inboxes
            $table->index('institution_id', 'wi_institution_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_instances');
    }
};

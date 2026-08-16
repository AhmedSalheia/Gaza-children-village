<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `workflow_definitions` table.
 *
 * Code-governed: rows are inserted by WorkflowDefinitionSeeder only.
 * The UI may toggle `is_active` but may never insert or mutate a definition.
 *
 * `transitions` — JSON array of { "from": state, "action": code, "to": state }
 * `terminal_states` — JSON array of state strings that accept no further transitions
 * `initial_state` — the state a new WorkflowInstance starts in
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_definitions', function (Blueprint $table): void {
            $table->id();

            // Stable machine-readable type code (e.g. 'student_correction')
            $table->string('type', 64)->index();

            // Monotonically increasing version within a type
            $table->unsignedSmallInteger('version')->default(1);

            $table->text('description')->nullable();

            // State machine specification (JSON)
            $table->json('transitions');
            $table->json('terminal_states');
            $table->string('initial_state', 64);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Only one active definition per type
            $table->unique(['type', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_definitions');
    }
};

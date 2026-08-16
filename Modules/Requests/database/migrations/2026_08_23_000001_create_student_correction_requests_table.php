<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the student_correction_requests table.
 *
 * Each row is a single-field correction proposal submitted by a guardian,
 * backed by a Workflow module workflow_instance for state tracking.
 *
 * Cross-module integer references (no DB FK constraints):
 *   workflow_instance_id  → workflow_instances.id
 *   student_profile_id    → student_profiles.id
 *   guardian_account_id   → guardian_accounts.id
 *   guardian_profile_id   → guardian_profiles.id
 *   applied_by_account_id → administrative_accounts.id or staff_accounts.id
 *   institution_id        → institutions.id
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_correction_requests', function (Blueprint $table): void {
            $table->id();

            // Cross-module references (plain integers, no FK)
            $table->unsignedBigInteger('workflow_instance_id')->unique();
            $table->unsignedBigInteger('student_profile_id');
            $table->unsignedBigInteger('guardian_account_id');
            $table->unsignedBigInteger('guardian_profile_id');
            $table->unsignedBigInteger('institution_id')->nullable();

            // Which field is being corrected (governed catalogue code)
            $table->string('field_catalogue_code', 64);

            // Standard or sensitive classification (derived from field catalogue at creation)
            $table->string('classification', 20)->default('standard');

            // Conflict detection: set true if official data changed between submission and apply
            $table->boolean('conflict_flag')->default(false);
            $table->text('conflict_reason')->nullable();

            // Application outcome
            $table->timestamp('applied_at')->nullable();
            $table->unsignedBigInteger('applied_by_account_id')->nullable();
            $table->string('applied_by_actor_type', 32)->nullable();

            $table->timestamps();
        });

        Schema::table('student_correction_requests', function (Blueprint $table): void {
            $table->index('student_profile_id');
            $table->index('guardian_account_id');
            $table->index('guardian_profile_id');
            $table->index('institution_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_correction_requests');
    }
};

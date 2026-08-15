<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Homeroom assignments designate a lead or co-lead teacher for a ClassGroup
 * within an InstitutionSemester.
 *
 * A homeroom assignment grants the right to enter student attendance for the
 * assigned class. It does NOT grant marks access — that requires a matching
 * TeachingAssignment.
 *
 * All cross-module column references (staff_profile_id, institution_semester_id,
 * staff_position_id) are plain unsigned integers with no DB-level foreign key
 * constraint, following the module-boundary contract.
 *
 * At most one active lead (is_co_lead = false) per class group per semester.
 * Co-lead assignments (is_co_lead = true) are permitted in multiples.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homeroom_assignments', function (Blueprint $table): void {
            $table->id();

            // Cross-module plain int references (no DB FK — module-boundary contract)
            $table->unsignedBigInteger('staff_profile_id');
            $table->unsignedBigInteger('institution_semester_id');
            $table->unsignedBigInteger('staff_position_id');

            // Intra-module FK constraint
            $table->foreignId('class_group_id')
                ->constrained('class_groups')
                ->restrictOnDelete();

            // Whether this is a co-lead rather than the lead homeroom teacher
            $table->boolean('is_co_lead')->default(false);

            // Effective interval
            $table->date('starts_on');
            $table->date('ends_on')->nullable();

            // Lifecycle: active | ended | superseded
            $table->string('status')->default('active');

            $table->text('ends_reason')->nullable();

            $table->timestamps();

            // Indexes on cross-module references
            $table->index('staff_profile_id');
            $table->index('institution_semester_id');
            $table->index('staff_position_id');
            $table->index(['class_group_id', 'status', 'is_co_lead']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homeroom_assignments');
    }
};

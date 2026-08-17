<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Teaching assignments link an eligible teacher/trainer position to a
 * ClassGroup and InstitutionSubjectOffering within the same InstitutionSemester.
 *
 * All cross-module column references (staff_profile_id, institution_semester_id,
 * staff_position_id) are plain unsigned integers with no DB-level foreign key
 * constraint. This follows the module-boundary contract: modules reference each
 * other through plain IDs; enforcement happens at the application layer.
 *
 * Uniqueness: one active assignment per position + class group + subject offering.
 * Historical assignments remain readable; status tracks active/ended/superseded.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teaching_assignments', function (Blueprint $table): void {
            $table->id();

            // Cross-module plain int references (no DB FK — module-boundary contract)
            $table->unsignedBigInteger('staff_profile_id');
            $table->unsignedBigInteger('institution_semester_id');
            $table->unsignedBigInteger('staff_position_id');

            // Intra-module FK constraints
            $table->foreignId('class_group_id')
                ->constrained('class_groups')
                ->restrictOnDelete();

            $table->foreignId('subject_offering_id')
                ->constrained('institution_subject_offerings')
                ->restrictOnDelete();

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
            $table->index(['class_group_id', 'subject_offering_id', 'status'],'cls_grp_sub_state');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teaching_assignments');
    }
};

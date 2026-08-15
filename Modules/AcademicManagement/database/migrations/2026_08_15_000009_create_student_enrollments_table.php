<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `student_enrollments` table.
 *
 * A StudentEnrollment is the semester-specific operational record connecting a
 * student to an institution, class group, and period for one semester.
 *
 * student_profile_id and institution_semester_id are plain integers — cross-module
 * references (Students and AcademicCalendar modules). No DB-level FK constraints
 * are created for cross-module references; application-layer validation ensures
 * referential integrity.
 *
 * class_group_id is a within-module FK (constrained).
 *
 * The "one active enrollment per student per semester" invariant is enforced
 * at the application layer with lockForUpdate inside a DB transaction.
 *
 * Institution, semester, and academic level are derivable through the
 * class_group → institution_semester chain — not stored redundantly here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_enrollments', function (Blueprint $table): void {
            $table->id();
            // Cross-module plain integer references.
            $table->unsignedBigInteger('student_profile_id');
            $table->unsignedBigInteger('institution_semester_id');
            // Within-module FK.
            $table->foreignId('class_group_id')->constrained('class_groups')->restrictOnDelete();
            $table->string('enrollment_status', 32)->default('draft');
            $table->date('enrolled_on');
            $table->date('activated_on')->nullable();
            $table->date('completed_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Common lookup: student's history in a semester.
            $table->index(['student_profile_id', 'institution_semester_id']);
            // Active enrollment guard query.
            $table->index(['student_profile_id', 'enrollment_status']);
            $table->index('class_group_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_enrollments');
    }
};

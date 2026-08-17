<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `student_attendance_sheets` table.
 *
 * One sheet represents one day of attendance for one class group within one
 * operational period of an institution semester.
 *
 * Lifecycle status: draft → submitted → [returned →] verified → reopened
 *
 * All cross-module column references are plain unsigned integers with no
 * DB-level foreign key constraints, following the module-boundary contract:
 *   institution_semester_id — AcademicCalendar
 *   operational_period_id   — AcademicCalendar
 *   class_group_id          — AcademicManagement
 *   creator_staff_profile_id / verified_by_staff_profile_id — Staff
 *
 * parent_sheet_id is a self-referencing plain integer (not a FK) used to
 * link a reopened correction sheet to its predecessor.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_attendance_sheets', function (Blueprint $table): void {
            $table->id();

            // Cross-module plain int references (no DB FK)
            $table->unsignedBigInteger('institution_semester_id');
            $table->unsignedBigInteger('operational_period_id');
            $table->unsignedBigInteger('class_group_id');

            $table->date('attendance_date');

            // Lifecycle status
            $table->string('status', 32)->default('draft');

            // Secretary return reason (set when status → returned)
            $table->text('return_reason')->nullable();

            // Creator identity
            $table->unsignedBigInteger('creator_staff_profile_id');

            // Submission / verification timestamps + actor
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->unsignedBigInteger('verified_by_staff_profile_id')->nullable();

            // Link to predecessor sheet (for reopened corrections)
            $table->unsignedBigInteger('parent_sheet_id')->nullable();

            $table->timestamps();

            // One active sheet per class group per date (soft-enforced by version)
            // A class may have multiple historical sheets if reopened; the current
            // active one is always the one with the latest created_at that is not
            // in a superseded state. Business logic prevents duplicate drafts.
            $table->index(['class_group_id', 'attendance_date', 'status'],'cls_grp_att_stt');
            $table->index('institution_semester_id');
            $table->index('operational_period_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_attendance_sheets');
    }
};

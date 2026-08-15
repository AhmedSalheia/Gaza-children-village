<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `student_attendance_records` table.
 *
 * One record per student (enrollment) per sheet. Together all records for a
 * sheet represent the class roster with their attendance status for the day.
 *
 * Cross-module plain integer references (no DB FK):
 *   enrollment_id       — AcademicManagement.student_enrollments
 *   student_profile_id  — Students module (denormalized for query efficiency)
 *
 * Correction history is stored immutably: when CorrectVerifiedAttendance runs,
 * the previous status/reason are snapshotted into previous_status_code and
 * previous_reason before the new values are written.
 *
 * source values: teacher_entry | secretary_entry | correction
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_attendance_records', function (Blueprint $table): void {
            $table->id();

            // Owning sheet (within-module FK)
            $table->foreignId('sheet_id')
                ->constrained('student_attendance_sheets')
                ->cascadeOnDelete();

            // Cross-module plain int references (no DB FK)
            $table->unsignedBigInteger('enrollment_id');
            $table->unsignedBigInteger('student_profile_id');

            // Attendance status — see StudentAttendanceStatus catalogue
            // Null means not yet filled (sheet still in draft)
            $table->string('status_code', 32)->nullable();

            // Free-text reason (required for excused_absence; optional for others)
            $table->text('reason')->nullable();

            // Optional time fields (meaningful for late / left_early)
            $table->time('arrived_at')->nullable();
            $table->time('departed_at')->nullable();

            // Note visible to guardian after publication
            $table->text('safe_note')->nullable();

            // Entry source
            $table->string('source', 32)->default('teacher_entry');

            // Correction audit fields — populated by CorrectVerifiedAttendance
            $table->string('previous_status_code', 32)->nullable();
            $table->text('previous_reason')->nullable();
            $table->unsignedBigInteger('corrected_by_staff_profile_id')->nullable();
            $table->timestamp('corrected_at')->nullable();

            $table->timestamps();

            // One record per enrollment per sheet
            $table->unique(['sheet_id', 'enrollment_id']);
            $table->index('enrollment_id');
            $table->index('student_profile_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_attendance_records');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `student_attendance_correction_history` table.
 *
 * Append-only audit log of every post-verification correction applied to an
 * attendance record. Each row captures the record's previous values at the
 * moment of correction, the cycle in which the correction occurred, and the
 * identity of the authorising staff member.
 *
 * Invariant: exactly one row per (attendance_record_id, correction_cycle).
 * CorrectVerifiedAttendance enforces this before inserting.
 *
 * Rows in this table are never updated or deleted.
 *
 * cross-module references:
 *   corrected_by_staff_profile_id — Staff module (plain int, no DB FK)
 *   enrollment_id                 — AcademicManagement (plain int, no DB FK)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_attendance_correction_history', function (Blueprint $table): void {
            $table->id();

            // Within-module FK (cascade delete keeps history consistent with record lifecycle)
            $table->foreignId('attendance_record_id')
                ->constrained('student_attendance_records')
                ->cascadeOnDelete();

            // Denormalized for efficient lookups without joining back to records
            $table->unsignedBigInteger('sheet_id');
            $table->unsignedBigInteger('enrollment_id');

            // Which reopen cycle this correction belongs to (matches correction_cycle on record)
            $table->unsignedSmallInteger('correction_cycle');

            // Snapshot of values BEFORE this correction was applied
            $table->string('previous_status_code', 32);
            $table->text('previous_reason')->nullable();

            // Cross-module plain int reference
            $table->unsignedBigInteger('corrected_by_staff_profile_id');
            $table->timestamp('corrected_at');

            // One history entry per record per cycle
            $table->unique(['attendance_record_id', 'correction_cycle'], 'sach_record_cycle_unique');

            $table->index('sheet_id');
            $table->index('enrollment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_attendance_correction_history');
    }
};

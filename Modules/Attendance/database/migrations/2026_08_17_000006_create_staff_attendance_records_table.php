<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `staff_attendance_records` table.
 *
 * One record per staff member per operational period per date.
 * Covers all staff — including guards and non-login staff who hold no
 * StaffAccount — so staff_profile_id is the sole identity anchor.
 *
 * QR scan times are stored separately from confirmed times:
 *   scanned_arrived_at / scanned_departed_at  → informational only, never official
 *   confirmed_arrived_at / confirmed_departed_at → the official record set by secretary
 *
 * Correction history lives in staff_attendance_correction_history; correction_cycle
 * is incremented when a verified record is unlocked for correction (same pattern
 * as student_attendance_records).
 *
 * All cross-module column references are plain unsigned integers with no DB-level
 * foreign key constraints, following the module-boundary contract.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_attendance_records', function (Blueprint $table): void {
            $table->id();

            // Cross-module plain int references (no DB FK)
            $table->unsignedBigInteger('staff_profile_id');
            $table->unsignedBigInteger('institution_semester_id');
            $table->unsignedBigInteger('operational_period_id');

            $table->date('record_date');

            // Attendance status — see StaffAttendanceStatus catalogue; null = not yet filled
            $table->string('status_code', 32)->nullable();

            // Free-text reason (required for excused_absence, leave, official_duty)
            $table->text('reason')->nullable();

            // Official arrival/departure (set by secretary; authoritative)
            $table->time('confirmed_arrived_at')->nullable();
            $table->time('confirmed_departed_at')->nullable();

            // QR scan times (informational; never auto-promoted to official)
            $table->time('scanned_arrived_at')->nullable();
            $table->time('scanned_departed_at')->nullable();

            // Verification state
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->unsignedBigInteger('verified_by_staff_profile_id')->nullable();

            // Correction cycle counter (incremented on each unlock for correction)
            $table->unsignedSmallInteger('correction_cycle')->default(0);

            // Entry source: manual_entry | scan_assisted | correction
            $table->string('source', 32)->default('manual_entry');

            // Creator
            $table->unsignedBigInteger('creator_staff_profile_id');

            $table->timestamps();

            // Enforce one record per staff member per period per date
            $table->unique(
                ['staff_profile_id', 'operational_period_id', 'record_date'],
                'sar_staff_period_date_unique',
            );

            $table->index('institution_semester_id');
            $table->index('operational_period_id');
            $table->index(['operational_period_id', 'record_date']);
            $table->index('is_verified');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_attendance_records');
    }
};

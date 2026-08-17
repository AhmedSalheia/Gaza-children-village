<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `staff_attendance_correction_history` table.
 *
 * Append-only audit log — rows are never updated or deleted.
 * Exactly one row per (staff_attendance_record_id, correction_cycle).
 * CorrectVerifiedStaffRecord enforces this before inserting.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_attendance_correction_history', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('staff_attendance_record_id')
                ->constrained('staff_attendance_records', indexName: 'stf_att_rec')
                ->cascadeOnDelete();

            // Denormalized for efficient lookups
            $table->unsignedBigInteger('staff_profile_id');
            $table->unsignedBigInteger('operational_period_id');
            $table->date('record_date');

            $table->unsignedSmallInteger('correction_cycle');

            // Snapshot of values BEFORE this correction
            $table->string('previous_status_code', 32)->nullable();
            $table->text('previous_reason')->nullable();

            $table->unsignedBigInteger('corrected_by_staff_profile_id');
            $table->timestamp('corrected_at');

            $table->unique(
                ['staff_attendance_record_id', 'correction_cycle'],
                'staff_ach_record_cycle_unique',
            );

            $table->index('staff_profile_id');
            $table->index('operational_period_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_attendance_correction_history');
    }
};

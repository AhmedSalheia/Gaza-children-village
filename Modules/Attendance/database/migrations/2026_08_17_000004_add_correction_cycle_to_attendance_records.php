<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds `correction_cycle` to `student_attendance_records`.
 *
 * Tracks which reopen-cycle the record is in. Starts at 0 (never corrected).
 * ReopenForCorrection increments the cycle counter on all records in the sheet
 * whenever the sheet is reopened, signalling the start of a new correction cycle.
 *
 * CorrectVerifiedAttendance uses this to enforce one-correction-per-cycle while
 * appending each correction's prior values to the correction history table so
 * every cycle's audit entry is durably preserved.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_attendance_records', function (Blueprint $table): void {
            $table->unsignedSmallInteger('correction_cycle')->default(0)->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('student_attendance_records', function (Blueprint $table): void {
            $table->dropColumn('correction_cycle');
        });
    }
};

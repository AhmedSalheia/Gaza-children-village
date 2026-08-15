<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a unique constraint on (class_group_id, attendance_date) to enforce
 * the invariant that at most one attendance sheet exists per class group per day.
 *
 * The application layer also enforces this (OpenDailySheet checks for an
 * existing row before creating), but a DB-level constraint is the last line
 * of defence against concurrent creates, seeder bugs, or direct inserts.
 *
 * Drops the composite non-unique index that previously covered the same
 * columns (plus status) to avoid redundant index overhead.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_attendance_sheets', function (Blueprint $table): void {
            // Drop the old composite non-unique index first to avoid a redundant
            // index once the unique constraint covers the same leading columns.
            $table->dropIndex(['class_group_id', 'attendance_date', 'status']);

            // DB-enforced one-sheet-per-class-per-day invariant.
            $table->unique(['class_group_id', 'attendance_date'], 'sas_class_date_unique');

            // Rebuild a status-only index for the queue/filter queries.
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('student_attendance_sheets', function (Blueprint $table): void {
            $table->dropUnique('sas_class_date_unique');
            $table->dropIndex(['status']);
            $table->index(['class_group_id', 'attendance_date', 'status']);
        });
    }
};

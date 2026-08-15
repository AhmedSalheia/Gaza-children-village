<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-student per-day rows for an attendance_publication_snapshot.
 *
 * Written once at publication time; never mutated. Reason and arrival/departure
 * are populated only when the policy at publication time permitted them.
 *
 * All cross-module IDs are plain integers (no DB-level FKs).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_snapshot_rows', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('snapshot_id')
                ->constrained('attendance_publication_snapshots')
                ->cascadeOnDelete();

            // Cross-module references
            $table->unsignedBigInteger('student_profile_id')->index();
            $table->unsignedBigInteger('enrollment_id');

            // Attendance record
            $table->date('attendance_date');
            $table->string('status_code', 32)->nullable();

            // Conditionally included fields (null when policy forbids)
            $table->text('reason')->nullable();
            $table->time('arrived_at')->nullable();

            $table->timestamps();

            $table->unique(['snapshot_id', 'enrollment_id', 'attendance_date']);
            $table->index(['snapshot_id', 'student_profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_snapshot_rows');
    }
};

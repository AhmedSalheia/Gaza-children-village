<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Staff position rows — effective-dated record linking a StaffProfile to a
 * controlled position definition within an institution and optionally an
 * InstitutionSemester.
 *
 * Cross-module FK notes:
 *  - institution_id references Organization.Institution but we store a plain
 *    integer (no FK constraint) to avoid a migration-time cross-module dependency.
 *  - institution_semester_id references AcademicCalendar.InstitutionSemester;
 *    same convention.
 *
 * Overlap and mutual-exclusion rules are enforced at the application layer
 * (lockForUpdate + check inside DB transaction) because SQLite does not support
 * exclusion constraints.
 *
 * PostgreSQL notes: add an exclusion constraint on
 * (staff_profile_id, institution_id, institution_semester_id, position_definition)
 * with daterange(started_on, ended_on, '[)') after the SQLite test phase ends.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_positions', function (Blueprint $table): void {
            $table->id();

            // Profile owning this position
            $table->foreignId('staff_profile_id')
                ->constrained('staff_profiles')
                ->cascadeOnDelete();

            // The specific assignment under which the position is held.
            // The assignment's institution must match institution_id below.
            $table->foreignId('staff_institution_assignment_id')
                ->constrained('staff_institution_assignments')
                ->cascadeOnDelete();

            // Denormalised from the assignment for fast scope queries.
            // Not an FK constraint (cross-module boundary).
            $table->unsignedBigInteger('institution_id');

            // Null for non-academic (medical, storage, women's center) positions.
            // Not an FK constraint (cross-module boundary).
            $table->unsignedBigInteger('institution_semester_id')->nullable();

            // Controlled vocabulary — values are validated at the PHP layer.
            $table->string('position_definition', 50);

            // Effective date range (inclusive on both ends; null ended_on = open).
            $table->date('started_on');
            $table->date('ended_on')->nullable();

            // Provenance
            $table->string('created_by', 200);
            $table->string('ended_by', 200)->nullable();
            $table->string('closure_reason', 500)->nullable();
            $table->string('closure_source', 50)->nullable(); // transfer, manual, etc.

            $table->timestamps();

            // Fast lookups for active-position resolution
            $table->index(['staff_profile_id', 'institution_id', 'started_on', 'ended_on'],
                'staff_positions_profile_inst_dates_idx');
            $table->index(['institution_id', 'institution_semester_id', 'position_definition'],
                'staff_positions_inst_sem_pos_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_positions');
    }
};

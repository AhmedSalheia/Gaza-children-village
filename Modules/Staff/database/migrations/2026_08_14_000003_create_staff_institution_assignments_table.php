<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `staff_institution_assignments` table.
 *
 * Records the effective-dated history of which institution a staff member is
 * assigned to. A staff member may be assigned to at most one institution on
 * any calendar date. Overlapping assignments are rejected.
 *
 * started_on and ended_on are inclusive calendar dates (DATE columns).
 * A null ended_on means the assignment is currently active (open-ended).
 *
 * Overlap prevention:
 *  - Application-level: a select-for-update + check inside a serializable
 *    transaction runs before each insert (SQLite-compatible).
 *  - Database-level: on PostgreSQL, add a TSRANGE/DATERANGE exclusion
 *    constraint manually after deployment (noted in comments; not in migration
 *    because SQLite does not support exclusion constraints).
 *
 * Transfers: the old assignment is closed (ended_on = transfer_date - 1 day)
 * and a new assignment is opened (started_on = transfer_date) atomically.
 *
 * Migration rollback: safe (drops the table). Add re-creation guard if ever
 * run against data-bearing production tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_institution_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('staff_profile_id')->constrained('staff_profiles')->restrictOnDelete();
            $table->foreignId('institution_id')->constrained('institutions')->restrictOnDelete();

            $table->date('started_on');
            $table->date('ended_on')->nullable(); // null = currently active

            // Closure/transfer provenance
            $table->string('closure_reason')->nullable();  // why this assignment ended
            $table->string('source_actor')->nullable();    // who made the change
            $table->text('source_context')->nullable();    // free-text context/reason

            $table->timestamps();

            // Index to accelerate overlap queries and history lookups.
            $table->index(['staff_profile_id', 'started_on', 'ended_on'], 'sia_profile_date_range');

            // PostgreSQL note: after deployment on PostgreSQL, add:
            //   ALTER TABLE staff_institution_assignments
            //   ADD CONSTRAINT no_overlapping_assignments
            //   EXCLUDE USING gist (
            //     staff_profile_id WITH =,
            //     daterange(started_on, COALESCE(ended_on, '9999-12-31'::date), '[]') WITH &&
            //   );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_institution_assignments');
    }
};

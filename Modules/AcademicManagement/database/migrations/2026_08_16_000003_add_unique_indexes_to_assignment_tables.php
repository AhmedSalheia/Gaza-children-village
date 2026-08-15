<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Add partial unique indexes to enforce active-assignment uniqueness at the
 * database layer, closing the TOCTOU race that application-level checks alone
 * cannot eliminate.
 *
 * teaching_assignments:
 *   Only one active assignment per (staff_position_id, class_group_id,
 *   subject_offering_id) is permitted. Historical (ended/superseded) rows
 *   are exempt — the partial WHERE clause scopes enforcement to status='active'.
 *
 * homeroom_assignments:
 *   Only one active non-co-lead (lead) per class_group_id is permitted.
 *   Co-leads (is_co_lead = 1) are exempt.
 *
 * Supported deployment drivers: SQLite (tests) and PostgreSQL (production).
 * Both engines support partial (filtered) unique indexes natively.
 *
 * MySQL is NOT a supported deployment target for this application. MySQL does
 * not support partial unique indexes, so this migration is intentionally
 * skipped for MySQL drivers. Running against MySQL will log a warning and
 * continue; the application-level transaction + lockForUpdate() check remains
 * the sole enforcement mechanism in that unsupported configuration.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            logger()->warning(
                '[assignments] Partial unique indexes require SQLite or PostgreSQL. '.
                'MySQL is not a supported deployment driver. DB-level uniqueness is not enforced.'
            );

            return;
        }

        DB::statement(
            "CREATE UNIQUE INDEX teaching_assignments_active_unique
             ON teaching_assignments (staff_position_id, class_group_id, subject_offering_id)
             WHERE status = 'active'"
        );

        // is_co_lead = false uses the boolean literal — compatible with both
        // PostgreSQL (boolean column) and SQLite (stores as 0/1 but accepts false).
        // Do NOT use is_co_lead = 0: PostgreSQL rejects integer-to-boolean comparison.
        DB::statement(
            "CREATE UNIQUE INDEX homeroom_assignments_active_lead_unique
             ON homeroom_assignments (class_group_id)
             WHERE is_co_lead = false AND status = 'active'"
        );

        // Prevents concurrent duplicate active assignments for the same
        // staff_position_id + class_group_id pair regardless of is_co_lead.
        // This closes the concurrent co-lead duplicate race: the lead-uniqueness
        // index above only protects (class_group_id) for leads; this index
        // protects (staff_position_id, class_group_id) for all active rows.
        DB::statement(
            "CREATE UNIQUE INDEX homeroom_assignments_active_position_class_unique
             ON homeroom_assignments (staff_position_id, class_group_id)
             WHERE status = 'active'"
        );
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS teaching_assignments_active_unique');
        DB::statement('DROP INDEX IF EXISTS homeroom_assignments_active_lead_unique');
        DB::statement('DROP INDEX IF EXISTS homeroom_assignments_active_position_class_unique');
    }
};

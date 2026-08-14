<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Explicit period-scope links for a staff position.
 *
 * Rules (F16):
 *  - Period links are only valid for academic positions (institution_semester_id
 *    is not null on the parent staff_position).
 *  - Institution-wide non-academic positions must NOT have period links.
 *  - "All periods" = one row per approved period; no wildcard column.
 *  - Adding a new period to an institution semester does NOT automatically
 *    grant access: existing staff must have the new row added explicitly.
 *  - A position without any period links covers ALL periods by default only
 *    for positions where the spec says "all periods"; secretaries receive no
 *    periods unless explicitly linked.
 *
 * operational_period_id references AcademicCalendar.OperationalPeriod; stored
 * as a plain integer (no FK constraint — cross-module boundary).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_position_periods', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('staff_position_id')
                ->constrained('staff_positions')
                ->cascadeOnDelete();

            // References AcademicCalendar.OperationalPeriod — no FK constraint.
            $table->unsignedBigInteger('operational_period_id');

            $table->timestamps();

            // A position may reference each period at most once.
            $table->unique(['staff_position_id', 'operational_period_id'],
                'staff_position_periods_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_position_periods');
    }
};

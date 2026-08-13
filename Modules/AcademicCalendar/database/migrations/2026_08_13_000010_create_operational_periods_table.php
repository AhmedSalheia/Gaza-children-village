<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Operational periods subdivide an institution semester into named time blocks.
 *
 * Common examples are morning and afternoon shifts, but administrators may
 * define any number of periods. The sequence is unique within the institution
 * semester. Code is a stable machine identifier unique within the institution
 * semester.
 *
 * Schema decisions:
 *   - starts_at / ends_at are TIME columns (time-of-day only).
 *     Overnight periods (e.g. ending past midnight) are not supported in F08.
 *   - is_active defaults to true; deactivation is used instead of deletion.
 *   - No soft deletion column; inactive records remain queryable indefinitely.
 *   - No actor-audit columns; F18 will add those through the audit engine.
 *   - Overlap validation and draft-only mutation rules are enforced by
 *     application actions, not DB constraints (portability requirement).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operational_periods', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('institution_semester_id');
            $table->string('code');
            $table->string('name_en');
            $table->string('name_ar')->nullable();
            $table->unsignedInteger('sequence');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['institution_semester_id', 'code']);
            $table->unique(['institution_semester_id', 'sequence']);

            $table->foreign('institution_semester_id')
                ->references('id')
                ->on('institution_semesters')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operational_periods');
    }
};

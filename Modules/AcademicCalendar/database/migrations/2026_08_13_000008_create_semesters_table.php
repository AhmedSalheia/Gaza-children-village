<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * F07 — Semester catalogue table.
 *
 * A semester belongs to exactly one academic year. Any positive number of
 * semesters is allowed; there is no two-semester assumption. Summer semesters
 * and exceptional semesters are representable through the same model.
 *
 * Stable semantic codes (S1, S2, SUMMER) are examples, not a fixed catalogue.
 * Do not encode fixed code semantics in the database schema.
 *
 * Status lifecycle mirrors AcademicYear:
 *   draft → open → closed → archived (terminal for ordinary actions)
 *   closed → open (reopen with reason)
 *
 * Constraints:
 *   - code unique within academic year (academic_year_id, code)
 *   - sequence unique within academic year (academic_year_id, sequence)
 *   - sequence positive (enforced in application actions)
 *   - semester dates must fall within the parent academic year dates (actions)
 *   - semesters in one academic year must not overlap (actions)
 *   - no soft deletion
 *   - no database ENUM
 *   - no actor-audit columns (deferred to F18/Audit integration)
 *
 * InstitutionSemester (F08) activates a global Semester for a specific
 * institution. Do not conflate the two models.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('semesters', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')
                ->constrained('academic_years')
                ->restrictOnDelete();
            $table->string('code');
            $table->string('name_en');
            $table->string('name_ar')->nullable();
            $table->unsignedInteger('sequence');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status');
            $table->timestamps();

            $table->unique(['academic_year_id', 'code']);
            $table->unique(['academic_year_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('semesters');
    }
};

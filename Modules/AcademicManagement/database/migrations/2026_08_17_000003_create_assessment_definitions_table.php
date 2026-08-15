<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Assessment definitions for a semester/class/subject context.
 *
 * An assessment_definition describes a single graded task:
 *   • Midterm exam for Class 10A — Mathematics, max 50 pts, weight 30%
 *   • Classwork quiz 1 for all Maths classes, max 10 pts
 *
 * Context granularity (all nullable except institution_semester_id):
 *   institution_semester_id + class_group_id + subject_offering_id
 *     → one specific class-section's subject assessment
 *   institution_semester_id + subject_offering_id (no class group)
 *     → shared definition across all sections of that subject
 *   institution_semester_id only
 *     → semester-wide definition (rare, e.g. a school-wide project)
 *
 * class_group_id and subject_offering_id are within-module FK references.
 * institution_semester_id is a plain cross-module integer.
 *
 * status:
 *   active   — visible, editable (unless used by a published mark sheet)
 *   archived — hidden from new mark sheets; historical data intact
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_definitions', function (Blueprint $table): void {
            $table->id();

            // Cross-module reference to institution_semesters.id (no DB FK)
            $table->unsignedBigInteger('institution_semester_id')->index();

            // Optional within-module scoping FKs
            $table->foreignId('class_group_id')
                ->nullable()
                ->constrained('class_groups')
                ->restrictOnDelete();

            $table->foreignId('subject_offering_id')
                ->nullable()
                ->constrained('institution_subject_offerings')
                ->restrictOnDelete();

            $table->string('name_ar', 150);
            $table->string('name_en', 150)->nullable();

            // classwork | homework | quiz | project | midterm | final | participation | other
            $table->string('assessment_type', 32);

            // Maximum achievable score (0 < max_score)
            $table->decimal('max_score', 7, 2);

            // Percentage weight in the final result calculation (0 = informational only)
            $table->decimal('weight', 5, 2)->default(0);

            $table->date('assessment_date')->nullable();

            // active | archived
            $table->string('status', 32)->default('active');

            $table->timestamps();

            $table->index(['institution_semester_id', 'class_group_id', 'subject_offering_id']);
            $table->index(['institution_semester_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_definitions');
    }
};

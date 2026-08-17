<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-student per-subject immutable result rows for a result_publication.
 *
 * Written once at publication time; never mutated afterward. Corrections
 * produce a new result_publication version which writes fresh rows.
 *
 * All cross-module IDs are plain integers (no DB-level FKs).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('result_publication_rows', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('result_publication_id')
                ->constrained('result_publications')
                ->cascadeOnDelete();

            // Cross-module references (plain integers, no DB FK)
            $table->unsignedBigInteger('student_profile_id')->index();
            $table->unsignedBigInteger('enrollment_id');
            $table->unsignedBigInteger('subject_offering_id');

            // Calculated values (snapshot at publish time)
            $table->decimal('raw_total_score', 10, 4)->nullable(); // weighted score sum
            $table->decimal('raw_max_possible', 10, 4)->nullable(); // total possible weighted score
            $table->decimal('normalized_score', 6, 2)->nullable(); // 0–100 percentage

            // Grading scale result
            $table->string('grade_code', 20)->nullable();
            $table->string('grade_name_ar', 100)->nullable();
            $table->boolean('is_passing')->nullable();

            // Data quality flag
            $table->string('completeness_status', 32)->default('complete');
            // complete | incomplete (some marks missing) | all_absent | no_assessments

            $table->timestamps();

            $table->unique(['result_publication_id', 'enrollment_id', 'subject_offering_id'],'res_pub_enr_sub');
            $table->index(['result_publication_id', 'student_profile_id'],'res_pub_stu_pro');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result_publication_rows');
    }
};

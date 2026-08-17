<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Replace the broad unique constraint on student_marks with a partial unique
 * index that only applies to original marks (correction_of_id IS NULL).
 *
 * Corrections are new rows pointing back to the original via correction_of_id.
 * They share the same (mark_sheet_id, enrollment_id, assessment_definition_id)
 * triplet, so the non-partial unique key incorrectly blocks them.
 *
 * The application layer (CorrectMark action) enforces that only one active
 * correction per original mark is allowed (lockForUpdate check).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_marks', function ($table): void {
            $table->dropUnique('student_marks_sheet_enrollment_assessment_unique');

            $table->unsignedTinyInteger('original_guard')
                ->nullable()
                ->storedAs(
                    'IF(correction_of_id IS NULL, 1, NULL)'
                );

                $table->unique(
                    [
                        'mark_sheet_id',
                        'enrollment_id',
                        'assessment_definition_id',
                        'original_guard',
                    ],
                    'student_marks_original_unique'
                );
        });
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS student_marks_original_unique');

        Schema::table('student_marks', function ($table): void {
            $table->unique(
                ['mark_sheet_id', 'enrollment_id', 'assessment_definition_id'],
                'student_marks_sheet_enrollment_assessment_unique'
            );
        });
    }
};

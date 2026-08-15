<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Student marks — one row per enrollment per assessment per mark sheet.
 *
 * A null score with an exception_status (absent/exempt/medical) is valid.
 * A non-null score must be between 0 and the assessment's max_score.
 * These invariants are enforced at the application layer.
 *
 * Correction versioning:
 *   When a published mark is corrected, a new row is inserted with
 *   correction_of_id pointing to the old row. The new row stores the
 *   corrected score, corrected_by_staff_profile_id, corrected_at, and
 *   correction_reason. The old row is not deleted (immutable audit trail).
 *
 * All FKs are within-module (mark_sheets, student_enrollments,
 * assessment_definitions, and self for correction_of_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_marks', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('mark_sheet_id')
                ->constrained('mark_sheets')
                ->cascadeOnDelete();

            $table->foreignId('enrollment_id')
                ->constrained('student_enrollments')
                ->restrictOnDelete();

            $table->foreignId('assessment_definition_id')
                ->constrained('assessment_definitions')
                ->restrictOnDelete();

            // Null when exception_status is set (absent/exempt/medical)
            $table->decimal('score', 7, 2)->nullable();

            // absent | exempt | medical — null when score is present
            $table->string('exception_status', 32)->nullable();

            // Safe teacher note — must not contain sensitive personal data
            $table->text('teacher_note')->nullable();

            // Correction chain
            $table->foreignId('correction_of_id')
                ->nullable()
                ->constrained('student_marks')
                ->nullOnDelete();

            // Cross-module plain int (no DB FK to Staff module)
            $table->unsignedBigInteger('corrected_by_staff_profile_id')->nullable();
            $table->dateTime('corrected_at')->nullable();
            $table->text('correction_reason')->nullable();

            $table->timestamps();

            // One current mark per enrollment per assessment per sheet
            $table->unique(
                ['mark_sheet_id', 'enrollment_id', 'assessment_definition_id'],
                'student_marks_sheet_enrollment_assessment_unique'
            );

            $table->index(['mark_sheet_id', 'enrollment_id']);
            $table->index('enrollment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_marks');
    }
};

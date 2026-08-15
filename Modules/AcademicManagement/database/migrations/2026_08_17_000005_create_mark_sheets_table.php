<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mark sheets — the workflow aggregate for teacher → secretary → principal.
 *
 * One mark sheet ties a specific TeachingAssignment to a MarkEntryWindow
 * (or a semester with no window). It is the unit of review: a teacher
 * submits, a secretary verifies, a principal approves.
 *
 * Status lifecycle:
 *   draft → submitted → returned → (back to) draft
 *                     → verified → approved → published
 *                                           → superseded (correction)
 *
 * Correction: when a published sheet needs correction, a new version is
 * created with the same teaching_assignment + window; the old sheet is
 * moved to "superseded" and the new one starts as "draft".
 *
 * Cross-module plain integers (no DB FK):
 *   institution_semester_id, submitted_by_staff_profile_id,
 *   verified_by_staff_profile_id, approved_by_staff_profile_id,
 *   returned_by_staff_profile_id
 *
 * Within-module FKs:
 *   class_group_id, subject_offering_id, teaching_assignment_id,
 *   mark_entry_window_id (nullable), grading_scale_id (nullable),
 *   superseded_by_id (nullable self-ref)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mark_sheets', function (Blueprint $table): void {
            $table->id();

            // Denormalised for efficient queries; derived from teaching_assignment
            $table->unsignedBigInteger('institution_semester_id')->index();

            $table->foreignId('class_group_id')
                ->constrained('class_groups')
                ->restrictOnDelete();

            $table->foreignId('subject_offering_id')
                ->constrained('institution_subject_offerings')
                ->restrictOnDelete();

            $table->foreignId('teaching_assignment_id')
                ->constrained('teaching_assignments')
                ->restrictOnDelete();

            $table->foreignId('mark_entry_window_id')
                ->nullable()
                ->constrained('mark_entry_windows')
                ->nullOnDelete();

            $table->foreignId('grading_scale_id')
                ->nullable()
                ->constrained('grading_scales')
                ->nullOnDelete();

            // Version counter — increments when a published sheet is superseded
            $table->unsignedTinyInteger('version')->default(1);

            // draft | submitted | returned | verified | approved | published | superseded
            $table->string('status', 32)->default('draft');

            // Audit trail — who did what and when
            $table->unsignedBigInteger('submitted_by_staff_profile_id')->nullable();
            $table->dateTime('submitted_at')->nullable();

            $table->unsignedBigInteger('verified_by_staff_profile_id')->nullable();
            $table->dateTime('verified_at')->nullable();

            $table->unsignedBigInteger('approved_by_staff_profile_id')->nullable();
            $table->dateTime('approved_at')->nullable();

            $table->unsignedBigInteger('returned_by_staff_profile_id')->nullable();
            $table->dateTime('returned_at')->nullable();
            $table->text('return_reason')->nullable();

            // Self-referential: if this sheet was superseded, points to the replacement
            $table->foreignId('superseded_by_id')
                ->nullable()
                ->constrained('mark_sheets')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['institution_semester_id', 'status']);
            $table->index(['class_group_id', 'subject_offering_id', 'status']);
            $table->index(['teaching_assignment_id', 'status']);

            // One active (non-superseded) version per assignment+window combination
            // (application layer enforces; DB enforces via unique for non-superseded)
            $table->unique(
                ['teaching_assignment_id', 'mark_entry_window_id', 'version'],
                'mark_sheets_assignment_window_version_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mark_sheets');
    }
};

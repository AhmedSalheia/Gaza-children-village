<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the correction_field_proposals table.
 *
 * One row per proposal attempt for a correction request.
 * A request may have multiple proposal rows when a guardian resubmits
 * after a secretary requests clarification — each resubmission creates
 * a new row; prior rows are kept for the audit trail.
 *
 * proposed_value / old_value_snapshot are stored as TEXT (JSON or plain string).
 * Sensitive fields (birth_date, identifier_correction) are stored encrypted
 * by the service layer using Laravel's Crypt facade before insertion.
 * The model applies no cast — the service owns encryption/decryption.
 *
 * relationship_ref_id: for guardian_relationship_type and guardian_legal_authority
 * corrections, stores the guardian_student_relationship.id being corrected.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('correction_field_proposals', function (Blueprint $table): void {
            $table->id();

            $table->unsignedBigInteger('correction_request_id');
            $table->foreign('correction_request_id')
                ->references('id')
                ->on('student_correction_requests')
                ->restrictOnDelete();

            // Field being corrected (redundant with request.field_catalogue_code but
            // kept here for self-contained audit rows in case of multi-field future expansion)
            $table->string('field_code', 64);

            // Snapshot of the official value at time of submission
            // (TEXT, may be JSON; encrypted for sensitive fields)
            $table->text('old_value_snapshot')->nullable();

            // The value the guardian is proposing
            // (TEXT, may be JSON; encrypted for sensitive fields)
            $table->text('proposed_value');

            // Guardian's explanation / supporting context
            $table->text('explanation')->nullable();

            // Filled only when this proposal was applied
            $table->text('applied_value')->nullable();

            // For relationship corrections: which relationship row is being updated
            $table->unsignedBigInteger('relationship_ref_id')->nullable();

            // Sequence number — 1 for original, 2+ for resubmissions
            $table->unsignedTinyInteger('submission_sequence')->default(1);

            $table->timestamps();
        });

        Schema::table('correction_field_proposals', function (Blueprint $table): void {
            $table->index('correction_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('correction_field_proposals');
    }
};

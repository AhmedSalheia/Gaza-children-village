<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * student_document_requests
 *
 * Tracks each guardian/secretary document request through its lifecycle.
 * There are 13 workflow states; the terminal states are issued, generation_failed,
 * cancelled, and superseded.
 *
 * Enrollment and account IDs are plain cross-module integers (no DB foreign keys)
 * to respect module boundaries. Application layer validates existence.
 *
 * The 13 states:
 *   draft                  Guardian saved but not yet submitted
 *   submitted              Guardian submitted; awaiting secretary review
 *   pending_completeness   Secretary is checking data completeness
 *   completeness_failed    Secretary found missing data (links to correction workflow)
 *   completeness_passed    Secretary confirmed complete; forwarded for approval
 *   awaiting_approval      Waiting for principal / admin to approve
 *   approved               Approved; GenerateDocumentJob dispatched
 *   generating             Job is processing the PDF
 *   issued                 Document generated and stored ← terminal/happy
 *   generation_failed      Job failed                    ← terminal/recoverable
 *   pending_clarification  Secretary requested clarification from guardian
 *   rejected               Rejected by secretary or principal  ← terminal
 *   cancelled              Cancelled by any actor              ← terminal
 *
 * Note: 'superseded' is recorded on the IssuedDocument, not the request.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_document_requests', function (Blueprint $table): void {
            $table->id();

            // Cross-module plain int references — no DB FK
            $table->unsignedBigInteger('enrollment_id')->index();
            $table->unsignedBigInteger('student_profile_id')->index();
            $table->unsignedBigInteger('institution_id')->index();

            // Actor who initiated the request
            $table->string('requested_by_actor_type', 16); // guardian | staff | admin
            $table->unsignedBigInteger('requested_by_account_id');
            $table->string('portal', 16);                  // guardian | staff | admin

            // What document is requested
            $table->string('document_type_code', 64)->index();
            $table->string('locale', 8)->default('ar');    // ar | en

            // Workflow state
            $table->string('status', 32)->default('draft')->index();

            // Optional notes / reasons
            $table->text('purpose_notes')->nullable();
            $table->text('clarification_reason')->nullable();
            $table->text('rejection_reason')->nullable();

            // Optional staff/admin actor who reviewed/approved/rejected
            $table->unsignedBigInteger('reviewed_by_account_id')->nullable();
            $table->unsignedBigInteger('approved_by_account_id')->nullable();

            // Timestamps for state transitions (audit trail)
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable(); // issued or failed

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_document_requests');
    }
};

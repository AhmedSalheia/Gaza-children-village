<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `electronic_approvals` table.
 *
 * Each row is an immutable, identity-reconfirmed approval or rejection.
 * Revocation is expressed by is_revoked = true; the original row stays intact.
 * A superseding approval sets superseded_by_id on the old row and creates a
 * new row — preserving the complete chain.
 *
 * content_hash: SHA-256 of the exact content the approver saw. Computed and
 * stored at approval time; compared again by the recording service. Mismatch
 * indicates content changed between page load and submission — reject.
 *
 * Cross-module column references (plain unsigned integers — no DB FK):
 *   approver_account_id — Accounts module (type in approver_actor_type)
 *   subject_id          — any domain model (type in subject_type)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('electronic_approvals', function (Blueprint $table): void {
            $table->id();

            // Approver identity
            $table->string('approver_actor_type', 32);   // administrative|staff
            $table->string('approver_actor_portal', 32); // admin|staff
            $table->unsignedBigInteger('approver_account_id');

            // Approval type (e.g. 'sensitive_field_correction', 'document_issuance')
            $table->string('approval_type', 64);

            // Decision
            $table->string('decision', 32); // approved|rejected

            // Subject reference
            $table->string('subject_type', 128);
            $table->unsignedBigInteger('subject_id');

            // Version of the subject at approval time (for optimistic locking)
            $table->unsignedInteger('subject_version')->nullable();

            // SHA-256 of the content the approver saw on their screen
            $table->char('content_hash', 64);

            $table->text('comment')->nullable();

            // How the approver reconfirmed their identity
            $table->string('reconfirmation_method', 32)->default('password');

            // Revocation / supersession
            $table->boolean('is_revoked')->default(false);
            $table->unsignedBigInteger('superseded_by_id')->nullable();

            // Low-entropy safe device fingerprint (browser family, OS family only)
            $table->string('device_fingerprint', 128)->nullable();

            // Write-once
            $table->timestamp('created_at')->useCurrent();

            $table->index(['subject_type', 'subject_id'], 'ea_subject_idx');
            $table->index('approver_account_id', 'ea_approver_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('electronic_approvals');
    }
};

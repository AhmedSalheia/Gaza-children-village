<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Background job / operation status tracking.
 *
 * Each row tracks one long-running operation (PDF export, bulk import,
 * large report, etc.). The actor (who triggered it) and the operation type
 * are stored for isolation checks — an actor can only poll their own jobs.
 *
 * Linked to Laravel's database queue via nullable job_id; for operations
 * dispatched as queued jobs, job_id holds the value from the 'jobs' table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operation_statuses', function (Blueprint $table): void {
            $table->id();

            // Optional link to Laravel jobs table (nullable for sync operations)
            $table->unsignedBigInteger('job_id')->nullable()->index('os_job_id_idx');

            // Actor who triggered the operation
            $table->string('actor_type', 30);          // admin | staff | guardian
            $table->unsignedBigInteger('actor_account_id');
            $table->string('portal', 20);

            // Type of operation (stable string key, e.g. 'pdf_export', 'bulk_import')
            $table->string('operation_type', 80);

            // Status lifecycle
            $table->string('status', 20)->default('queued');
            // queued → running → completed | failed | cancelled | expired

            // Optional structured context (institution_id, semester_id, etc.)
            $table->json('scope')->nullable();

            // Progress (0–100); null when not trackable
            $table->unsignedTinyInteger('progress_percent')->nullable();

            // Safe error summary — never raw stack traces with PII
            $table->string('failure_summary', 500)->nullable();

            // How many times the queue worker has attempted this job
            $table->unsignedSmallInteger('attempts')->default(0);

            // Reference to the output (e.g. a storage path or URL for a generated PDF)
            $table->string('output_reference', 500)->nullable();

            // Lifecycle timestamps
            $table->timestamp('queued_at')->useCurrent();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            // Isolation check: actor can only see their own jobs
            $table->index(
                ['actor_type', 'actor_account_id', 'portal', 'status'],
                'os_actor_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operation_statuses');
    }
};

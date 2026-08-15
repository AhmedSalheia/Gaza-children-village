<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `guardian_correction_requests` table.
 *
 * Stores lightweight correction requests submitted by guardians via the
 * guardian portal. A request flags a discrepancy in contact_priority or
 * is_emergency_contact on a specific guardian_student_relationship.
 *
 * Rows are never deleted; resolved requests keep their audit trail.
 *
 * Status lifecycle: pending → approved | rejected
 *
 * Concurrency invariant:
 *   pending_lock is set to 1 while the request is pending and NULL once
 *   resolved. The unique index on (guardian_student_relationship_id,
 *   pending_lock) enforces that only one pending request can exist per
 *   relationship at any time — even under concurrent submissions — because
 *   NULL values are not considered equal in unique indexes in both SQLite
 *   and MySQL, so multiple resolved rows per relationship are allowed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guardian_correction_requests', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('guardian_student_relationship_id')
                ->constrained('guardian_student_relationships')
                ->restrictOnDelete();

            // The guardian's proposed values (null = no change requested for that field)
            $table->unsignedTinyInteger('requested_contact_priority')->nullable();
            $table->boolean('requested_is_emergency_contact')->nullable();

            // Optional free-text note from the guardian
            $table->text('note')->nullable();

            // Workflow status
            $table->string('status', 16)->default('pending'); // pending | approved | rejected

            // Concurrency lock: set to 1 while pending, NULL once resolved.
            // The unique index below enforces at most one pending row per relationship.
            $table->unsignedTinyInteger('pending_lock')->nullable()->default(1);

            // Resolution audit
            $table->unsignedBigInteger('resolved_by_admin_id')->nullable();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            // DB-enforced: only one row with pending_lock=1 per relationship.
            // NULLs in unique indexes do not conflict, so resolved rows accumulate freely.
            $table->unique(['guardian_student_relationship_id', 'pending_lock'],
                'gcr_relationship_pending_unique');

            // Fast lookup for status queries
            $table->index(['guardian_student_relationship_id', 'status'],
                'gcr_relationship_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardian_correction_requests');
    }
};

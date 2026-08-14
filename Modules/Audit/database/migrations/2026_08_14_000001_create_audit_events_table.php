<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only audit event log.
 *
 * Design rules (F18):
 *  - INSERT only: the application never updates or deletes rows.
 *  - DB-level immutability: where the driver supports it, triggers block
 *    UPDATE/DELETE (added in a separate migration for PostgreSQL).
 *    SQLite used in tests has no trigger support — immutability enforced at the
 *    PHP layer (AuditRecorder contract has no update/delete methods).
 *  - Redaction rules: no passwords, tokens, session IDs, or raw national IDs
 *    may appear in before_state, after_state, or metadata.
 *  - event_id is a stable UUID assigned at the PHP layer (not auto-increment)
 *    so the event can be referenced in distributed logs before it is written.
 *
 * Cross-module scope columns (institution_id, institution_semester_id,
 * operational_period_id) are plain integers — not FK-constrained — so this
 * table can receive events from any module without hard dependencies.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('event_id')->unique(); // stable external reference

            // Actor / source
            $table->string('actor_type', 30);  // 'administrative'|'staff'|'guardian'|'system'
            $table->unsignedBigInteger('actor_account_id')->nullable(); // null for system events
            $table->string('portal', 30)->nullable();                  // 'admin'|'staff'|'guardian'|null

            // Event classification
            $table->string('source_module', 60);    // e.g. 'Staff', 'AcademicCalendar'
            $table->string('action', 100);           // e.g. 'staff_position.assigned'
            $table->string('subject_type', 100)->nullable(); // e.g. 'StaffPosition'
            $table->unsignedBigInteger('subject_id')->nullable();

            // Operational scope (optional — filled where meaningful)
            $table->unsignedBigInteger('institution_id')->nullable();
            $table->unsignedBigInteger('institution_semester_id')->nullable();
            $table->unsignedBigInteger('operational_period_id')->nullable();

            // State snapshot (JSON; NEVER store passwords / tokens / raw IDs)
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->string('change_reason', 500)->nullable();

            // Request provenance
            $table->string('request_id', 36)->nullable(); // UUID correlation
            $table->string('ip_address', 45)->nullable();

            // Schema version — bump when shape changes to aid projection readers.
            $table->unsignedSmallInteger('schema_version')->default(1);

            // JSON metadata bucket (non-sensitive supplemental data only)
            $table->json('metadata')->nullable();

            // Immutable timestamp (no updated_at)
            $table->timestamp('recorded_at')->useCurrent();

            // Indexes for common query patterns
            $table->index(['actor_account_id', 'recorded_at'], 'audit_actor_recorded_idx');
            $table->index(['source_module', 'action', 'recorded_at'], 'audit_module_action_idx');
            $table->index(['subject_type', 'subject_id'], 'audit_subject_idx');
            $table->index(['institution_id', 'recorded_at'], 'audit_institution_recorded_idx');
            $table->index(['request_id'], 'audit_request_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `attendance_scan_events` table.
 *
 * A scan event is created when a QR code is scanned or a token is submitted
 * via the manual fallback form. Scan events are NEVER automatically promoted
 * to official attendance — a secretary must review and accept each event.
 *
 * Replay prevention: a unique index on (qr_credential_id, operational_period_id,
 * scan_date, direction) prevents the same credential from creating duplicate
 * pending events for the same period/date/direction combination.
 *
 * The raw token is NEVER stored. Only qr_credential_id (FK to credentials table)
 * is stored, so no token leakage is possible even if this table is read directly.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_scan_events', function (Blueprint $table): void {
            $table->id();

            // Credential reference — no raw token ever stored
            $table->foreignId('qr_credential_id')
                ->constrained('staff_qr_credentials')
                ->restrictOnDelete();

            // Denormalized for queue queries without joining credentials
            $table->unsignedBigInteger('staff_profile_id');

            // Cross-module plain int references
            $table->unsignedBigInteger('institution_semester_id');
            $table->unsignedBigInteger('operational_period_id');

            // Scan metadata
            $table->timestamp('scanned_at');
            $table->date('scan_date'); // date portion of scanned_at (for replay-prevention index)
            $table->string('direction', 16)->default('unknown'); // arrival | departure | unknown
            $table->string('device_fingerprint', 128)->nullable();

            // Processing state: pending | accepted | rejected
            $table->string('processing_status', 16)->default('pending');

            // Review outcome
            $table->unsignedBigInteger('reviewed_by_staff_profile_id')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();

            // Replay prevention: one pending event per credential/period/date/direction
            $table->unique(
                ['qr_credential_id', 'operational_period_id', 'scan_date', 'direction'],
                'ase_replay_prevention_unique',
            );

            $table->index('staff_profile_id');
            $table->index(['operational_period_id', 'scan_date', 'processing_status']);
            $table->index('processing_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_scan_events');
    }
};

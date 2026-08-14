<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only authentication security-event log.
 *
 * This is a narrow security-event stream owned by Modules\Accounts.
 * It is NOT the generic F18 business-audit infrastructure, but is designed
 * so that F18 can bridge into it without rewriting authentication.
 *
 * Privacy rules:
 * - Raw credentials, national IDs, and session identifiers are NEVER stored.
 * - Login identifiers are stored only as HMAC fingerprints.
 * - IP addresses are stored as HMAC fingerprints (configurable).
 * - User-agent strings are truncated, not hashed.
 * - account_id is stored (opaque integer) when the account is positively identified.
 *
 * Append-only contract:
 * - No application UPDATE or DELETE paths exist for this table.
 * - Only INSERT is permitted through RecordAuthenticationEvent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authentication_events', function (Blueprint $table): void {
            $table->id();

            // Which portal this event belongs to: 'admin', 'staff', 'guardian'.
            $table->string('portal', 32);

            // Event type: login_succeeded | login_failed | login_throttled | logout | sessions_revoked.
            $table->string('event_type', 64);

            // Account PK when positively identified (null for unknown-identifier failures/throttles).
            $table->unsignedBigInteger('account_id')->nullable();

            // FQCN of the account model class (null when account_id is null).
            $table->string('account_type', 255)->nullable();

            // HMAC fingerprint of the normalized login identifier — never the raw value.
            $table->string('identifier_fingerprint', 64)->nullable();

            // Logical event time (filled by the application).
            $table->timestamp('occurred_at');

            // true for success events (login_succeeded, logout, sessions_revoked).
            $table->boolean('success');

            // Opaque category for failure analysis: bad_credentials | account_not_active | throttled.
            // Null for success events.
            $table->string('failure_category', 64)->nullable();

            // Request/correlation identifier from X-Request-Id header (or null).
            $table->string('correlation_id', 128)->nullable();

            // HMAC fingerprint of the client IP address.
            $table->string('ip_fingerprint', 64)->nullable();

            // Truncated User-Agent string (max 200 chars).
            $table->string('user_agent_summary', 200)->nullable();

            // DB write timestamp only — no updated_at (events are immutable).
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::table('authentication_events', function (Blueprint $table): void {
            $table->index(['portal', 'event_type', 'occurred_at'], 'ae_portal_type_occurred');
            $table->index(['account_id', 'account_type', 'occurred_at'], 'ae_account_occurred');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authentication_events');
    }
};

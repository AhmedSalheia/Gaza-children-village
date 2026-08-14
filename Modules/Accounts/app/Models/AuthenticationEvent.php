<?php

declare(strict_types=1);

namespace Modules\Accounts\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Append-only authentication security-event record.
 *
 * Application code MUST NOT call update() or delete() on this model.
 * Only RecordAuthenticationEvent::__invoke() creates records.
 *
 * Privacy constraints (enforced by RecordAuthenticationEvent):
 * - Raw login identifiers are never stored; only HMAC fingerprints.
 * - Raw IP addresses are never stored; only HMAC fingerprints.
 * - Passwords, session IDs, CSRF tokens, and reset tokens are never stored.
 * - User-agent strings are truncated to 200 characters.
 *
 * No updated_at column — records are immutable after creation.
 * F18 will bridge this stream into the system-wide audit infrastructure.
 */
final class AuthenticationEvent extends Model
{
    /**
     * Disable updated_at — events are append-only and immutable.
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'portal',
        'event_type',
        'account_id',
        'account_type',
        'identifier_fingerprint',
        'occurred_at',
        'success',
        'failure_category',
        'correlation_id',
        'ip_fingerprint',
        'user_agent_summary',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'success' => 'boolean',
        ];
    }
}

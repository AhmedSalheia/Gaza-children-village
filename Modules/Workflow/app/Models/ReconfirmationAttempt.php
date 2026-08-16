<?php

declare(strict_types=1);

namespace Modules\Workflow\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Append-only record of a reconfirmation attempt (successful or failed).
 *
 * Both succeeded and failed attempts are persisted so that the rate-limit
 * window includes incorrect-password retries. This prevents an attacker from
 * exhausting allowed credential checks without counting against the limit.
 */
final class ReconfirmationAttempt extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'reconfirmation_attempts';

    /** @var list<string> */
    protected $fillable = [
        'actor_type',
        'actor_account_id',
        'portal',
        'succeeded',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'succeeded' => 'boolean',
    ];
}

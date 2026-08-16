<?php

declare(strict_types=1);

namespace Modules\Workflow\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Server-side proof of a recent, actor-bound, content-bound password reconfirmation.
 *
 * Tokens are:
 *  - Single-use (consumed_at is set atomically on first use)
 *  - Short-lived (TTL configured at issue time, default 5 minutes)
 *  - Bound to an actor identity and a SHA-256 content hash
 *
 * The UUID primary key is issued by the service and passed to the Livewire
 * component; the component then submits it with the approval form.
 */
final class ElectronicApprovalToken extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * Disable automatic updated_at; tokens are write-once (consumed_at is set directly).
     */
    public const UPDATED_AT = null;

    protected $table = 'electronic_approval_tokens';

    /** @var list<string> */
    protected $fillable = [
        'id',
        'actor_type',
        'actor_portal',
        'actor_account_id',
        'content_hash',
        'approval_type',
        'subject_type',
        'subject_id',
        'reconfirmation_method',
        'expires_at',
        'consumed_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    public static function generateId(): string
    {
        return (string) Str::uuid();
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }

    public function isValid(): bool
    {
        return ! $this->isExpired() && ! $this->isConsumed();
    }

    /**
     * @param  Builder<ElectronicApprovalToken>  $query
     * @return Builder<ElectronicApprovalToken>
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now());
    }
}

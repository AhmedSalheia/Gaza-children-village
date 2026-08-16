<?php

declare(strict_types=1);

namespace Modules\Notifications\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Portal notification record.
 *
 * Append-only: read_at and dismissed_at are set once; no row is ever
 * updated to change message content. Named PortalNotification to distinguish
 * from Laravel's built-in Notification model used by the Notifiable trait.
 *
 * Table: portal_notifications
 */
final class PortalNotification extends Model
{
    public $timestamps = false;

    protected $table = 'portal_notifications';

    /**
     * No mass-assignment — set fields individually to prevent parameter-injection
     * attacks where callers might sneak in recipient_account_id or message_key.
     */
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'message_params' => 'array',
            'priority' => 'integer',
            'read_at' => 'datetime',
            'dismissed_at' => 'datetime',
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    /**
     * Notifications for a specific recipient account (type + id + portal).
     *
     * @param  Builder<PortalNotification>  $query
     * @return Builder<PortalNotification>
     */
    public function scopeForRecipient(Builder $query, string $type, int $id, string $portal): Builder
    {
        return $query
            ->where('recipient_account_type', $type)
            ->where('recipient_account_id', $id)
            ->where('portal', $portal);
    }

    /**
     * Only unread (not yet read AND not dismissed AND not expired).
     *
     * @param  Builder<PortalNotification>  $query
     * @return Builder<PortalNotification>
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query
            ->whereNull('read_at')
            ->whereNull('dismissed_at')
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    /**
     * Active (not dismissed, not expired — may or may not be read).
     *
     * @param  Builder<PortalNotification>  $query
     * @return Builder<PortalNotification>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->whereNull('dismissed_at')
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }
}

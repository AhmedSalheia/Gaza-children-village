<?php

declare(strict_types=1);

namespace Modules\Notifications\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Per-account notification preference override.
 *
 * When no row exists for a (account_type, account_id, portal, notification_type)
 * tuple, the defaults apply: in-app enabled, email disabled.
 *
 * Table: notification_preferences
 */
final class NotificationPreference extends Model
{
    protected $table = 'notification_preferences';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'email_enabled' => 'boolean',
        ];
    }
}

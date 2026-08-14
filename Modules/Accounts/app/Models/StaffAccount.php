<?php

declare(strict_types=1);

namespace Modules\Accounts\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Modules\Accounts\Database\Factories\StaffAccountFactory;
use Modules\Accounts\Enums\AccountStatus;
use Modules\Accounts\Services\LoginIdentifierNormalizer;

/**
 * Staff account for the Staff Portal.
 *
 * Login credential: normalized unique username.
 * Authenticated only through the `staff` guard.
 * Only Active accounts may authenticate or retain an authenticated session.
 *
 * Account existence is optional. A StaffProfile record must never automatically create a
 * StaffAccount. Guards and other non-login staff appear in staff lists without an account.
 *
 * DEFERRED (F13): When StaffProfile is introduced, a nullable profile foreign key will be
 * added to this table. No such column exists in F09.
 */
final class StaffAccount extends Authenticatable
{
    /** @use HasFactory<StaffAccountFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'password',
        'status',
        'activated_at',
        'suspended_at',
        'locked_at',
        'revoked_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'status' => AccountStatus::class,
            'password' => 'hashed',
            'activated_at' => 'datetime',
            'suspended_at' => 'datetime',
            'locked_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        $normalizer = new LoginIdentifierNormalizer;

        self::creating(function (self $account) use ($normalizer): void {
            $account->username = $normalizer->normalize($account->username);
        });

        self::updating(function (self $account) use ($normalizer): void {
            if ($account->isDirty('username')) {
                $account->username = $normalizer->normalize($account->username);
            }
        });
    }

    protected static function newFactory(): StaffAccountFactory
    {
        return StaffAccountFactory::new();
    }
}

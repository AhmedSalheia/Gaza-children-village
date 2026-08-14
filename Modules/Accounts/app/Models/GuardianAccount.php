<?php

declare(strict_types=1);

namespace Modules\Accounts\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Modules\Accounts\Database\Factories\GuardianAccountFactory;
use Modules\Accounts\Enums\AccountStatus;
use Modules\Accounts\Services\LoginIdentifierNormalizer;

/**
 * Guardian account for the Parent/Student Portal.
 *
 * Login credential: opaque normalized login_identifier (not a username or national ID).
 * Authenticated only through the `guardian` guard.
 * Only Active accounts may authenticate or retain an authenticated session.
 *
 * The account belongs to a parent or authorized guardian, never to a student directly.
 * Guardian authentication grants no student access until a future verified guardian-student
 * relationship exists (deferred to F13/F15).
 *
 * National-ID resolution direction: Future F11/F13 work will resolve submitted national IDs
 * through approved person-identifier records into the login_identifier. No national_id column
 * is added in F09.
 *
 * DEFERRED (F15): When guardian Person records are introduced, a nullable profile foreign key
 * will be added to this table. No such column exists in F09.
 */
final class GuardianAccount extends Authenticatable
{
    /** @use HasFactory<GuardianAccountFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'login_identifier',
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
            $account->login_identifier = $normalizer->normalize($account->login_identifier);
        });

        self::updating(function (self $account) use ($normalizer): void {
            if ($account->isDirty('login_identifier')) {
                $account->login_identifier = $normalizer->normalize($account->login_identifier);
            }
        });
    }

    protected static function newFactory(): GuardianAccountFactory
    {
        return GuardianAccountFactory::new();
    }
}

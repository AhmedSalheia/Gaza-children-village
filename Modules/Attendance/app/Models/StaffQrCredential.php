<?php

declare(strict_types=1);

namespace Modules\Attendance\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * QR credential issued to a staff member.
 *
 * NEVER expose token_hash via API responses or logs. The plaintext token
 * is shown exactly once at generation and never stored in this table.
 * The token_hash is HMAC-SHA256(plaintext, config('app.key')).
 */
final class StaffQrCredential extends Model
{
    protected $table = 'staff_qr_credentials';

    protected $fillable = [];

    /** @var list<string> Fields excluded from serialization (never leak the hash) */
    protected $hidden = ['token_hash'];

    /** @var array<string, string> */
    protected $casts = [
        'is_active' => 'boolean',
        'issued_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }
}

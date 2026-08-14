<?php

declare(strict_types=1);

namespace Modules\Accounts\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Grant row linking an AdministrativeAccount to a role code.
 *
 * role_id is a plain integer referencing Authorization.Role.
 * No ORM cross-module relationship — callers resolve the role via the
 * Authorization module's public actions/data surfaces.
 */
final class AdministrativeAccountRole extends Model
{
    protected $fillable = [];

    protected $casts = [
        'granted_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    /** @return BelongsTo<AdministrativeAccount, $this> */
    public function administrativeAccount(): BelongsTo
    {
        return $this->belongsTo(AdministrativeAccount::class);
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null;
    }

    /**
     * @param  Builder<AdministrativeAccountRole>  $query
     * @return Builder<AdministrativeAccountRole>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at');
    }
}

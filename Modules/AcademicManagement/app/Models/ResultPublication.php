<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Versioned publication header for a student-result snapshot.
 *
 * institution_semester_id, class_group_id (nullable), publisher_staff_profile_id,
 * revoked_by_staff_profile_id are plain cross-module integers (no DB FK).
 *
 * Lifecycle: published → revoked (terminal).
 * Supersession: a republish writes a new row and sets superseded_by_id on the old one.
 */
final class ResultPublication extends Model
{
    protected $table = 'result_publications';

    protected $fillable = [
        'institution_semester_id',
        'class_group_id',
        'version',
        'superseded_by_id',
        'status',
        'published_at',
        'publisher_staff_profile_id',
        'revoked_at',
        'revoke_reason',
        'revoked_by_staff_profile_id',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'published_at' => 'datetime',
        'revoked_at' => 'datetime',
        'version' => 'integer',
    ];

    /** @return HasMany<ResultPublicationRow, $this> */
    public function rows(): HasMany
    {
        return $this->hasMany(ResultPublicationRow::class);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isRevoked(): bool
    {
        return $this->status === 'revoked';
    }
}

<?php

declare(strict_types=1);

namespace Modules\Documents\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A template family for one document type at one (optional) institution.
 *
 * A template has zero or more `DocumentTemplateVersion` rows. At most one
 * version is `active` at any time; `active_version_id` points to it.
 *
 * Hierarchy:
 *   organization-wide template  → organization_id set, institution_id null
 *   institution override        → institution_id set (organization_id inherited)
 *
 * `branding_config` — institution-specific logo URL, colour hex, signature image
 * URL, and any other rendering overrides for the PDF engine.
 */
final class DocumentTemplate extends Model
{
    /** @var list<string> All columns excluded from mass assignment. */
    protected $guarded = ['*'];

    /** @var array<string, string> */
    protected $casts = [
        'ar_available' => 'boolean',
        'en_available' => 'boolean',
        'approval_required' => 'boolean',
        'branding_config' => 'array',
    ];

    /** @return HasMany<DocumentTemplateVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(DocumentTemplateVersion::class, 'template_id');
    }

    /** @return BelongsTo<DocumentTemplateVersion, $this> */
    public function activeVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplateVersion::class, 'active_version_id');
    }

    public function hasActiveVersion(): bool
    {
        return $this->active_version_id !== null;
    }
}

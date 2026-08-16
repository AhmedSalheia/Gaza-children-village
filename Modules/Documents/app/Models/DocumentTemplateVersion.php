<?php

declare(strict_types=1);

namespace Modules\Documents\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An immutable numbered version of a DocumentTemplate.
 *
 * Immutability contract:
 *   - `version_number` and `content_hash` are set on creation and never changed.
 *   - Active and archived versions must never be edited.
 *   - Only draft versions may be updated; activation transitions draft → active
 *     and records the approver_account_id.
 *   - DocumentTemplateVersionService enforces these rules at the application layer.
 *
 * `body` — UTF-8 HTML with `{{ dot.key }}` placeholders from the approved catalogue.
 *   No PHP, Blade, or JavaScript may be embedded. TemplatePlaceholderResolver
 *   validates the body against the catalogue before activation.
 *
 * `placeholder_catalogue` — JSON array of placeholder keys known at version-create
 *   time, for fast validation and documentation.
 *
 * `header_config` / `footer_config` — JSON objects:
 *   { "html": "<string>", "height_mm": 15 }
 *   Used by MpdfEngine to set page-level headers and footers.
 *
 * `status` — 'draft' | 'active' | 'archived'
 */
final class DocumentTemplateVersion extends Model
{
    /** @var list<string> All columns excluded from mass assignment — service uses direct property assignment. */
    protected $guarded = ['*'];

    /** @var array<string, string> */
    protected $casts = [
        'version_number' => 'integer',
        'placeholder_catalogue' => 'array',
        'header_config' => 'array',
        'footer_config' => 'array',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    /** @return BelongsTo<DocumentTemplate, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'template_id');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    /**
     * Guard: throws if this version is not in draft state.
     * Used by the service layer to enforce immutability of active/archived versions.
     */
    public function assertIsDraft(): void
    {
        if (! $this->isDraft()) {
            throw new \LogicException(
                "Template version #{$this->id} (v{$this->version_number}) is '{$this->status}' and cannot be modified."
            );
        }
    }
}

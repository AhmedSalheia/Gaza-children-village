<?php

declare(strict_types=1);

namespace Modules\Attachments\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a validated, privately stored file attachment.
 *
 * Rows are append-only. Only the `status` column may be updated (by the
 * virus scanner job). All identity and storage columns are immutable after
 * insert.
 *
 * Status lifecycle:
 *   quarantine → available   (scanner confirms clean)
 *   quarantine → rejected    (scanner detected threat)
 */
final class SecureAttachment extends Model
{
    /**
     * UUID primary key — always server-generated, never user-supplied.
     */
    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * No updated_at — attachment metadata is append-only.
     */
    public const UPDATED_AT = null;

    /**
     * Nothing is mass-assignable. All fields are set explicitly by
     * SecureAttachmentService.
     */
    protected $fillable = [];

    /**
     * Scope: only attachments available for download (scanner confirmed clean).
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', 'available');
    }

    /**
     * Scope: attachments owned by a given institution.
     * Always apply before serving a download to prevent cross-institution access.
     */
    public function scopeForInstitution(Builder $query, int $institutionId): Builder
    {
        return $query->where('institution_id', $institutionId);
    }

    /**
     * Scope: attachments in quarantine (pending scan).
     */
    public function scopeQuarantined(Builder $query): Builder
    {
        return $query->where('status', 'quarantine');
    }

    /**
     * Whether the attachment is ready to serve.
     */
    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    /**
     * Whether the attachment is waiting for a virus scan.
     */
    public function isQuarantined(): bool
    {
        return $this->status === 'quarantine';
    }

    /**
     * Links connecting this attachment to domain entities.
     */
    public function links(): HasMany
    {
        return $this->hasMany(AttachmentLink::class, 'attachment_id');
    }
}

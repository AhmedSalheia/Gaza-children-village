<?php

declare(strict_types=1);

namespace Modules\Attachments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Polymorphic join between a domain entity and a SecureAttachment.
 *
 * Rows are append-only. Links are never deleted; when a request is cancelled
 * or superseded, the linkable entity is soft-deleted but the attachment row
 * and its links remain for audit purposes.
 */
final class AttachmentLink extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [];

    public function attachment(): BelongsTo
    {
        return $this->belongsTo(SecureAttachment::class, 'attachment_id');
    }
}

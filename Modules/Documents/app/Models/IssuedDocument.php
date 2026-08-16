<?php

declare(strict_types=1);

namespace Modules\Documents\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable record of a successfully generated and stored PDF document.
 *
 * The storage_path references the private disk — never a public URL.
 * The verification_code is a 64-char high-entropy random string;
 * verification_code_hash is SHA-256(verification_code) for indexed lookup.
 *
 * Cancellation: sets cancelled_at + cancellation_reason; never deletes the row
 * or file (historical preservation requirement).
 *
 * Reissue: creates a new IssuedDocument; supersedes_id points from the old
 * document to the new one.
 *
 * Cross-module IDs are plain integers without DB foreign keys.
 */
final class IssuedDocument extends Model
{
    /** @var list<string> */
    protected $guarded = ['id'];

    /** @var array<string, string> */
    protected $casts = [
        'issued_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    /** @return BelongsTo<DocumentTemplateVersion, $this> */
    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplateVersion::class, 'template_version_id');
    }

    /** @return BelongsTo<StudentDocumentRequest, $this> */
    public function request(): BelongsTo
    {
        return $this->belongsTo(StudentDocumentRequest::class, 'request_id');
    }

    /** @return BelongsTo<IssuedDocument, $this> */
    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(IssuedDocument::class, 'supersedes_id');
    }

    // ── Status helpers ────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->cancelled_at === null;
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    /**
     * Return a verification-safe summary — NO PII fields (no student name,
     * national ID, marks, or guardian info).
     *
     * @return array<string, string|null>
     */
    public function verificationSummary(): array
    {
        return [
            'status' => $this->isCancelled() ? 'cancelled' : 'valid',
            'document_number' => $this->document_number,
            'document_type' => $this->document_type_code,
            'locale' => $this->locale,
            'issued_at' => $this->issued_at?->toDateString(),
            'cancelled_at' => $this->cancelled_at?->toDateString(),
        ];
    }
}

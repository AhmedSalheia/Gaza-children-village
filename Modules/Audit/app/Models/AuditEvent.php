<?php

declare(strict_types=1);

namespace Modules\Audit\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Immutable audit event record.
 *
 * Immutability contract (F18):
 *  - No update or delete operations via the application layer (no update(),
 *    save() after initial creation, or delete()).
 *  - Records are created via AuditRecorder; read via AuditReader.
 *  - $guarded protects all columns (nothing is $fillable) so mass-assignment
 *    cannot mutate stored events.
 *  - Timestamps are disabled in Eloquent (only recorded_at exists, set on INSERT).
 *
 * Redaction rule: before_state, after_state, and metadata MUST NOT contain
 * passwords, tokens, session IDs, or raw national IDs / contact numbers.
 * Callers must redact before passing JSON; AuditRecorder validates known
 * sensitive key patterns.
 */
final class AuditEvent extends Model
{
    /** No Eloquent updated_at; recorded_at is set by the DB. */
    public const UPDATED_AT = null;

    public const CREATED_AT = 'recorded_at';

    protected $table = 'audit_events';

    protected $guarded = ['id'];

    /** @var array<string, string> */
    protected $casts = [
        'before_state' => 'array',
        'after_state' => 'array',
        'metadata' => 'array',
        'recorded_at' => 'datetime',
    ];

    /**
     * Prevent any update to a written audit event.
     *
     * {@inheritdoc}
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new \LogicException('AuditEvent records are immutable and cannot be updated.');
    }

    /**
     * Prevent deletion of audit events at the application layer.
     *
     * {@inheritdoc}
     */
    public function delete(): ?bool
    {
        throw new \LogicException('AuditEvent records are immutable and cannot be deleted.');
    }
}

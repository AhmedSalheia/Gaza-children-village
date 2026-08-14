<?php

declare(strict_types=1);

namespace Modules\Audit\Contracts;

use Modules\Audit\Data\AuditEventPayload;
use Modules\Audit\Models\AuditEvent;

/**
 * Public surface of the Audit module for writing events.
 *
 * Intentionally append-only: no update or delete methods.
 * Implementations must enforce the redaction rules before persisting.
 */
interface AuditRecorder
{
    /**
     * Record an audit event and return the persisted model.
     *
     * The implementation is responsible for:
     *  - Assigning a stable UUID event_id.
     *  - Redacting known sensitive key patterns from before/after/metadata.
     *  - Setting recorded_at to now().
     *
     * Throws \InvalidArgumentException if a sensitive key is detected in payload.
     */
    public function record(AuditEventPayload $payload): AuditEvent;
}

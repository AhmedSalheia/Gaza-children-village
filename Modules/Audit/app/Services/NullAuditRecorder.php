<?php

declare(strict_types=1);

namespace Modules\Audit\Services;

use Modules\Audit\Contracts\AuditRecorder;
use Modules\Audit\Data\AuditEventPayload;
use Modules\Audit\Models\AuditEvent;

/**
 * No-op AuditRecorder for tests that do not care about audit event persistence.
 *
 * Swap in via:
 *   app()->instance(AuditRecorder::class, new NullAuditRecorder());
 *
 * Returns an unsaved AuditEvent instance (id = null) so callers that
 * immediately use the returned model reference still work.
 */
final class NullAuditRecorder implements AuditRecorder
{
    public function record(AuditEventPayload $payload): AuditEvent
    {
        $event = new AuditEvent;
        $event->event_id = 'null-'.uniqid('', true);
        $event->actor_type = $payload->actorType;
        $event->source_module = $payload->sourceModule;
        $event->action = $payload->action;

        return $event;
    }
}

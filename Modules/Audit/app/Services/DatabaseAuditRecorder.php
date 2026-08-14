<?php

declare(strict_types=1);

namespace Modules\Audit\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Audit\Contracts\AuditRecorder;
use Modules\Audit\Data\AuditEventPayload;
use Modules\Audit\Models\AuditEvent;

/**
 * Primary AuditRecorder implementation: writes to the audit_events table.
 *
 * Responsibilities:
 *  - Assigns a stable UUID event_id.
 *  - Enforces redaction rules (rejects payloads containing known sensitive keys).
 *  - Uses a raw DB insert (not Eloquent save) to bypass any accidental
 *    event-model hooks and guarantee INSERT-only behavior.
 */
final class DatabaseAuditRecorder implements AuditRecorder
{
    /**
     * Key patterns that MUST NOT appear in before_state, after_state, or metadata.
     */
    private const FORBIDDEN_KEY_PATTERNS = [
        'password', 'token', 'secret', 'session', 'national_id', 'contact',
        'phone', 'email', 'hash', 'fingerprint', 'plain',
    ];

    public function record(AuditEventPayload $payload): AuditEvent
    {
        $this->redactionGuard('before_state', $payload->beforeState);
        $this->redactionGuard('after_state', $payload->afterState);
        $this->redactionGuard('metadata', $payload->metadata);

        $eventId = (string) Str::uuid();
        $now = now();

        $event = new AuditEvent;
        $event->event_id = $eventId;
        $event->actor_type = $payload->actorType;
        $event->actor_account_id = $payload->actorAccountId;
        $event->portal = $payload->portal;
        $event->source_module = $payload->sourceModule;
        $event->action = $payload->action;
        $event->subject_type = $payload->subjectType;
        $event->subject_id = $payload->subjectId;
        $event->institution_id = $payload->institutionId;
        $event->institution_semester_id = $payload->institutionSemesterId;
        $event->operational_period_id = $payload->operationalPeriodId;
        $event->before_state = $payload->beforeState;
        $event->after_state = $payload->afterState;
        $event->change_reason = $payload->changeReason;
        $event->request_id = $payload->requestId;
        $event->ip_address = $payload->ipAddress;
        $event->metadata = $payload->metadata;
        $event->schema_version = $payload->schemaVersion;
        // recorded_at is set by DB default

        // Use getConnection()->table() insert for guaranteed immutability
        // (bypasses Eloquent update hooks completely).
        DB::table('audit_events')->insert([
            'event_id' => $eventId,
            'actor_type' => $payload->actorType,
            'actor_account_id' => $payload->actorAccountId,
            'portal' => $payload->portal,
            'source_module' => $payload->sourceModule,
            'action' => $payload->action,
            'subject_type' => $payload->subjectType,
            'subject_id' => $payload->subjectId,
            'institution_id' => $payload->institutionId,
            'institution_semester_id' => $payload->institutionSemesterId,
            'operational_period_id' => $payload->operationalPeriodId,
            'before_state' => $payload->beforeState ? json_encode($payload->beforeState) : null,
            'after_state' => $payload->afterState ? json_encode($payload->afterState) : null,
            'change_reason' => $payload->changeReason,
            'request_id' => $payload->requestId,
            'ip_address' => $payload->ipAddress,
            'schema_version' => $payload->schemaVersion,
            'metadata' => $payload->metadata ? json_encode($payload->metadata) : null,
            'recorded_at' => $now,
        ]);

        return AuditEvent::where('event_id', $eventId)->firstOrFail();
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    private function redactionGuard(string $field, ?array $data): void
    {
        if ($data === null) {
            return;
        }

        foreach (array_keys($data) as $key) {
            $keyLower = strtolower((string) $key);
            foreach (self::FORBIDDEN_KEY_PATTERNS as $pattern) {
                if (str_contains($keyLower, $pattern)) {
                    throw new \InvalidArgumentException(
                        "Audit payload field '{$field}' contains forbidden key '{$key}' (matches '{$pattern}'). "
                        .'Redact sensitive data before recording.'
                    );
                }
            }
        }
    }
}

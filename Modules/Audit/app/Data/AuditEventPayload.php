<?php

declare(strict_types=1);

namespace Modules\Audit\Data;

/**
 * Value object carrying all fields needed to record one audit event.
 *
 * All data must be pre-validated / pre-redacted by the caller.
 * Sensitive keys will be additionally checked by the AuditRecorder
 * implementation before persistence.
 */
final class AuditEventPayload
{
    /**
     * @param  string  $actorType  'administrative'|'staff'|'guardian'|'system'
     * @param  string  $sourceModule  Module name, e.g. 'Staff'
     * @param  string  $action  e.g. 'staff_position.assigned'
     * @param  int|null  $actorAccountId  null for system-generated events
     * @param  string|null  $portal  'admin'|'staff'|'guardian'|null
     * @param  string|null  $subjectType  e.g. 'StaffPosition'
     * @param  array<string, mixed>|null  $beforeState
     * @param  array<string, mixed>|null  $afterState
     * @param  string|null  $requestId  UUID for request correlation
     * @param  array<string, mixed>|null  $metadata  Non-sensitive supplemental data
     */
    public function __construct(
        public readonly string $actorType,
        public readonly string $sourceModule,
        public readonly string $action,
        public readonly ?int $actorAccountId = null,
        public readonly ?string $portal = null,
        public readonly ?string $subjectType = null,
        public readonly ?int $subjectId = null,
        public readonly ?int $institutionId = null,
        public readonly ?int $institutionSemesterId = null,
        public readonly ?int $operationalPeriodId = null,
        public readonly ?array $beforeState = null,
        public readonly ?array $afterState = null,
        public readonly ?string $changeReason = null,
        public readonly ?string $requestId = null,
        public readonly ?string $ipAddress = null,
        public readonly ?array $metadata = null,
        public readonly int $schemaVersion = 1,
    ) {}
}

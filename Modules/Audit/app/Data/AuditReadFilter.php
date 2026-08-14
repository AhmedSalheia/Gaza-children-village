<?php

declare(strict_types=1);

namespace Modules\Audit\Data;

/**
 * Immutable filter for AuditReader::query().
 *
 * All fields are optional; an empty filter returns the most recent 100 events
 * (callers should always supply at least one scope filter in production).
 */
final class AuditReadFilter
{
    /**
     * @param  int|null  $institutionId  Scope by institution.
     * @param  int|null  $actorAccountId  Scope by actor.
     * @param  string|null  $sourceModule  Filter by module name.
     * @param  string|null  $action  Filter by exact action string.
     * @param  \DateTimeInterface|null  $from  Inclusive lower bound on recorded_at.
     * @param  \DateTimeInterface|null  $until  Inclusive upper bound on recorded_at.
     * @param  int  $limit  Maximum rows returned (max 500).
     */
    public function __construct(
        public readonly ?int $institutionId = null,
        public readonly ?int $actorAccountId = null,
        public readonly ?string $sourceModule = null,
        public readonly ?string $action = null,
        public readonly ?\DateTimeInterface $from = null,
        public readonly ?\DateTimeInterface $until = null,
        public readonly int $limit = 100,
    ) {}
}

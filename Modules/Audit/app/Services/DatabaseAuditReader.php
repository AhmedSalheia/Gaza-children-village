<?php

declare(strict_types=1);

namespace Modules\Audit\Services;

use Illuminate\Support\Collection;
use Modules\Audit\Contracts\AuditReader;
use Modules\Audit\Data\AuditReadFilter;
use Modules\Audit\Models\AuditEvent;

/**
 * AuditReader implementation — reads from audit_events with scope filters.
 */
final class DatabaseAuditReader implements AuditReader
{
    private const MAX_LIMIT = 500;

    /** @return Collection<int, AuditEvent> */
    public function query(AuditReadFilter $filter): Collection
    {
        $limit = min($filter->limit, self::MAX_LIMIT);

        return AuditEvent::query()
            ->when($filter->institutionId !== null,
                fn ($q) => $q->where('institution_id', $filter->institutionId))
            ->when($filter->actorAccountId !== null,
                fn ($q) => $q->where('actor_account_id', $filter->actorAccountId))
            ->when($filter->sourceModule !== null,
                fn ($q) => $q->where('source_module', $filter->sourceModule))
            ->when($filter->action !== null,
                fn ($q) => $q->where('action', $filter->action))
            ->when($filter->from !== null,
                fn ($q) => $q->where('recorded_at', '>=', $filter->from->format('Y-m-d H:i:s')))
            ->when($filter->until !== null,
                fn ($q) => $q->where('recorded_at', '<=', $filter->until->format('Y-m-d H:i:s')))
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function findByEventId(string $eventId): ?AuditEvent
    {
        return AuditEvent::where('event_id', $eventId)->first();
    }
}

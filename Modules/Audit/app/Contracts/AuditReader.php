<?php

declare(strict_types=1);

namespace Modules\Audit\Contracts;

use Illuminate\Support\Collection;
use Modules\Audit\Data\AuditReadFilter;
use Modules\Audit\Models\AuditEvent;

/**
 * Public surface of the Audit module for reading events.
 *
 * Returns immutable AuditEvent collection results.
 * No filtering by sensitive fields is exposed here; callers control scope
 * via AuditReadFilter (institution_id, actor_account_id, date range, action).
 */
interface AuditReader
{
    /**
     * @return Collection<int, AuditEvent>
     */
    public function query(AuditReadFilter $filter): Collection;

    public function findByEventId(string $eventId): ?AuditEvent;
}

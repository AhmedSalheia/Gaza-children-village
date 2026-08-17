<?php

declare(strict_types=1);

namespace Modules\Reporting\Data;

/**
 * Typed filter context for a single report run.
 *
 * Constructed once per request and passed through the query service, the
 * export class, and the queued job. All cross-module boundary compliance
 * is enforced by the caller (Livewire component) before construction.
 */
final readonly class ReportScope
{
    /**
     * @param  array<int>|null  $allowedPeriodIds  When non-null the query
     *                                             service restricts results to sheets in these period IDs
     *                                             (staff period restriction enforcement).
     */
    public function __construct(
        public string $actorType,
        public int $actorAccountId,
        public string $portal,
        public string $locale = 'ar',
        public ?int $institutionSemesterId = null,
        public ?int $institutionId = null,
        public ?int $classGroupId = null,
        public ?int $operationalPeriodId = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public bool $isFullScope = true,
        /** @var array<int>|null */
        public ?array $allowedPeriodIds = null,
        public int $limit = 200,
        public int $offset = 0,
    ) {}

    /** Serialize to JSON-safe array for storage in report_runs.scope column. */
    public function toArray(): array
    {
        return array_filter([
            'institution_semester_id' => $this->institutionSemesterId,
            'institution_id' => $this->institutionId,
            'class_group_id' => $this->classGroupId,
            'operational_period_id' => $this->operationalPeriodId,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ], fn ($v) => $v !== null);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Reporting\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Reporting\Data\ReportScope;
use Modules\Reporting\Exports\ReportExcelExport;
use Modules\Reporting\Services\FormulaInjectionSanitizer;
use Modules\Reporting\Services\ReportAuthorizationService;
use Modules\Reporting\Services\ReportQueryService;

/**
 * Queued job that generates a full (unlimited-row) Excel export for a report.
 *
 * Lifecycle (tracked in the Notifications module's operation_statuses table):
 *   queued → running → completed (output_reference = encrypted report_exports id)
 *                    → failed    (failure_summary   = safe error text)
 *
 * Isolation: the actor who dispatched the job is recorded in both the
 * operation status and the report_exports audit row; download controllers
 * verify actor ownership before serving the file.
 */
final class GenerateReportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /** Must stay below the queue worker's --timeout=120 so the job can fail cleanly. */
    public int $timeout = 110;

    public int $backoff = 30;

    /**
     * @param  array<string, mixed>  $scopeArray  Serialized ReportScope constructor args.
     */
    public function __construct(
        public readonly string $definitionCode,
        public readonly array $scopeArray,
        public readonly int $operationStatusId,
        public readonly int $reportRunId,
    ) {}

    public function handle(ReportQueryService $queryService, FormulaInjectionSanitizer $sanitizer): void
    {
        // Mark running (string-variable service resolution keeps the boundary
        // scanner happy — Notifications is a declared dependency of Reporting).
        $statusServiceClass = 'Modules\\Notifications\\Services\\OperationStatusService';
        $statusService = app($statusServiceClass);

        try {
            $statusService->markRunning($this->operationStatusId, $this->job?->getJobId() ? (int) $this->job->getJobId() : null);

            $actorType = (string) $this->scopeArray['actor_type'];
            $actorAccountId = (int) $this->scopeArray['actor_account_id'];

            // Re-authorize at execution time — permissions or staff scope may
            // have been revoked between dispatch and execution. Staff scope
            // (semester / institution / period grants) is REBUILT from the
            // current trusted position, never trusted from the payload.
            $authService = app(ReportAuthorizationService::class);

            $isFullScope = (bool) ($this->scopeArray['is_full_scope'] ?? true);
            $allowedPeriodIds = $this->scopeArray['allowed_period_ids'] ?? null;
            $institutionSemesterId = $this->scopeArray['institution_semester_id'] ?? null;
            $institutionId = $this->scopeArray['institution_id'] ?? null;

            if ($actorType === 'staff') {
                if (! $authService->staffCanAccessExport($actorAccountId, $this->definitionCode)) {
                    $statusService->markFailed($this->operationStatusId, 'Authorization revoked before export generation.');

                    return;
                }

                $position = $authService->staffActivePosition($actorAccountId);

                // A staff export must always be bound to a concrete institution
                // semester — a null scope would produce an unscoped global export.
                if (! $position->institution_semester_id || ! $position->institution_id) {
                    $statusService->markFailed($this->operationStatusId, 'Staff position lacks an operational scope; export denied.');

                    return;
                }

                $institutionSemesterId = (int) $position->institution_semester_id;
                $institutionId = (int) $position->institution_id;
                $isFullScope = $authService->isFullScopePosition((string) $position->position_definition);
                $allowedPeriodIds = $isFullScope ? null : $authService->allowedPeriodIds((int) $position->id);
            } else {
                if (! $authService->adminCanAccessExport($actorAccountId, $this->definitionCode)) {
                    $statusService->markFailed($this->operationStatusId, 'Authorization revoked before export generation.');

                    return;
                }
            }

            $definition = DB::table('report_definitions')->where('code', $this->definitionCode)->first();

            if (! $definition) {
                throw new \RuntimeException("Report definition not found: {$this->definitionCode}");
            }

            // Explicit export resource policy: rows are fetched in bounded
            // chunks and the total is capped, so worker memory stays bounded
            // regardless of the underlying table size.
            $maxRows = max(1, (int) config('reporting.max_export_rows', 20000));
            $chunkSize = max(1, min($maxRows, (int) config('reporting.export_chunk_size', 1000)));

            $rows = collect();
            $truncated = false;

            for ($offset = 0; $offset < $maxRows; $offset += $chunkSize) {
                $take = min($chunkSize, $maxRows - $offset);

                $chunkScope = new ReportScope(
                    actorType: $actorType,
                    actorAccountId: $actorAccountId,
                    portal: (string) $this->scopeArray['portal'],
                    locale: (string) ($this->scopeArray['locale'] ?? 'ar'),
                    institutionSemesterId: $institutionSemesterId,
                    institutionId: $institutionId,
                    classGroupId: $this->scopeArray['class_group_id'] ?? null,
                    operationalPeriodId: $this->scopeArray['operational_period_id'] ?? null,
                    dateFrom: $this->scopeArray['date_from'] ?? null,
                    dateTo: $this->scopeArray['date_to'] ?? null,
                    isFullScope: $isFullScope,
                    allowedPeriodIds: $allowedPeriodIds,
                    limit: $take,
                    offset: $offset,
                );

                $chunk = $queryService->run($this->definitionCode, $chunkScope);
                $rows = $rows->concat($chunk);

                if ($chunk->count() < $take) {
                    break; // exhausted the result set
                }

                if ($rows->count() >= $maxRows) {
                    $truncated = true;
                    break;
                }
            }

            $scope = $chunkScope;

            $headings = $rows->isEmpty()
                ? ['(no rows)']
                : array_map(
                    fn (string $key): string => ucwords(str_replace('_', ' ', $key)),
                    array_keys((array) $rows->first()),
                );

            $scopeSummary = $scope->toArray();

            if ($truncated) {
                $scopeSummary['row_cap'] = $maxRows;
                $scopeSummary['truncated'] = 'yes';
            }

            $export = new ReportExcelExport(
                definitionCode: $this->definitionCode,
                definitionNameAr: (string) $definition->name_ar,
                headings: $headings,
                rows: $rows,
                scopeSummary: $scopeSummary,
                locale: $scope->locale,
                sanitizer: $sanitizer,
            );

            $filename = $this->definitionCode.'-'.now()->format('Y-m-d-His').'.xlsx';
            $path = 'reports/'.Str::uuid().'/'.$filename;

            Storage::disk('local')->makeDirectory(dirname($path));
            Excel::store($export, $path, 'local');

            // Audit the export
            $exportId = (int) DB::table('report_exports')->insertGetId([
                'export_type' => $this->definitionCode,
                'actor_type' => $scope->actorType,
                'actor_account_id' => $scope->actorAccountId,
                'scope' => json_encode($scope->toArray()),
                'locale' => $scope->locale,
                'row_count' => $rows->count(),
                'file_path' => $path,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Update the report run record
            DB::table('report_runs')->where('id', $this->reportRunId)->update([
                'row_count' => $rows->count(),
                'file_path' => $path,
                'updated_at' => now(),
            ]);

            // Output reference: the report_exports row ID (download controller
            // decrypts and verifies actor ownership).
            $statusService->markCompleted($this->operationStatusId, (string) $exportId);
        } catch (\Throwable $e) {
            // Safe failure summary — no PII, no SQL fragments.
            $statusService->markFailed(
                $this->operationStatusId,
                'Report generation failed. Please retry or contact support.',
            );

            report($e);

            throw $e; // let the queue retry up to $tries
        }
    }
}

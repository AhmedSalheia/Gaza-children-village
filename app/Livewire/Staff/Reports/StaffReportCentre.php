<?php

declare(strict_types=1);

namespace App\Livewire\Staff\Reports;

use App\Livewire\Staff\Concerns\HasStaffAuth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Reporting\Data\ReportScope;
use Modules\Reporting\Exports\ReportExcelExport;
use Modules\Reporting\Jobs\GenerateReportJob;
use Modules\Reporting\Services\FormulaInjectionSanitizer;
use Modules\Reporting\Services\ReportQueryService;

/**
 * Staff report centre — scoped, period-restricted report browser.
 *
 * Scope rules (enforced server-side on every query):
 *  - Institution semester is ALWAYS the staff member's own semester from
 *    staffScope(); the client cannot choose another one.
 *  - admin_only definitions are never listed or runnable.
 *  - Secretary / teacher positions (non-full-scope) are restricted to their
 *    granted operational periods; zero grants → empty results.
 */
class StaffReportCentre extends Component
{
    use HasStaffAuth;

    public const PREVIEW_LIMIT = 200;

    /** Row threshold above which exports run as a queued background job. */
    public const ASYNC_THRESHOLD = 500;

    public string $definitionCode = '';

    public int $classGroupId = 0;

    public string $dateFrom = '';

    public string $dateTo = '';

    public bool $canExport = false;

    public bool $hasRun = false;

    public ?int $pendingOperationId = null;

    public function mount(): void
    {
        abort_if(! $this->staffCan('report.read'), 403);

        $this->canExport = $this->staffCan('export.create');
    }

    // ── Definition browser (staff-visible only) ─────────────────────────────

    #[Computed]
    public function definitions(): Collection
    {
        return DB::table('report_definitions')
            ->where('admin_only', false)
            ->orderBy('report_family')
            ->orderBy('code')
            ->get()
            ->filter(fn (object $d): bool => $this->staffCan((string) $d->permission_key))
            ->groupBy('report_family');
    }

    #[Computed]
    public function selectedDefinition(): ?object
    {
        if ($this->definitionCode === '') {
            return null;
        }

        $def = DB::table('report_definitions')
            ->where('code', $this->definitionCode)
            ->where('admin_only', false)
            ->first();

        if (! $def || ! $this->staffCan((string) $def->permission_key)) {
            return null;
        }

        return $def;
    }

    #[Computed]
    public function filterSchema(): array
    {
        $def = $this->selectedDefinition;

        return $def ? (array) json_decode((string) $def->filter_schema, true) : [];
    }

    #[Computed]
    public function classGroups(): Collection
    {
        $scope = $this->staffScope();

        if (! $scope) {
            return collect();
        }

        $q = DB::table('class_groups')
            ->where('institution_semester_id', $scope['institution_semester_id'])
            ->orderBy('name_ar');

        if (! $this->isFullScopePosition()) {
            $allowed = $this->allowedPeriodIds();

            if (empty($allowed)) {
                return collect();
            }

            $q->whereIn('operational_period_id', $allowed);
        }

        return $q->get(['id', 'name_ar', 'code']);
    }

    // ── Report execution ────────────────────────────────────────────────────

    public function selectDefinition(string $code): void
    {
        $this->definitionCode = $code;
        $this->hasRun = false;
        unset($this->rows, $this->selectedDefinition, $this->filterSchema);
    }

    public function runReport(): void
    {
        // Re-authorize centre access on every action — grants can be revoked
        // after the component snapshot was issued.
        abort_if(! $this->staffCan('report.read'), 403);

        if (! $this->selectedDefinition) {
            return;
        }

        $this->hasRun = true;
        unset($this->rows);

        $scope = $this->buildScope(self::PREVIEW_LIMIT);

        if ($scope === null) {
            return;
        }

        DB::table('report_runs')->insert([
            'definition_code' => $this->definitionCode,
            'actor_type' => 'staff',
            'actor_account_id' => $this->staffAccountId(),
            'portal' => 'staff',
            'scope' => json_encode($scope->toArray()),
            'locale' => app()->getLocale(),
            'run_mode' => 'preview',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    #[Computed]
    public function rows(): Collection
    {
        if (! $this->hasRun || ! $this->selectedDefinition) {
            return collect();
        }

        $scope = $this->buildScope(self::PREVIEW_LIMIT);

        if ($scope === null) {
            return collect();
        }

        return app(ReportQueryService::class)->run($this->definitionCode, $scope);
    }

    #[Computed]
    public function headings(): array
    {
        if ($this->rows->isEmpty()) {
            return [];
        }

        return array_map(
            fn (string $key): string => ucwords(str_replace('_', ' ', $key)),
            array_keys((array) $this->rows->first()),
        );
    }

    // ── Export (sync ≤ threshold, queued above) ─────────────────────────────

    public function exportReport(): void
    {
        $def = $this->selectedDefinition;

        // Server-side re-authorization — never trust the hydrated public flag.
        if (! $def || ! $this->staffCan('report.read') || ! $this->staffCan('export.create')) {
            abort(403);
        }

        $probeScope = $this->buildScope(self::ASYNC_THRESHOLD + 1);

        if ($probeScope === null) {
            return;
        }

        // Bounded probe: fetch at most threshold+1 rows to decide sync vs async
        // without materializing an unbounded result set in the request.
        $rows = app(ReportQueryService::class)->run($this->definitionCode, $probeScope);

        if ($rows->count() > self::ASYNC_THRESHOLD) {
            $fullScope = $this->buildScope(PHP_INT_MAX);

            if ($fullScope !== null) {
                $this->dispatchAsyncExport($fullScope);
            }

            return;
        }

        $scope = $this->buildScope(PHP_INT_MAX);

        if ($scope === null) {
            return;
        }

        $headings = $rows->isEmpty()
            ? ['(no rows)']
            : array_map(
                fn (string $key): string => ucwords(str_replace('_', ' ', $key)),
                array_keys((array) $rows->first()),
            );

        $export = new ReportExcelExport(
            definitionCode: $this->definitionCode,
            definitionNameAr: (string) $def->name_ar,
            headings: $headings,
            rows: $rows,
            scopeSummary: $scope->toArray(),
            locale: $scope->locale,
            sanitizer: app(FormulaInjectionSanitizer::class),
        );

        $filename = $this->definitionCode.'-'.now()->format('Y-m-d').'.xlsx';
        $path = 'reports/'.Str::uuid().'/'.$filename;

        Storage::disk('local')->makeDirectory(dirname($path));
        Excel::store($export, $path, 'local');

        $exportId = (int) DB::table('report_exports')->insertGetId([
            'export_type' => $this->definitionCode,
            'actor_type' => 'staff',
            'actor_account_id' => $this->staffAccountId(),
            'scope' => json_encode($scope->toArray()),
            'locale' => $scope->locale,
            'row_count' => $rows->count(),
            'file_path' => $path,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('report_runs')->insert([
            'definition_code' => $this->definitionCode,
            'actor_type' => 'staff',
            'actor_account_id' => $this->staffAccountId(),
            'portal' => 'staff',
            'scope' => json_encode($scope->toArray()),
            'locale' => $scope->locale,
            'run_mode' => 'export',
            'row_count' => $rows->count(),
            'file_path' => $path,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->dispatch('start-download', url: route('staff.reports.download', [
            'export' => encrypt($exportId),
            'name' => $filename,
        ]));
    }

    // ── Async export (queued job + polling) ─────────────────────────────────

    private function dispatchAsyncExport(ReportScope $scope): void
    {
        $statusServiceClass = 'Modules\\Notifications\\Services\\OperationStatusService';
        $statusService = app($statusServiceClass);

        $operation = $statusService->create(
            'staff',
            $this->staffAccountId(),
            'staff',
            'report_export:'.$this->definitionCode,
            $scope->toArray(),
        );

        $runId = (int) DB::table('report_runs')->insertGetId([
            'definition_code' => $this->definitionCode,
            'actor_type' => 'staff',
            'actor_account_id' => $this->staffAccountId(),
            'portal' => 'staff',
            'scope' => json_encode($scope->toArray()),
            'locale' => $scope->locale,
            'run_mode' => 'export',
            'operation_status_id' => (int) $operation->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        GenerateReportJob::dispatch(
            $this->definitionCode,
            [
                'actor_type' => 'staff',
                'actor_account_id' => $this->staffAccountId(),
                'portal' => 'staff',
                'locale' => $scope->locale,
                'institution_semester_id' => $scope->institutionSemesterId,
                'institution_id' => $scope->institutionId,
                'class_group_id' => $scope->classGroupId,
                'operational_period_id' => $scope->operationalPeriodId,
                'date_from' => $scope->dateFrom,
                'date_to' => $scope->dateTo,
                'is_full_scope' => $scope->isFullScope,
                'allowed_period_ids' => $scope->allowedPeriodIds,
            ],
            (int) $operation->id,
            $runId,
        );

        $this->pendingOperationId = (int) $operation->id;
    }

    #[Computed]
    public function pendingOperation(): ?object
    {
        if ($this->pendingOperationId === null) {
            return null;
        }

        return DB::table('operation_statuses')
            ->where('id', $this->pendingOperationId)
            ->where('actor_type', 'staff')
            ->where('actor_account_id', $this->staffAccountId())
            ->first();
    }

    public function downloadCompletedExport(): void
    {
        // Re-authorize at retrieval time — grants can be revoked while the job runs.
        abort_if(! $this->staffCan('report.read') || ! $this->staffCan('export.create'), 403);

        $op = $this->pendingOperation;

        if (! $op || $op->status !== 'completed' || ! $op->output_reference) {
            return;
        }

        $exportId = (int) $op->output_reference;

        // Ownership check: export row must belong to this staff account
        $export = DB::table('report_exports')
            ->where('id', $exportId)
            ->where('actor_type', 'staff')
            ->where('actor_account_id', $this->staffAccountId())
            ->first();

        if (! $export) {
            return;
        }

        $this->pendingOperationId = null;

        $this->dispatch('start-download', url: route('staff.reports.download', [
            'export' => encrypt($exportId),
            'name' => basename((string) $export->file_path),
        ]));
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Build the scope from the staff member's own position — never from
     * client input. Returns null when the staff member has no active
     * position (no data access at all).
     */
    private function buildScope(int $limit): ?ReportScope
    {
        $staffScope = $this->staffScope();

        // Deny when either trusted scope value is missing — a position with a
        // null semester/institution must never yield an unscoped global query.
        if (! $staffScope
            || ! $staffScope['institution_id']
            || ! $staffScope['institution_semester_id']) {
            return null;
        }

        $isFullScope = $this->isFullScopePosition();
        $allowedPeriodIds = $isFullScope ? null : $this->allowedPeriodIds();

        $schema = $this->filterSchema;

        return new ReportScope(
            actorType: 'staff',
            actorAccountId: $this->staffAccountId(),
            portal: 'staff',
            locale: app()->getLocale(),
            institutionSemesterId: (int) $staffScope['institution_semester_id'],
            institutionId: $staffScope['institution_id'],
            classGroupId: in_array('class_group_id', $schema, true) && $this->classGroupId > 0 ? $this->classGroupId : null,
            dateFrom: in_array('date_from', $schema, true) && $this->dateFrom !== '' ? $this->dateFrom : null,
            dateTo: in_array('date_to', $schema, true) && $this->dateTo !== '' ? $this->dateTo : null,
            isFullScope: $isFullScope,
            allowedPeriodIds: $allowedPeriodIds,
            limit: $limit,
        );
    }

    public function render(): View
    {
        return view('livewire.staff.reports.staff-report-centre')
            ->layout('layouts.staff');
    }
}

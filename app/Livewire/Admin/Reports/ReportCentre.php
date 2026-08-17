<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Reports;

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
 * Admin report centre — browse all report families, run on-screen previews
 * (max 200 rows) and export to Excel (sync for small result sets, queued
 * background job for large ones).
 *
 * Access requires report.read; each definition is additionally gated by its
 * own permission_key, and export actions require export.create.
 */
class ReportCentre extends Component
{
    /** Row threshold above which exports run as a queued background job. */
    public const ASYNC_THRESHOLD = 500;

    public const PREVIEW_LIMIT = 200;

    public string $definitionCode = '';

    public int $semesterId = 0;

    public int $classGroupId = 0;

    public int $periodId = 0;

    public string $dateFrom = '';

    public string $dateTo = '';

    public bool $canExport = false;

    public bool $hasRun = false;

    public ?int $pendingOperationId = null;

    public function mount(): void
    {
        abort_if(! $this->adminCan('report.read'), 403);

        $this->canExport = $this->adminCan('export.create');

        $sem = DB::table('institution_semesters')
            ->where('status', 'open')
            ->orderByDesc('id')
            ->first(['id']);

        if ($sem) {
            $this->semesterId = (int) $sem->id;
        }
    }

    // ── Definition browser ──────────────────────────────────────────────────

    #[Computed]
    public function definitions(): Collection
    {
        return DB::table('report_definitions')
            ->orderBy('report_family')
            ->orderBy('code')
            ->get()
            ->filter(fn (object $d): bool => $this->adminCan((string) $d->permission_key))
            ->groupBy('report_family');
    }

    #[Computed]
    public function selectedDefinition(): ?object
    {
        if ($this->definitionCode === '') {
            return null;
        }

        $def = DB::table('report_definitions')->where('code', $this->definitionCode)->first();

        if (! $def || ! $this->adminCan((string) $def->permission_key)) {
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

    // ── Filter option lists ─────────────────────────────────────────────────

    #[Computed]
    public function semesters(): Collection
    {
        return DB::table('institution_semesters as is_')
            ->join('institutions as i', 'i.id', '=', 'is_.institution_id')
            ->join('semesters as s', 's.id', '=', 'is_.semester_id')
            ->whereIn('is_.status', ['open', 'archived'])
            ->orderByDesc('is_.id')
            ->get(['is_.id', 'i.name_ar as institution_name', 's.name_ar as semester_name', 'is_.status']);
    }

    #[Computed]
    public function classGroups(): Collection
    {
        if ($this->semesterId === 0) {
            return collect();
        }

        return DB::table('class_groups')
            ->where('institution_semester_id', $this->semesterId)
            ->orderBy('name_ar')
            ->get(['id', 'name_ar', 'code']);
    }

    #[Computed]
    public function operationalPeriods(): Collection
    {
        if ($this->semesterId === 0) {
            return collect();
        }

        return DB::table('operational_periods')
            ->where('institution_semester_id', $this->semesterId)
            ->orderBy('sequence')
            ->get(['id', 'name_ar', 'code']);
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
        abort_if(! $this->adminCan('report.read'), 403);

        if (! $this->selectedDefinition) {
            return;
        }

        $this->hasRun = true;
        unset($this->rows);

        // Audit the preview run
        DB::table('report_runs')->insert([
            'definition_code' => $this->definitionCode,
            'actor_type' => 'admin',
            'actor_account_id' => (int) auth('admin')->id(),
            'portal' => 'admin',
            'scope' => json_encode($this->buildScope(self::PREVIEW_LIMIT)->toArray()),
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

        return app(ReportQueryService::class)->run(
            $this->definitionCode,
            $this->buildScope(self::PREVIEW_LIMIT),
        );
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

    // ── Export ──────────────────────────────────────────────────────────────

    public function exportReport(): void
    {
        $def = $this->selectedDefinition;

        // Server-side re-authorization — never trust the hydrated public flag.
        if (! $def || ! $this->adminCan('report.read') || ! $this->adminCan('export.create')) {
            abort(403);
        }

        $service = app(ReportQueryService::class);

        // Bounded probe: fetch at most threshold+1 rows to decide sync vs async
        // without materializing an unbounded result set in the request.
        $rows = $service->run($this->definitionCode, $this->buildScope(self::ASYNC_THRESHOLD + 1));

        if ($rows->count() > self::ASYNC_THRESHOLD) {
            $this->dispatchAsyncExport($this->buildScope(PHP_INT_MAX));

            return;
        }

        $scope = $this->buildScope(PHP_INT_MAX);

        // Sync path — small result set
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
            'actor_type' => 'admin',
            'actor_account_id' => (int) auth('admin')->id(),
            'scope' => json_encode($scope->toArray()),
            'locale' => $scope->locale,
            'row_count' => $rows->count(),
            'file_path' => $path,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('report_runs')->insert([
            'definition_code' => $this->definitionCode,
            'actor_type' => 'admin',
            'actor_account_id' => (int) auth('admin')->id(),
            'portal' => 'admin',
            'scope' => json_encode($scope->toArray()),
            'locale' => $scope->locale,
            'run_mode' => 'export',
            'row_count' => $rows->count(),
            'file_path' => $path,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->dispatch('start-download', url: route('admin.reports.download', [
            'export' => encrypt($exportId),
            'name' => $filename,
        ]));
    }

    private function dispatchAsyncExport(ReportScope $scope): void
    {
        $statusServiceClass = 'Modules\\Notifications\\Services\\OperationStatusService';
        $statusService = app($statusServiceClass);

        $operation = $statusService->create(
            'admin',
            (int) auth('admin')->id(),
            'admin',
            'report_export:'.$this->definitionCode,
            $scope->toArray(),
        );

        $runId = (int) DB::table('report_runs')->insertGetId([
            'definition_code' => $this->definitionCode,
            'actor_type' => 'admin',
            'actor_account_id' => (int) auth('admin')->id(),
            'portal' => 'admin',
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
                'actor_type' => 'admin',
                'actor_account_id' => (int) auth('admin')->id(),
                'portal' => 'admin',
                'locale' => $scope->locale,
                'institution_semester_id' => $scope->institutionSemesterId,
                'institution_id' => $scope->institutionId,
                'class_group_id' => $scope->classGroupId,
                'operational_period_id' => $scope->operationalPeriodId,
                'date_from' => $scope->dateFrom,
                'date_to' => $scope->dateTo,
                'is_full_scope' => true,
                'allowed_period_ids' => null,
            ],
            (int) $operation->id,
            $runId,
        );

        $this->pendingOperationId = (int) $operation->id;
    }

    // ── Job status polling ──────────────────────────────────────────────────

    #[Computed]
    public function pendingOperation(): ?object
    {
        if ($this->pendingOperationId === null) {
            return null;
        }

        return DB::table('operation_statuses')
            ->where('id', $this->pendingOperationId)
            ->where('actor_type', 'admin')
            ->where('actor_account_id', (int) auth('admin')->id())
            ->first();
    }

    public function downloadCompletedExport(): void
    {
        // Re-authorize at retrieval time — grants can be revoked while the job runs.
        abort_if(! $this->adminCan('report.read') || ! $this->adminCan('export.create'), 403);

        $op = $this->pendingOperation;

        if (! $op || $op->status !== 'completed' || ! $op->output_reference) {
            return;
        }

        $exportId = (int) $op->output_reference;

        // Ownership check: export row must belong to this admin
        $export = DB::table('report_exports')
            ->where('id', $exportId)
            ->where('actor_type', 'admin')
            ->where('actor_account_id', (int) auth('admin')->id())
            ->first();

        if (! $export) {
            return;
        }

        $this->pendingOperationId = null;

        $this->dispatch('start-download', url: route('admin.reports.download', [
            'export' => encrypt($exportId),
            'name' => basename((string) $export->file_path),
        ]));
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function buildScope(int $limit): ReportScope
    {
        $schema = $this->filterSchema;

        $semesterId = in_array('institution_semester_id', $schema, true) && $this->semesterId > 0
            ? $this->semesterId
            : null;

        // Definitions that disallow organization-wide scope MUST be bound to a
        // semester (and therefore to a single institution) — refuse otherwise.
        $def = $this->selectedDefinition;
        if ($def && ! (bool) $def->organization_scope_allowed && $semesterId === null) {
            abort(422, 'A semester scope is required for this report.');
        }

        // Derive the institution server-side from the trusted semester record;
        // never from client input.
        $institutionId = $semesterId
            ? (int) DB::table('institution_semesters')->where('id', $semesterId)->value('institution_id')
            : null;

        // Dependent filters must belong to the selected semester.
        $classGroupId = in_array('class_group_id', $schema, true) && $this->classGroupId > 0 ? $this->classGroupId : null;
        if ($classGroupId !== null && $semesterId !== null) {
            $valid = DB::table('class_groups')
                ->where('id', $classGroupId)
                ->where('institution_semester_id', $semesterId)
                ->exists();
            $classGroupId = $valid ? $classGroupId : null;
        }

        $periodId = in_array('operational_period_id', $schema, true) && $this->periodId > 0 ? $this->periodId : null;
        if ($periodId !== null && $semesterId !== null) {
            $valid = DB::table('operational_periods')
                ->where('id', $periodId)
                ->where('institution_semester_id', $semesterId)
                ->exists();
            $periodId = $valid ? $periodId : null;
        }

        return new ReportScope(
            actorType: 'admin',
            actorAccountId: (int) auth('admin')->id(),
            portal: 'admin',
            locale: app()->getLocale(),
            institutionSemesterId: $semesterId,
            institutionId: $institutionId,
            classGroupId: $classGroupId,
            operationalPeriodId: $periodId,
            dateFrom: in_array('date_from', $schema, true) && $this->dateFrom !== '' ? $this->dateFrom : null,
            dateTo: in_array('date_to', $schema, true) && $this->dateTo !== '' ? $this->dateTo : null,
            isFullScope: true,
            allowedPeriodIds: null,
            limit: $limit,
        );
    }

    private function adminCan(string $permission): bool
    {
        $accountId = auth('admin')->id();

        if (! $accountId) {
            return false;
        }

        return DB::table('administrative_account_roles as aar')
            ->join('role_permissions as rp', 'rp.role_id', '=', 'aar.role_id')
            ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
            ->where('aar.administrative_account_id', $accountId)
            ->whereNull('aar.revoked_at')
            ->where('p.key', $permission)
            ->exists();
    }

    public function render(): View
    {
        return view('livewire.admin.reports.report-centre')
            ->layout('layouts.admin');
    }
}

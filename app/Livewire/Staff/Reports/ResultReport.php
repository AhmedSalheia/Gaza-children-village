<?php

declare(strict_types=1);

namespace App\Livewire\Staff\Reports;

use App\Exports\MarksCompletionExport;
use App\Exports\ResultReportExport;
use App\Livewire\Staff\Concerns\HasStaffAuth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Authorization\App\Data\PermissionKey;

class ResultReport extends Component
{
    use HasStaffAuth;

    public string $reportType = 'completion';

    public int $classGroupId = 0;

    public bool $canExport = false;

    public function mount(): void
    {
        $this->requirePermission(PermissionKey::RESULT_REPORT_READ);

        $this->canExport = $this->staffCan(
            PermissionKey::RESULT_REPORT_EXPORT
        );
    }

    #[Computed]
    public function classGroups(): Collection
    {
        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            return collect();
        }

        $query = DB::table('class_groups as cg')
            ->where(
                'cg.institution_semester_id',
                $scope['institution_semester_id']
            )
            ->orderBy('cg.name_ar');

        if (! $this->isFullScopePosition()) {
            $allowed = $this->allowedPeriodIds();

            if (empty($allowed)) {
                return collect();
            }

            $query->whereIn(
                'cg.operational_period_id',
                $allowed
            );
        }

        return $query->get([
            'cg.id',
            'cg.name_ar',
            'cg.code',
        ]);
    }

    #[Computed]
    public function completionRows(): Collection
    {
        if ($this->reportType !== 'completion') {
            return collect();
        }

        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            return collect();
        }

        $query = DB::table('mark_sheets as ms')
            ->join(
                'class_groups as cg',
                'cg.id',
                '=',
                'ms.class_group_id'
            )
            ->join(
                'institution_subject_offerings as iso',
                'iso.id',
                '=',
                'ms.subject_offering_id'
            )
            ->join(
                'subjects as s',
                's.id',
                '=',
                'iso.subject_id'
            )
            ->join(
                'teaching_assignments as ta',
                'ta.id',
                '=',
                'ms.teaching_assignment_id'
            )
            ->join(
                'staff_profiles as sp',
                'sp.id',
                '=',
                'ta.staff_profile_id'
            )
            ->join(
                'people as p',
                'p.id',
                '=',
                'sp.person_id'
            )
            ->where(
                'ms.institution_semester_id',
                $scope['institution_semester_id']
            )
            ->whereNull('ms.superseded_by_id')
            ->orderBy('cg.name_ar')
            ->orderBy('s.name_ar');

        if (! $this->isFullScopePosition()) {
            $allowed = $this->allowedPeriodIds();

            if (empty($allowed)) {
                return collect();
            }

            $query->whereIn(
                'cg.operational_period_id',
                $allowed
            );
        }

        if ($this->classGroupId > 0) {
            $query->where(
                'ms.class_group_id',
                $this->classGroupId
            );
        }

        return $query->select(
            'ms.id as sheet_id',
            'cg.name_ar as class_group_name',
            's.name_ar as subject_name',
            'p.full_name_ar as teacher_name',
            'ms.status',
            'ms.version',
            'ms.submitted_at',
            'ms.approved_at',
        )->get();
    }

    #[Computed]
    public function completionSummary(): object
    {
        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            return $this->emptyCompletionSummary();
        }

        $query = DB::table('mark_sheets as ms')
            ->join(
                'class_groups as cg',
                'cg.id',
                '=',
                'ms.class_group_id'
            )
            ->where(
                'ms.institution_semester_id',
                $scope['institution_semester_id']
            )
            ->whereNull('ms.superseded_by_id');

        if (! $this->isFullScopePosition()) {
            $allowed = $this->allowedPeriodIds();

            if (empty($allowed)) {
                return $this->emptyCompletionSummary();
            }

            $query->whereIn(
                'cg.operational_period_id',
                $allowed
            );
        }

        if ($this->classGroupId > 0) {
            $query->where(
                'ms.class_group_id',
                $this->classGroupId
            );
        }

        $map = $query
            ->selectRaw('ms.status, count(*) as cnt')
            ->groupBy('ms.status')
            ->pluck('cnt', 'status')
            ->toArray();

        $total = array_sum($map);

        return (object) [
            'total' => $total,
            'draft' => (int) ($map['draft'] ?? 0),
            'submitted' => (int) ($map['submitted'] ?? 0),
            'verified' => (int) ($map['verified'] ?? 0),
            'returned' => (int) ($map['returned'] ?? 0),
            'approved' => (int) ($map['approved'] ?? 0),
        ];
    }

    #[Computed]
    public function resultRows(): Collection
    {
        if ($this->reportType !== 'results') {
            return collect();
        }

        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            return collect();
        }

        $query = DB::table('result_publication_rows as rpr')
            ->join(
                'result_publications as rp',
                'rp.id',
                '=',
                'rpr.result_publication_id'
            )
            ->join(
                'class_groups as cg',
                'cg.id',
                '=',
                'rp.class_group_id'
            )
            ->join(
                'student_enrollments as se',
                'se.id',
                '=',
                'rpr.enrollment_id'
            )
            ->join(
                'student_profiles as sp',
                'sp.id',
                '=',
                'se.student_profile_id'
            )
            ->join(
                'people as p',
                'p.id',
                '=',
                'sp.person_id'
            )
            ->join(
                'institution_subject_offerings as iso',
                'iso.id',
                '=',
                'rpr.subject_offering_id'
            )
            ->join(
                'subjects as s',
                's.id',
                '=',
                'iso.subject_id'
            )
            ->where(
                'rp.institution_semester_id',
                $scope['institution_semester_id']
            )
            ->where('rp.status', 'published')
            ->whereNull('rp.superseded_by_id')
            ->orderBy('cg.name_ar')
            ->orderBy('p.full_name_ar')
            ->orderBy('s.name_ar');

        if (! $this->isFullScopePosition()) {
            $allowed = $this->allowedPeriodIds();

            if (empty($allowed)) {
                return collect();
            }

            $query->whereIn(
                'cg.operational_period_id',
                $allowed
            );
        }

        if ($this->classGroupId > 0) {
            $query->where(
                'rp.class_group_id',
                $this->classGroupId
            );
        }

        return $query->select(
            'cg.name_ar as class_group_name',
            'p.full_name_ar as student_name',
            'sp.student_code',
            's.name_ar as subject_name',
            'rpr.normalized_score',
            'rpr.grade_code',
            'rpr.completeness_status',
        )->limit(500)->get();
    }

    /**
     * ترجمة أسماء الحقول بدون استخدام report_fields.
     */
    public function translateField(string $field): string
    {
        return match ($field) {
            'class_group',
            'class_group_name'
                => __('ui.class_group'),

            'student_name',
            'name'
                => __('ui.student_name'),

            'student_code',
            'student_id'
                => __('ui.student_code'),

            'subject',
            'subject_name'
                => __('ui.subject'),

            'teacher_name',
            'teacher'
                => __('ui.teacher'),

            'score',
            'normalized_score',
            'score_100'
                => __('ui.score_100'),

            'grade',
            'grade_code'
                => __('ui.grade'),

            'completeness',
            'completeness_status'
                => __('ui.completeness'),

            'status'
                => __('ui.status'),

            'version'
                => __('ui.version'),

            'submitted_at'
                => __('ui.submitted_at'),

            'approved_at'
                => __('ui.approved_at'),

            'date'
                => __('ui.date'),

            'created_at'
                => __('ui.created_at'),

            'updated_at'
                => __('ui.updated_at'),

            'total'
                => __('ui.total'),

            'draft'
                => __('ui.draft'),

            'submitted'
                => __('ui.submitted'),

            'verified'
                => __('ui.verified'),

            'returned'
                => __('ui.returned'),

            'approved'
                => __('ui.approved'),

            default
                => ucwords(
                    str_replace('_', ' ', $field)
                ),
        };
    }

    /**
     * ترجمة حالات أوراق العلامات.
     */
    public function translateStatus(?string $status): string
    {
        if ($status === null || $status === '') {
            return '';
        }

        return match ($status) {
            'draft'
                => __('ui.draft'),

            'submitted'
                => __('ui.submitted'),

            'verified'
                => __('ui.verified'),

            'returned'
                => __('ui.returned'),

            'approved'
                => __('ui.approved'),

            default
                => $status,
        };
    }

    /**
     * ترجمة حالة اكتمال النتائج.
     */
    public function translateCompleteness(?string $status): string
    {
        if ($status === null || $status === '') {
            return '';
        }

        return match ($status) {
            'complete'
                => __('ui.complete'),

            'incomplete'
                => __('ui.incomplete'),

            'all_absent'
                => __('ui.all_absent'),

            'no_assessments'
                => __('ui.no_assessments'),

            default
                => $status,
        };
    }

    private function emptyCompletionSummary(): object
    {
        return (object) [
            'total' => 0,
            'draft' => 0,
            'submitted' => 0,
            'verified' => 0,
            'returned' => 0,
            'approved' => 0,
        ];
    }

    public function exportCompletion(): void
    {
        if (! $this->canExport) {
            return;
        }

        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            return;
        }

        $allowedPeriodIds = $this->isFullScopePosition()
            ? null
            : $this->allowedPeriodIds();

        if (
            $allowedPeriodIds !== null
            && count($allowedPeriodIds) === 0
        ) {
            return;
        }

        $export = new MarksCompletionExport(
            institutionSemesterId: $scope['institution_semester_id'],
            classGroupId: $this->classGroupId > 0
                ? $this->classGroupId
                : null,
            allowedPeriodIds: $allowedPeriodIds,
        );

        $rows = $export->collection();

        $filename = 'marks-completion-'
            . now()->format('Y-m-d')
            . '.xlsx';

        $path = 'reports/'
            . Str::uuid()
            . '/'
            . $filename;

        Storage::disk('local')->makeDirectory(
            dirname($path)
        );

        Excel::store(
            $export,
            $path,
            'local'
        );

        $exportId = $this->auditExport(
            'marks_completion',
            [
                'institution_semester_id'
                    => $scope['institution_semester_id'],

                'class_group_id'
                    => $this->classGroupId,
            ],
            $rows->count(),
            $path
        );

        $this->dispatch(
            'start-download',
            url: route(
                'staff.reports.download',
                [
                    'export' => encrypt($exportId),
                    'name' => $filename,
                ]
            )
        );
    }

    public function exportResults(): void
    {
        if (! $this->canExport) {
            return;
        }

        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            return;
        }

        $allowedPeriodIds = $this->isFullScopePosition()
            ? null
            : $this->allowedPeriodIds();

        if (
            $allowedPeriodIds !== null
            && count($allowedPeriodIds) === 0
        ) {
            return;
        }

        $export = new ResultReportExport(
            institutionSemesterId: $scope['institution_semester_id'],
            classGroupId: $this->classGroupId > 0
                ? $this->classGroupId
                : null,
            allowedPeriodIds: $allowedPeriodIds,
        );

        $rows = $export->collection();

        $filename = 'results-report-'
            . now()->format('Y-m-d')
            . '.xlsx';

        $path = 'reports/'
            . Str::uuid()
            . '/'
            . $filename;

        Storage::disk('local')->makeDirectory(
            dirname($path)
        );

        Excel::store(
            $export,
            $path,
            'local'
        );

        $exportId = $this->auditExport(
            'result_report',
            [
                'institution_semester_id'
                    => $scope['institution_semester_id'],

                'class_group_id'
                    => $this->classGroupId,
            ],
            $rows->count(),
            $path
        );

        $this->dispatch(
            'start-download',
            url: route(
                'staff.reports.download',
                [
                    'export' => encrypt($exportId),
                    'name' => $filename,
                ]
            )
        );
    }

    private function auditExport(
        string $type,
        array $scope,
        int $rowCount,
        string $filePath
    ): int {
        return (int) DB::table('report_exports')->insertGetId([
            'export_type' => $type,
            'actor_type' => 'staff',
            'actor_account_id' => (int) auth('staff')->id(),
            'scope' => json_encode($scope),
            'locale' => 'ar',
            'row_count' => $rowCount,
            'file_path' => $filePath,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function render(): View
    {
        return view(
            'livewire.staff.reports.result-report'
        )->layout('layouts.staff');
    }
}

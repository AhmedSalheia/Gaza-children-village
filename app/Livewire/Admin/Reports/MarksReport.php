<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Reports;

use App\Exports\MarksCompletionExport;
use App\Exports\ResultReportExport;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Admin marks and results report page.
 *
 * Two report sub-types:
 *  - completion: mark-sheet status dashboard (draft/submitted/verified/approved counts)
 *  - results: published result publication rows
 *
 * Access gated by result_report.read.
 */
class MarksReport extends Component
{
    public string $reportType = 'completion';

    public int $semesterId = 0;

    public int $classGroupId = 0;

    public bool $canExport = false;

    public function mount(): void
    {
        // Gate: any admin without result_report.read sees a 403.
        abort_if(! $this->adminCan('result_report.read'), 403);

        $this->canExport = $this->adminCan('result_report.export');

        $sem = DB::table('institution_semesters')
            ->where('status', 'open')
            ->orderByDesc('id')
            ->first(['id']);

        if ($sem) {
            $this->semesterId = (int) $sem->id;
        }
    }

    #[Computed]
    public function semesters(): Collection
    {
        return DB::table('institution_semesters as is_')
            ->join('institutions as i', 'i.id', '=', 'is_.institution_id')
            ->join('semesters as s', 's.id', '=', 'is_.semester_id')
            ->whereIn('is_.status', ['open', 'archived'])
            ->orderByDesc('is_.id')
            ->get(['is_.id', 'i.name_ar as institution_name', 's.name_ar as semester_name']);
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
    public function completionRows(): Collection
    {
        if ($this->semesterId === 0 || $this->reportType !== 'completion') {
            return collect();
        }

        $query = DB::table('mark_sheets as ms')
            ->join('class_groups as cg', 'cg.id', '=', 'ms.class_group_id')
            ->join('institution_subject_offerings as iso', 'iso.id', '=', 'ms.subject_offering_id')
            ->join('subjects as s', 's.id', '=', 'iso.subject_id')
            ->join('teaching_assignments as ta', 'ta.id', '=', 'ms.teaching_assignment_id')
            ->join('staff_profiles as sp', 'sp.id', '=', 'ta.staff_profile_id')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->where('ms.institution_semester_id', $this->semesterId)
            ->whereNull('ms.superseded_by_id')
            ->orderBy('cg.name_ar')
            ->orderBy('s.name_ar');

        if ($this->classGroupId > 0) {
            $query->where('ms.class_group_id', $this->classGroupId);
        }

        return $query->select(
            'ms.id as sheet_id',
            'cg.name_ar as class_group_name',
            'cg.code as class_group_code',
            's.name_ar as subject_name',
            'p.full_name_ar as teacher_name',
            'ms.status',
            'ms.version',
            'ms.submitted_at',
            'ms.verified_at',
            'ms.approved_at',
        )->get();
    }

    #[Computed]
    public function completionSummary(): object
    {
        if ($this->semesterId === 0) {
            return (object) ['total' => 0, 'draft' => 0, 'submitted' => 0, 'verified' => 0, 'returned' => 0, 'approved' => 0];
        }

        $query = DB::table('mark_sheets')
            ->where('institution_semester_id', $this->semesterId)
            ->whereNull('superseded_by_id');

        if ($this->classGroupId > 0) {
            $query->where('class_group_id', $this->classGroupId);
        }

        $map = $query->selectRaw('status, count(*) as cnt')
            ->groupBy('status')
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
        if ($this->semesterId === 0 || $this->reportType !== 'results') {
            return collect();
        }

        $query = DB::table('result_publication_rows as rpr')
            ->join('result_publications as rp', 'rp.id', '=', 'rpr.result_publication_id')
            ->join('class_groups as cg', 'cg.id', '=', 'rp.class_group_id')
            ->join('student_enrollments as se', 'se.id', '=', 'rpr.enrollment_id')
            ->join('student_profiles as sp', 'sp.id', '=', 'se.student_profile_id')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->join('institution_subject_offerings as iso', 'iso.id', '=', 'rpr.subject_offering_id')
            ->join('subjects as s', 's.id', '=', 'iso.subject_id')
            ->where('rp.institution_semester_id', $this->semesterId)
            ->where('rp.status', 'published')
            ->whereNull('rp.superseded_by_id')
            ->orderBy('cg.name_ar')
            ->orderBy('p.full_name_ar')
            ->orderBy('s.name_ar');

        if ($this->classGroupId > 0) {
            $query->where('rp.class_group_id', $this->classGroupId);
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

    public function exportCompletion(): void
    {
        if (! $this->canExport || $this->semesterId === 0) {
            return;
        }

        $export = new MarksCompletionExport($this->semesterId, $this->classGroupId > 0 ? $this->classGroupId : null);
        $rows = $export->collection();
        $filename = 'marks-completion-'.now()->format('Y-m-d').'.xlsx';
        $path = 'reports/'.Str::uuid().'/'.$filename;

        Storage::disk('local')->makeDirectory(dirname($path));
        Excel::store($export, $path, 'local');

        $exportId = $this->auditExport('marks_completion', [
            'institution_semester_id' => $this->semesterId,
            'class_group_id' => $this->classGroupId,
        ], $rows->count(), $path);

        $this->dispatch('start-download', url: route('admin.reports.download', [
            'export' => encrypt($exportId),
            'name' => $filename,
        ]));
    }

    public function exportResults(): void
    {
        if (! $this->canExport || $this->semesterId === 0) {
            return;
        }

        $export = new ResultReportExport($this->semesterId, $this->classGroupId > 0 ? $this->classGroupId : null);
        $rows = $export->collection();
        $filename = 'results-report-'.now()->format('Y-m-d').'.xlsx';
        $path = 'reports/'.Str::uuid().'/'.$filename;

        Storage::disk('local')->makeDirectory(dirname($path));
        Excel::store($export, $path, 'local');

        $exportId = $this->auditExport('result_report', [
            'institution_semester_id' => $this->semesterId,
            'class_group_id' => $this->classGroupId,
        ], $rows->count(), $path);

        $this->dispatch('start-download', url: route('admin.reports.download', [
            'export' => encrypt($exportId),
            'name' => $filename,
        ]));
    }

    /**
     * Write the export audit row and return its ID so the download controller
     * can verify actor ownership before serving the file.
     */
    private function auditExport(string $type, array $scope, int $rowCount, string $filePath): int
    {
        return (int) DB::table('report_exports')->insertGetId([
            'export_type' => $type,
            'actor_type' => 'admin',
            'actor_account_id' => (int) auth('admin')->id(),
            'scope' => json_encode($scope),
            'locale' => 'ar',
            'row_count' => $rowCount,
            'file_path' => $filePath,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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
        return view('livewire.admin.reports.marks-report')
            ->layout('layouts.admin');
    }
}

<?php

declare(strict_types=1);

namespace App\Livewire\Staff\Students;

use App\Exports\StaffStudentsExport;
use App\Livewire\Staff\Concerns\HasStaffAuth;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

final class StudentList extends Component
{
    use HasStaffAuth;
    use WithPagination;
    use WithFileUploads;

    /*
    |--------------------------------------------------------------------------
    | البحث والفلاتر
    |--------------------------------------------------------------------------
    */

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    #[Url]
    public int $classGroupFilter = 0;

    #[Url]
    public int $levelFilter = 0;

    /*
    |--------------------------------------------------------------------------
    | رفع ملف Excel مستقل
    |--------------------------------------------------------------------------
    */

    public $studentFile = null;

    public bool $showImportForm = false;

    public string $importMessage = '';

    public string $importMessageType = '';

    /*
    |--------------------------------------------------------------------------
    | الصلاحيات
    |--------------------------------------------------------------------------
    */

    public function mount(): void
    {
        if (
            ! $this->staffCan('student.view')
            && ! $this->staffCan('student.view_restricted')
        ) {
            abort(403);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | تحديث الفلاتر
    |--------------------------------------------------------------------------
    */

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedClassGroupFilter(): void
    {
        $this->resetPage();
    }

    public function updatedLevelFilter(): void
    {
        $this->resetPage();
    }

    /*
    |--------------------------------------------------------------------------
    | فتح نموذج رفع Excel
    |--------------------------------------------------------------------------
    */

    public function openImportForm(): void
    {
        if (! $this->staffCan('student.create')) {
            abort(403);
        }

        $this->resetImportState();

        $this->showImportForm = true;
    }

    /*
    |--------------------------------------------------------------------------
    | إغلاق نموذج رفع Excel
    |--------------------------------------------------------------------------
    */

    public function closeImportForm(): void
    {
        $this->studentFile = null;

        $this->showImportForm = false;

        $this->importMessage = '';

        $this->importMessageType = '';
    }

    /*
    |--------------------------------------------------------------------------
    | رفع ملف Excel فقط
    |--------------------------------------------------------------------------
    |
    | هذه العملية لا تقوم باستيراد الطلاب إلى قاعدة البيانات.
    |
    | وظيفتها فقط:
    |
    | 1. استقبال ملف Excel.
    | 2. حفظ الملف.
    | 3. إضافة التاريخ والوقت إلى اسم الملف.
    |
    */

    public function uploadExcelFile(): void
    {
        /*
        |--------------------------------------------------------------------------
        | التحقق من الصلاحية
        |--------------------------------------------------------------------------
        */

        if (! $this->staffCan('student.create')) {
            abort(403);
        }

        /*
        |--------------------------------------------------------------------------
        | التحقق من الملف
        |--------------------------------------------------------------------------
        |
        | XLSX فقط
        | الحد الأقصى 50 MB
        |
        */

        $this->validate([
            'studentFile' => [
                'required',
                'file',
                'mimes:xlsx',
                'max:51200',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | اسم الملف الأصلي بدون الامتداد
        |--------------------------------------------------------------------------
        */

        $originalName = pathinfo(
            $this->studentFile->getClientOriginalName(),
            PATHINFO_FILENAME
        );

        /*
        |--------------------------------------------------------------------------
        | امتداد الملف
        |--------------------------------------------------------------------------
        */

        $extension = strtolower(
            $this->studentFile->getClientOriginalExtension()
        );

        /*
        |--------------------------------------------------------------------------
        | التاريخ والوقت
        |--------------------------------------------------------------------------
        |
        | مثال:
        | 2026-09-15-123045
        |
        */

        $timestamp = now()->format('Y-m-d-His');

        /*
        |--------------------------------------------------------------------------
        | تنظيف اسم الملف
        |--------------------------------------------------------------------------
        */

        $safeOriginalName = preg_replace(
            '/[^A-Za-z0-9_-]+/',
            '-',
            $originalName
        );

        $safeOriginalName = trim(
            (string) $safeOriginalName,
            '-_'
        );

        /*
        |--------------------------------------------------------------------------
        | اسم افتراضي
        |--------------------------------------------------------------------------
        */

        if ($safeOriginalName === '') {
            $safeOriginalName = 'students';
        }

        /*
        |--------------------------------------------------------------------------
        | إنشاء اسم جديد وفريد
        |--------------------------------------------------------------------------
        |
        | مثال:
        |
        | students-2026-09-15-123045.xlsx
        |
        */

        $storedFileName = sprintf(
            '%s-%s.%s',
            $safeOriginalName,
            $timestamp,
            $extension
        );

        /*
        |--------------------------------------------------------------------------
        | حفظ الملف
        |--------------------------------------------------------------------------
        |
        | سيتم الحفظ في:
        |
        | storage/app/private/student-excel-files/
        |
        */

        try {

            $storedPath = $this->studentFile->storeAs(
                'student-excel-files',
                $storedFileName,
                'local'
            );

            /*
            |--------------------------------------------------------------------------
            | التأكد من نجاح الحفظ
            |--------------------------------------------------------------------------
            */

            if (! $storedPath) {
                $this->importMessage = __(
                    'ui.file_save_failed',
                    [],
                    null,
                    'Failed to save the Excel file.'
                );

                $this->importMessageType = 'error';

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | رسالة النجاح
            |--------------------------------------------------------------------------
            */

            $this->importMessage = sprintf(
                '%s: %s',
                __(
                    'ui.file_uploaded_successfully',
                    [],
                    null,
                    'Excel file uploaded successfully'
                ),
                $storedFileName
            );

            $this->importMessageType = 'success';

            /*
            |--------------------------------------------------------------------------
            | تنظيف الملف من Livewire
            |--------------------------------------------------------------------------
            */

            $this->studentFile = null;

            /*
            |--------------------------------------------------------------------------
            | إغلاق نموذج الرفع
            |--------------------------------------------------------------------------
            */

            $this->showImportForm = false;

        } catch (\Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | تسجيل الخطأ
            |--------------------------------------------------------------------------
            */

            report($e);

            $this->importMessage = __(
                'ui.file_save_failed',
                [],
                null,
                'Failed to save the Excel file.'
            );

            $this->importMessageType = 'error';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | تصفير حالة رفع الملف
    |--------------------------------------------------------------------------
    */

    private function resetImportState(): void
    {
        $this->studentFile = null;

        $this->importMessage = '';

        $this->importMessageType = '';
    }

    /*
    |--------------------------------------------------------------------------
    | استعلام الطلاب
    |--------------------------------------------------------------------------
    */

    private function studentsQuery()
    {
        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            return null;
        }

        $query = DB::table('student_enrollments as se')
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
                'class_groups as cg',
                'cg.id',
                '=',
                'se.class_group_id'
            )
            ->join(
                'academic_levels as al',
                'al.id',
                '=',
                'cg.academic_level_id'
            )
            ->where(
                'se.institution_semester_id',
                $scope['institution_semester_id']
            )
            ->whereNotIn(
                'se.enrollment_status',
                [
                    'completed',
                    'withdrawn',
                ]
            )
            ->select(
                'sp.id as student_id',
                'p.full_name_ar as name_ar',
                'p.full_name_en as name_en',
                'p.national_id as national_id',
                'sp.student_code',
                'sp.lifecycle_status',
                'se.id as enrollment_id',
                'se.enrollment_status',
                'cg.id as class_group_id',
                'cg.name_ar as class_group_name',
                'al.name_ar as level_name'
            );

        /*
        |--------------------------------------------------------------------------
        | الموظفون المقيدون بفترة تشغيلية
        |--------------------------------------------------------------------------
        */

        if (! $this->isFullScopePosition()) {

            $allowedPeriods = $this->allowedPeriodIds();

            if (empty($allowedPeriods)) {
                return null;
            }

            $query->whereIn(
                'cg.operational_period_id',
                $allowedPeriods
            );
        }

        /*
        |--------------------------------------------------------------------------
        | البحث
        |--------------------------------------------------------------------------
        */

        if ($this->search !== '') {

            $query->where(function ($q) {

                $q->where(
                    'p.full_name_ar',
                    'like',
                    '%' . $this->search . '%'
                )
                ->orWhere(
                    'p.full_name_en',
                    'like',
                    '%' . $this->search . '%'
                )
                ->orWhere(
                    'sp.student_code',
                    'like',
                    '%' . $this->search . '%'
                );

            });
        }

        /*
        |--------------------------------------------------------------------------
        | فلتر الحالة
        |--------------------------------------------------------------------------
        */

        if ($this->statusFilter !== '') {

            $query->where(
                'se.enrollment_status',
                $this->statusFilter
            );
        }

        /*
        |--------------------------------------------------------------------------
        | فلتر الشعبة
        |--------------------------------------------------------------------------
        */

        if ($this->classGroupFilter > 0) {

            $query->where(
                'se.class_group_id',
                $this->classGroupFilter
            );
        }

        /*
        |--------------------------------------------------------------------------
        | فلتر المرحلة
        |--------------------------------------------------------------------------
        */

        if ($this->levelFilter > 0) {

            $query->where(
                'cg.academic_level_id',
                $this->levelFilter
            );
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | قائمة الطلاب
    |--------------------------------------------------------------------------
    */

    public function students(): LengthAwarePaginator
    {
        $query = $this->studentsQuery();

        if ($query === null) {

            return new LengthAwarePaginator(
                [],
                0,
                25
            );
        }

        return $query
            ->orderBy('p.full_name_ar')
            ->paginate(25);
    }

    /*
    |--------------------------------------------------------------------------
    | تنزيل Excel للطلاب
    |--------------------------------------------------------------------------
    */

    public function downloadExcel()
    {
        return Excel::download(
            new StaffStudentsExport(
                search: $this->search,
                statusFilter: $this->statusFilter,
                classGroupFilter: $this->classGroupFilter,
                levelFilter: $this->levelFilter,
                staffScope: $this->staffScope(),
                isFullScope: $this->isFullScopePosition(),
                allowedPeriods: $this->allowedPeriodIds(),
            ),
            'students-' . now()->format('Y-m-d-His') . '.xlsx'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | الشُعب الصفية
    |--------------------------------------------------------------------------
    */

    public function classGroups(): Collection
    {
        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            return collect();
        }

        $query = DB::table('class_groups as cg')
            ->join(
                'academic_levels as al',
                'al.id',
                '=',
                'cg.academic_level_id'
            )
            ->where(
                'cg.institution_semester_id',
                $scope['institution_semester_id']
            )
            ->whereNotIn(
                'cg.lifecycle_status',
                ['archived']
            );

        /*
        |--------------------------------------------------------------------------
        | الموظفون المقيدون بفترات محددة
        |--------------------------------------------------------------------------
        */

        if (! $this->isFullScopePosition()) {

            $allowedPeriods = $this->allowedPeriodIds();

            if (empty($allowedPeriods)) {
                return collect();
            }

            $query->whereIn(
                'cg.operational_period_id',
                $allowedPeriods
            );
        }

        return $query
            ->orderBy('al.name_ar')
            ->orderBy('cg.name_ar')
            ->get([
                'cg.id',
                'cg.name_ar',
                'al.name_ar as level_name',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | المراحل الدراسية
    |--------------------------------------------------------------------------
    */

    public function academicLevels(): Collection
    {
        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            return collect();
        }

        $query = DB::table('class_groups as cg')
            ->join(
                'academic_levels as al',
                'al.id',
                '=',
                'cg.academic_level_id'
            )
            ->where(
                'cg.institution_semester_id',
                $scope['institution_semester_id']
            )
            ->distinct();

        /*
        |--------------------------------------------------------------------------
        | الموظفون المقيدون بفترات محددة
        |--------------------------------------------------------------------------
        */

        if (! $this->isFullScopePosition()) {

            $allowedPeriods = $this->allowedPeriodIds();

            if (empty($allowedPeriods)) {
                return collect();
            }

            $query->whereIn(
                'cg.operational_period_id',
                $allowedPeriods
            );
        }

        return $query
            ->orderBy('al.name_ar')
            ->get([
                'al.id',
                'al.name_ar',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Render
    |--------------------------------------------------------------------------
    */

    public function render(): View
    {
        return view(
            'livewire.staff.students.list',
            [
                'students' => $this->students(),

                'classGroups' => $this->classGroups(),

                'academicLevels' => $this->academicLevels(),

                'canCreateStudent' => $this->staffCan(
                    'student.create'
                ),

                'canManageEnrollments' => $this->staffCan(
                    'enrollment.manage'
                ),
            ]
        )->layout('layouts.staff');
    }
}

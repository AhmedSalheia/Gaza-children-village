<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Students;

use App\Exports\StudentsExport;
use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Searchable, filterable student list with pagination.
 *
 * National IDs are never shown; all identifier fields are masked.
 */
final class StudentIndex extends Component
{
    use HasAdminAuth;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    #[Url]
    public int $institutionFilter = 0;

    public function mount(): void
    {
        $this->requirePermission('student.view');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingInstitutionFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Build the students query.
     *
     * This is shared by the table and Excel export so that
     * the exported data always respects the current filters.
     */
    protected function studentsQuery()
    {
        $query = DB::table('student_profiles as sp')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->select([
                'sp.id',
                'sp.student_code',
                'sp.lifecycle_status',
                'sp.registered_on',
                'p.full_name_ar',
                'p.full_name_en',
                'p.birth_date',
                'p.national_id'
            ]);

        /*
         * Search
         */
        if ($this->search !== '') {
            $s = "%{$this->search}%";

            $query->where(function ($q) use ($s): void {
                $q->where('p.national_id', 'like', $s)
                    ->orWhere('p.full_name_ar', 'like', $s)
                    ->orWhere('p.full_name_en', 'like', $s);
            });
        }

        /*
         * Status filter
         */
        if ($this->statusFilter !== '') {
            $query->where(
                'sp.lifecycle_status',
                $this->statusFilter
            );
        }

        /*
         * Institution filter
         */
        if ($this->institutionFilter > 0) {
            $query->whereIn('sp.id', function ($sub): void {
                $sub->select('se.student_profile_id')
                    ->from('student_enrollments as se')
                    ->join(
                        'class_groups as cg',
                        'cg.id',
                        '=',
                        'se.class_group_id'
                    )
                    ->join(
                        'institution_semesters as is',
                        'is.id',
                        '=',
                        'cg.institution_semester_id'
                    )
                    ->where(
                        'is.institution_id',
                        $this->institutionFilter
                    );
            });
        }

        return $query->orderBy('p.full_name_ar');
    }

    /**
     * Paginated students for the table.
     */
    public function students(): LengthAwarePaginator
    {
        return $this->studentsQuery()->paginate(25);
    }

    /**
     * Download the currently filtered students as Excel.
     *
     * The export uses the same search, status and institution
     * filters currently applied to the page.
     */
    public function downloadExcel()
    {
        $students = $this->studentsQuery()->get();

        return Excel::download(
            new StudentsExport($students),
            'students-' . now()->format('Y-m-d-H-i-s') . '.xlsx'
        );
    }

    public function institutions(): Collection
    {
        return DB::table('institutions')
            ->where('is_active', true)
            ->orderBy('name_ar')
            ->get([
                'id',
                'name_ar',
            ]);
    }

    public function statusOptions(): array
    {
        return [
            'active',
            'inactive',
            'draft',
            'withdrawn',
            'graduated',
        ];
    }

    public function render(): View
    {
        return view('livewire.admin.students.index', [
            'students' => $this->students(),
            'institutions' => $this->institutions(),
            'statusOptions' => $this->statusOptions(),
            'canCreateStudent' => $this->adminCan('student.create'),
        ])->layout('layouts.admin');
    }
}

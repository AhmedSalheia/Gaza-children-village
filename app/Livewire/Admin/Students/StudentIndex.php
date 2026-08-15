<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Students;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

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

    public function students(): LengthAwarePaginator
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
            ]);

        if ($this->search !== '') {
            $s = "%{$this->search}%";
            $query->where(function ($q) use ($s): void {
                $q->where('sp.student_code', 'like', $s)
                    ->orWhere('p.full_name_ar', 'like', $s)
                    ->orWhere('p.full_name_en', 'like', $s);
            });
        }

        if ($this->statusFilter !== '') {
            $query->where('sp.lifecycle_status', $this->statusFilter);
        }

        if ($this->institutionFilter > 0) {
            $query->whereIn('sp.id', function ($sub): void {
                $sub->select('se.student_profile_id')
                    ->from('student_enrollments as se')
                    ->join('class_groups as cg', 'cg.id', '=', 'se.class_group_id')
                    ->join('institution_semesters as is', 'is.id', '=', 'cg.institution_semester_id')
                    ->where('is.institution_id', $this->institutionFilter);
            });
        }

        return $query->orderBy('p.full_name_ar')->paginate(25);
    }

    public function institutions(): Collection
    {
        return DB::table('institutions')->where('is_active', true)->orderBy('name_ar')->get(['id', 'name_ar']);
    }

    public function statusOptions(): array
    {
        return ['active', 'inactive', 'draft', 'withdrawn', 'graduated'];
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

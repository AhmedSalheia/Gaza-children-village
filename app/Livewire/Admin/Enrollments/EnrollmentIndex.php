<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Enrollments;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Enrollment history filterable by institution, semester, and status.
 */
final class EnrollmentIndex extends Component
{
    use HasAdminAuth;
    use WithPagination;

    #[Url]
    public int $institutionFilter = 0;

    #[Url]
    public int $semesterFilter = 0;

    #[Url]
    public string $statusFilter = '';

    #[Url(as: 'q')]
    public string $search = '';

    public function mount(): void
    {
        $this->requirePermission('enrollment.view');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingInstitutionFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function enrollments(): LengthAwarePaginator
    {
        return DB::table('student_enrollments as se')
            ->join('student_profiles as sp', 'sp.id', '=', 'se.student_profile_id')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->join('class_groups as cg', 'cg.id', '=', 'se.class_group_id')
            ->join('academic_levels as al', 'al.id', '=', 'cg.academic_level_id')
            ->join('institution_semesters as is', 'is.id', '=', 'cg.institution_semester_id')
            ->join('institutions as i', 'i.id', '=', 'is.institution_id')
            ->select([
                'se.id',
                'se.enrollment_status',
                'se.enrolled_on',
                'se.activated_on',
                'se.completed_on',
                'sp.student_code',
                'p.full_name_ar as student_name',
                'cg.name_ar as class_group_name',
                'al.name_ar as level_name',
                'i.name_ar as institution_name',
            ])
            ->when($this->search !== '', function ($q): void {
                $s = "%{$this->search}%";
                $q->where(function ($inner) use ($s): void {
                    $inner->where('sp.student_code', 'like', $s)
                        ->orWhere('p.full_name_ar', 'like', $s);
                });
            })
            ->when($this->institutionFilter > 0, fn ($q) => $q->where('i.id', $this->institutionFilter))
            ->when($this->semesterFilter > 0, fn ($q) => $q->where('is.id', $this->semesterFilter))
            ->when($this->statusFilter !== '', fn ($q) => $q->where('se.enrollment_status', $this->statusFilter))
            ->orderByDesc('se.enrolled_on')
            ->paginate(25);
    }

    public function institutions(): Collection
    {
        return DB::table('institutions')->where('is_active', true)->orderBy('name_ar')->get(['id', 'name_ar']);
    }

    public function semesters(): Collection
    {
        return DB::table('institution_semesters as is')
            ->join('semesters as s', 's.id', '=', 'is.semester_id')
            ->join('institutions as i', 'i.id', '=', 'is.institution_id')
            ->when($this->institutionFilter > 0, fn ($q) => $q->where('is.institution_id', $this->institutionFilter))
            ->orderByDesc('s.starts_on')
            ->get(['is.id', 's.name_ar as semester_name', 'i.name_ar as institution_name', 'is.status']);
    }

    public function statusOptions(): array
    {
        return ['draft', 'active', 'completed', 'withdrawn', 'transferred', 'promoted', 'repeating', 'suspended'];
    }

    public function render(): View
    {
        return view('livewire.admin.enrollments.index', [
            'enrollments' => $this->enrollments(),
            'institutions' => $this->institutions(),
            'semesters' => $this->semesters(),
            'statusOptions' => $this->statusOptions(),
            'canTransfer' => $this->adminCan('enrollment.transfer'),
            'canPromote' => $this->adminCan('enrollment.promote'),
        ])->layout('layouts.admin');
    }
}

<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Enrollments;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * List of student transfers (enrollments in 'transferred' status).
 */
final class TransferIndex extends Component
{
    use HasAdminAuth;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public function mount(): void
    {
        $this->requirePermission('enrollment.transfer');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function transfers(): LengthAwarePaginator
    {
        return DB::table('student_enrollments as se')
            ->join('student_profiles as sp', 'sp.id', '=', 'se.student_profile_id')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->join('class_groups as cg', 'cg.id', '=', 'se.class_group_id')
            ->join('institution_semesters as is', 'is.id', '=', 'cg.institution_semester_id')
            ->join('institutions as i', 'i.id', '=', 'is.institution_id')
            ->select([
                'se.id',
                'se.enrolled_on',
                'se.completed_on',
                'se.notes',
                'sp.student_code',
                'p.full_name_ar as student_name',
                'cg.name_ar as class_group_name',
                'i.name_ar as institution_name',
            ])
            ->where('se.enrollment_status', 'transferred')
            ->when($this->search !== '', function ($q): void {
                $s = "%{$this->search}%";
                $q->where(function ($inner) use ($s): void {
                    $inner->where('sp.student_code', 'like', $s)
                        ->orWhere('p.full_name_ar', 'like', $s);
                });
            })
            ->orderByDesc('se.completed_on')
            ->paginate(25);
    }

    public function render(): View
    {
        return view('livewire.admin.enrollments.transfers', [
            'transfers' => $this->transfers(),
        ])->layout('layouts.admin');
    }
}

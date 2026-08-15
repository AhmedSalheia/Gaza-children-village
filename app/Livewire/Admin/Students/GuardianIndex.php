<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Students;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Searchable guardian profile list.
 */
final class GuardianIndex extends Component
{
    use HasAdminAuth;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public function mount(): void
    {
        $this->requirePermission('guardian_relationship.view');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function guardians(): LengthAwarePaginator
    {
        return DB::table('guardian_profiles as gp')
            ->join('people as p', 'p.id', '=', 'gp.person_id')
            ->select([
                'gp.id',
                'gp.guardian_code',
                'gp.lifecycle_status',
                'p.full_name_ar',
                'p.full_name_en',
            ])
            ->when($this->search !== '', function ($q): void {
                $s = "%{$this->search}%";
                $q->where(function ($inner) use ($s): void {
                    $inner->where('gp.guardian_code', 'like', $s)
                        ->orWhere('p.full_name_ar', 'like', $s)
                        ->orWhere('p.full_name_en', 'like', $s);
                });
            })
            ->orderBy('p.full_name_ar')
            ->paginate(25);
    }

    public function render(): View
    {
        return view('livewire.admin.students.guardians', [
            'guardians' => $this->guardians(),
        ])->layout('layouts.admin');
    }
}

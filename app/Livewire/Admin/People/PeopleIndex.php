<?php

declare(strict_types=1);

namespace App\Livewire\Admin\People;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Searchable people list with masked sensitive fields.
 */
final class PeopleIndex extends Component
{
    use HasAdminAuth;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public function mount(): void
    {
        $this->requirePermission('person.view');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function people(): LengthAwarePaginator
    {
        return DB::table('people')
            ->when($this->search !== '', function ($q): void {
                $s = "%{$this->search}%";
                $q->where('full_name_ar', 'like', $s)
                    ->orWhere('full_name_en', 'like', $s);
            })
            ->orderBy('full_name_ar')
            ->paginate(25, [
                'id',
                'full_name_ar',
                'full_name_en',
                'birth_date',
                'birth_date_precision',
                'created_at',
            ]);
    }

    public function render(): View
    {
        return view('livewire.admin.people.index', [
            'people' => $this->people(),
        ])->layout('layouts.admin');
    }
}

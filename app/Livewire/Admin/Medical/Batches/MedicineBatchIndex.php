<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Medical\Batches;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class MedicineBatchIndex extends Component
{
    use HasAdminAuth;
    use WithPagination;

    #[Url(as: 'status')]
    public string $status = 'all';

    public function mount(): void
    {
        $this->requirePermission('medical.medicine.view');
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->status = 'all';
        $this->resetPage();
    }

    public function render(): View
    {
        /*
        |--------------------------------------------------------------------------
        | Batches
        |--------------------------------------------------------------------------
        */

        $batches = DB::table('medical_medicine_batches as b')
            ->join(
                'medical_medicines as m',
                'm.id',
                '=',
                'b.medicine_id'
            )
            ->join(
                'institutions as i',
                'i.id',
                '=',
                'b.institution_id'
            )
            ->select([
                'b.*',
                'm.name_ar',
                'm.name_en',
                'm.code',
                'm.unit',
                'm.reorder_level',
                'i.name_ar as clinic_ar',
                'i.name_en as clinic_en',
            ])
            ->when(
                $this->status !== 'all',
                fn ($query) => $query->where(
                    'b.status',
                    $this->status
                )
            )
            ->orderBy('b.expiry_date')
            ->paginate(30);

        /*
        |--------------------------------------------------------------------------
        | Statistics
        |--------------------------------------------------------------------------
        */

        $totalBatches = DB::table('medical_medicine_batches')
            ->count();

        $activeBatches = DB::table('medical_medicine_batches')
            ->where('status', 'active')
            ->count();

        $expiredBatches = DB::table('medical_medicine_batches')
            ->whereDate(
                'expiry_date',
                '<',
                now()->toDateString()
            )
            ->where(
                'current_quantity',
                '>',
                0
            )
            ->count();

        $expiringSoon = DB::table('medical_medicine_batches')
            ->whereDate(
                'expiry_date',
                '>=',
                now()->toDateString()
            )
            ->whereDate(
                'expiry_date',
                '<=',
                now()->addDays(90)->toDateString()
            )
            ->where(
                'current_quantity',
                '>',
                0
            )
            ->count();

        $totalQuantity = (float) DB::table(
            'medical_medicine_batches'
        )
            ->where(
                'current_quantity',
                '>',
                0
            )
            ->sum('current_quantity');

        return view(
            'livewire.admin.medical.batches.index',
            compact(
                'batches',
                'totalBatches',
                'activeBatches',
                'expiredBatches',
                'expiringSoon',
                'totalQuantity'
            )
        )->layout('layouts.admin');
    }
}

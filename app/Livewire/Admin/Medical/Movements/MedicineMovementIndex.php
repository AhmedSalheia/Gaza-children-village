<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Medical\Movements;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class MedicineMovementIndex extends Component
{
    use HasAdminAuth;
    use WithPagination;

    #[Url(as: 'q')]
    public string $q = '';

    #[Url(as: 'type')]
    public string $type = 'all';


    /*
    |--------------------------------------------------------------------------
    | Mount
    |--------------------------------------------------------------------------
    */

    public function mount(): void
    {
        $this->requirePermission('medical.medicine.view');
    }


    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */

    public function updatedQ(): void
    {
        $this->resetPage();
    }


    /*
    |--------------------------------------------------------------------------
    | Movement filter
    |--------------------------------------------------------------------------
    */

    public function updatedType(): void
    {
        $this->resetPage();
    }


    /*
    |--------------------------------------------------------------------------
    | Clear filters
    |--------------------------------------------------------------------------
    */

    public function clearFilters(): void
    {
        $this->q = '';

        $this->type = 'all';

        $this->resetPage();
    }


    /*
    |--------------------------------------------------------------------------
    | Render
    |--------------------------------------------------------------------------
    */

    public function render(): View
    {
        $q = trim($this->q);


        /*
        |--------------------------------------------------------------------------
        | Movements
        |--------------------------------------------------------------------------
        */

        $movements = DB::table('medical_medicine_transactions as t')
            ->join(
                'medical_medicines as m',
                'm.id',
                '=',
                't.medicine_id'
            )
            ->join(
                'institutions as i',
                'i.id',
                '=',
                't.institution_id'
            )
            ->leftJoin(
                'medical_patients as p',
                'p.id',
                '=',
                't.patient_id'
            )
            ->select([
                't.*',

                'm.name_ar',
                'm.name_en',
                'm.code',

                'i.name_ar as clinic_ar',
                'i.name_en as clinic_en',

                'p.patient_code',
            ])
            ->when(
                $this->type !== 'all',
                fn ($query) => $query->where(
                    't.type',
                    $this->type
                )
            )
            ->when(
                $q !== '',
                function ($query) use ($q): void {
                    $query->where(function ($search) use ($q): void {
                        $search
                            ->where(
                                't.transaction_number',
                                'like',
                                "%{$q}%"
                            )
                            ->orWhere(
                                'm.name_ar',
                                'like',
                                "%{$q}%"
                            )
                            ->orWhere(
                                'm.name_en',
                                'like',
                                "%{$q}%"
                            )
                            ->orWhere(
                                'm.code',
                                'like',
                                "%{$q}%"
                            )
                            ->orWhere(
                                'p.patient_code',
                                'like',
                                "%{$q}%"
                            );
                    });
                }
            )
            ->orderByDesc('t.occurred_at')
            ->paginate(30);


        /*
        |--------------------------------------------------------------------------
        | Statistics
        |--------------------------------------------------------------------------
        */

        $todayStart = now()->startOfDay();

        $todayEnd = now()->endOfDay();


        $todayMovements = DB::table(
            'medical_medicine_transactions'
        )
            ->whereBetween(
                'occurred_at',
                [
                    $todayStart,
                    $todayEnd,
                ]
            )
            ->count();


        $receipts = DB::table(
            'medical_medicine_transactions'
        )
            ->where('type', 'receipt')
            ->count();


        $issues = DB::table(
            'medical_medicine_transactions'
        )
            ->where('type', 'issue')
            ->count();


        $adjustments = DB::table(
            'medical_medicine_transactions'
        )
            ->whereIn(
                'type',
                [
                    'adjustment',
                    'disposal',
                ]
            )
            ->count();


        return view(
            'livewire.admin.medical.movements.index',
            compact(
                'movements',
                'todayMovements',
                'receipts',
                'issues',
                'adjustments'
            )
        )->layout('layouts.admin');
    }
}

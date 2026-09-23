<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Medical\Reports;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

final class MedicalReports extends Component
{
    use HasAdminAuth;

    /*
    |--------------------------------------------------------------------------
    | Filters
    |--------------------------------------------------------------------------
    */

    public string $from = '';

    public string $to = '';


    /*
    |--------------------------------------------------------------------------
    | Mount
    |--------------------------------------------------------------------------
    */

    public function mount(): void
    {
        $this->requirePermission('medical.report.view');

        $this->from = now()
            ->startOfMonth()
            ->toDateString();

        $this->to = now()
            ->toDateString();
    }


    /*
    |--------------------------------------------------------------------------
    | Generate / refresh report
    |--------------------------------------------------------------------------
    */

    public function generate(): void
    {
        $this->validate([
            'from' => [
                'required',
                'date',
            ],

            'to' => [
                'required',
                'date',
                'after_or_equal:from',
            ],
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Render
    |--------------------------------------------------------------------------
    */

    public function render(): View
    {
        $this->validate([
            'from' => [
                'required',
                'date',
            ],

            'to' => [
                'required',
                'date',
                'after_or_equal:from',
            ],
        ]);

        $from = $this->from . ' 00:00:00';

        $to = $this->to . ' 23:59:59';


        /*
        |--------------------------------------------------------------------------
        | Medical visits
        |--------------------------------------------------------------------------
        */

        $visits = DB::table('medical_visits')
            ->whereBetween('visited_at', [
                $from,
                $to,
            ])
            ->count();


        /*
        |--------------------------------------------------------------------------
        | Medicine receipts
        |--------------------------------------------------------------------------
        */

        $receipts = DB::table('medical_medicine_transactions')
            ->where('type', 'receipt')
            ->whereBetween('occurred_at', [
                $from,
                $to,
            ])
            ->sum('quantity');


        /*
        |--------------------------------------------------------------------------
        | Medicine issues / disposal / adjustments
        |--------------------------------------------------------------------------
        */

        $issues = DB::table('medical_medicine_transactions')
            ->whereIn('type', [
                'issue',
                'disposal',
                'adjustment',
            ])
            ->whereBetween('occurred_at', [
                $from,
                $to,
            ])
            ->sum('quantity');


        /*
        |--------------------------------------------------------------------------
        | Expired batches with remaining stock
        |--------------------------------------------------------------------------
        */

        $expired = DB::table('medical_medicine_batches')
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


        /*
        |--------------------------------------------------------------------------
        | Most issued / disposed medicines
        |--------------------------------------------------------------------------
        */

        $top = DB::table(
            'medical_medicine_transactions as t'
        )
            ->join(
                'medical_medicines as m',
                'm.id',
                '=',
                't.medicine_id'
            )
            ->whereIn('t.type', [
                'issue',
                'disposal',
            ])
            ->whereBetween('t.occurred_at', [
                $from,
                $to,
            ])
            ->groupBy(
                'm.id',
                'm.name_ar'
            )
            ->select([
                'm.id',
                'm.name_ar',
                DB::raw('SUM(t.quantity) as total'),
            ])
            ->orderByDesc('total')
            ->limit(10)
            ->get();


        return view(
            'livewire.admin.medical.reports.index',
            compact(
                'visits',
                'receipts',
                'issues',
                'expired',
                'top'
            )
        )->layout('layouts.admin');
    }
}

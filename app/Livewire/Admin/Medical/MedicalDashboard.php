<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Medical;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

final class MedicalDashboard extends Component
{
    use HasAdminAuth;

    public function mount(): void
    {
        $this->requirePermission('medical.view');
    }

    public function render(): View
    {
        $today = now()->toDateString();

        /*
        |--------------------------------------------------------------------------
        | Expiring medicines
        |--------------------------------------------------------------------------
        */

        $expiring = (int) DB::table('medical_medicine_batches')
            ->where('current_quantity', '>', 0)
            ->whereBetween('expiry_date', [
                $today,
                now()
                    ->addDays((int) config('medical.expiring_days', 90))
                    ->toDateString(),
            ])
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Expired medicines
        |--------------------------------------------------------------------------
        */

        $expired = (int) DB::table('medical_medicine_batches')
            ->where('current_quantity', '>', 0)
            ->whereDate('expiry_date', '<', $today)
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Low stock medicines
        |--------------------------------------------------------------------------
        |
        | Do not use ->count() directly on a grouped query because MySQL
        | ONLY_FULL_GROUP_BY can generate an invalid SELECT * subquery.
        |
        */

        $low = DB::table('medical_medicines as m')
            ->leftJoin('medical_medicine_batches as b', function ($join) {
                $join->on('b.medicine_id', '=', 'm.id')
                    ->where('b.current_quantity', '>', 0);
            })
            ->select('m.id')
            ->selectRaw(
                'COALESCE(SUM(b.current_quantity), 0) as total_quantity'
            )
            ->groupBy(
                'm.id',
                'm.reorder_level'
            )
            ->havingRaw(
                'COALESCE(SUM(b.current_quantity), 0) <= m.reorder_level'
            )
            ->get()
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */

        return view('livewire.admin.medical.dashboard', [

            /*
            |--------------------------------------------------------------------------
            | Medical clinics / points
            |--------------------------------------------------------------------------
            */

            'clinics' => DB::table('institutions as i')
                ->join(
                    'institution_types as t',
                    't.id',
                    '=',
                    'i.institution_type_id'
                )
                ->where('t.code', 'medical_point')
                ->where('i.is_active', 1)
                ->count(),

            /*
            |--------------------------------------------------------------------------
            | Medical staff
            |--------------------------------------------------------------------------
            */

            'staff' => DB::table('medical_staff')
                ->where('status', 'active')
                ->count(),

            /*
            |--------------------------------------------------------------------------
            | Active patients
            |--------------------------------------------------------------------------
            */

            'patients' => DB::table('medical_patients')
                ->where('status', 'active')
                ->count(),

            /*
            |--------------------------------------------------------------------------
            | Active medicines
            |--------------------------------------------------------------------------
            */

            'medicines' => DB::table('medical_medicines')
                ->where('is_active', 1)
                ->count(),

            /*
            |--------------------------------------------------------------------------
            | Today's visits
            |--------------------------------------------------------------------------
            */

            'visits' => DB::table('medical_visits')
                ->whereDate('visited_at', $today)
                ->count(),

            /*
            |--------------------------------------------------------------------------
            | Stock alerts
            |--------------------------------------------------------------------------
            */

            'expiring' => $expiring,
            'expired' => $expired,
            'low' => $low,

        ])->layout('layouts.admin');
    }
}

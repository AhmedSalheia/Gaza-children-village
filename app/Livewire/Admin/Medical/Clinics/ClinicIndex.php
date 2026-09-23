<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Medical\Clinics;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

final class ClinicIndex extends Component
{
    use HasAdminAuth;

    #[Url(as: 'q')]
    public string $q = '';


    /*
    |--------------------------------------------------------------------------
    | Mount
    |--------------------------------------------------------------------------
    */

    public function mount(): void
    {
        $this->requirePermission('medical.clinic.view');
    }


    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */

    public function updatedQ(): void
    {
        // Search is handled automatically through the URL-bound property.
    }


    /*
    |--------------------------------------------------------------------------
    | Clear Search
    |--------------------------------------------------------------------------
    */

    public function clearSearch(): void
    {
        $this->q = '';
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
        | Medical Points
        |--------------------------------------------------------------------------
        */

        $clinics = DB::table('institutions as i')
            ->join(
                'institution_types as t',
                't.id',
                '=',
                'i.institution_type_id'
            )
            ->where(
                't.code',
                'medical_point'
            )
            ->when(
                $q !== '',
                function ($query) use ($q): void {
                    $query->where(function ($search) use ($q): void {
                        $search
                            ->where(
                                'i.name_ar',
                                'like',
                                "%{$q}%"
                            )
                            ->orWhere(
                                'i.name_en',
                                'like',
                                "%{$q}%"
                            )
                            ->orWhere(
                                'i.code',
                                'like',
                                "%{$q}%"
                            );
                    });
                }
            )
            ->select([
                'i.id',
                'i.code',
                'i.name_ar',
                'i.name_en',
                'i.is_active',
                'i.institution_type_id',
            ])
            ->orderBy('i.id')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Staff count by medical point
        |--------------------------------------------------------------------------
        */

        $staffStats = DB::table('medical_staff')
            ->select(
                'institution_id',
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('institution_id')
            ->pluck(
                'total',
                'institution_id'
            );


        /*
        |--------------------------------------------------------------------------
        | Active staff count
        |--------------------------------------------------------------------------
        */

        $activeStaffStats = DB::table('medical_staff')
            ->where(
                'status',
                'active'
            )
            ->select(
                'institution_id',
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('institution_id')
            ->pluck(
                'total',
                'institution_id'
            );


        /*
        |--------------------------------------------------------------------------
        | Overall statistics
        |--------------------------------------------------------------------------
        */

        $totalClinics = DB::table('institutions as i')
            ->join(
                'institution_types as t',
                't.id',
                '=',
                'i.institution_type_id'
            )
            ->where(
                't.code',
                'medical_point'
            )
            ->count();


        $activeClinics = DB::table('institutions as i')
            ->join(
                'institution_types as t',
                't.id',
                '=',
                'i.institution_type_id'
            )
            ->where(
                't.code',
                'medical_point'
            )
            ->where(
                'i.is_active',
                1
            )
            ->count();


        $inactiveClinics = $totalClinics - $activeClinics;


        $totalStaff = DB::table('medical_staff')
            ->count();


        $activeStaff = DB::table('medical_staff')
            ->where(
                'status',
                'active'
            )
            ->count();


        return view(
            'livewire.admin.medical.clinics.index',
            compact(
                'clinics',
                'staffStats',
                'activeStaffStats',
                'totalClinics',
                'activeClinics',
                'inactiveClinics',
                'totalStaff',
                'activeStaff'
            )
        )->layout('layouts.admin');
    }
}

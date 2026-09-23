<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Medical\Patients;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

final class PatientProfile extends Component
{
    use HasAdminAuth;

    public int $patient;


    /*
    |--------------------------------------------------------------------------
    | Mount
    |--------------------------------------------------------------------------
    */

    public function mount($patient): void
    {
        $this->requirePermission('medical.patient.view');

        $this->patient = (int) $patient;

        abort_unless(
            DB::table('medical_patients')
                ->where('id', $this->patient)
                ->exists(),
            404
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Render
    |--------------------------------------------------------------------------
    */

    public function render(): View
    {
        /*
        |--------------------------------------------------------------------------
        | Patient profile
        |--------------------------------------------------------------------------
        */

        $p = DB::table('medical_patients as mp')
            ->join(
                'student_profiles as sp',
                'sp.id',
                '=',
                'mp.student_profile_id'
            )
            ->join(
                'people as pe',
                'pe.id',
                '=',
                'sp.person_id'
            )
            ->leftJoin(
                'institutions as i',
                'i.id',
                '=',
                'mp.institution_id'
            )
            ->select([
                'mp.*',

                'sp.student_code',

                'pe.full_name_ar',

                'pe.full_name_en',

                'pe.birth_date',

                'pe.gender',

                'i.name_ar as clinic_ar',

                'i.name_en as clinic_en',
            ])
            ->where(
                'mp.id',
                $this->patient
            )
            ->first();


        abort_unless($p !== null, 404);


        /*
        |--------------------------------------------------------------------------
        | Medical visits
        |--------------------------------------------------------------------------
        */

        $visits = DB::table('medical_visits as v')
            ->leftJoin(
                'institutions as i',
                'i.id',
                '=',
                'v.institution_id'
            )
            ->leftJoin(
                'medical_staff as ms',
                'ms.id',
                '=',
                'v.doctor_id'
            )
            ->leftJoin(
                'staff_profiles as sp',
                'sp.id',
                '=',
                'ms.staff_profile_id'
            )
            ->leftJoin(
                'people as pe',
                'pe.id',
                '=',
                'sp.person_id'
            )
            ->where(
                'v.patient_id',
                $this->patient
            )
            ->select([
                'v.*',

                'i.name_ar as clinic_ar',

                'i.name_en as clinic_en',

                'pe.full_name_ar as doctor_ar',

                'pe.full_name_en as doctor_en',
            ])
            ->orderByDesc('v.visited_at')
            ->limit(30)
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Prescriptions
        |--------------------------------------------------------------------------
        */

        $rx = DB::table('medical_prescriptions as r')
            ->leftJoin(
                'medical_staff as ms',
                'ms.id',
                '=',
                'r.doctor_id'
            )
            ->leftJoin(
                'staff_profiles as sp',
                'sp.id',
                '=',
                'ms.staff_profile_id'
            )
            ->leftJoin(
                'people as pe',
                'pe.id',
                '=',
                'sp.person_id'
            )
            ->where(
                'r.patient_id',
                $this->patient
            )
            ->select([
                'r.*',

                'pe.full_name_ar as doctor_ar',

                'pe.full_name_en as doctor_en',
            ])
            ->orderByDesc('r.prescribed_at')
            ->limit(20)
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Summary
        |--------------------------------------------------------------------------
        */

        $visitsCount = $visits->count();

        $prescriptionsCount = $rx->count();


        return view(
            'livewire.admin.medical.patients.profile',
            compact(
                'p',
                'visits',
                'rx',
                'visitsCount',
                'prescriptionsCount'
            )
        )->layout('layouts.admin');
    }
}

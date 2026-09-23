<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Medical\Visits;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

final class VisitIndex extends Component
{
    use HasAdminAuth;
    use WithPagination;

    public function mount(): void
    {
        $this->requirePermission('medical.visit.view');
    }

    public function render(): View
    {
        $visits = DB::table('medical_visits as v')
            ->join(
                'medical_patients as mp',
                'mp.id',
                '=',
                'v.patient_id'
            )
            ->join(
                'student_profiles as sp',
                'sp.id',
                '=',
                'mp.student_profile_id'
            )
            ->join(
                'people as p',
                'p.id',
                '=',
                'sp.person_id'
            )
            ->join(
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
                'staff_profiles as dsp',
                'dsp.id',
                '=',
                'ms.staff_profile_id'
            )
            ->leftJoin(
                'people as dp',
                'dp.id',
                '=',
                'dsp.person_id'
            )
            ->select([
                'v.*',

                'mp.patient_code',

                'sp.student_code',

                'p.full_name_ar',
                'p.full_name_en',

                'i.name_ar as clinic_ar',
                'i.name_en as clinic_en',

                'dp.full_name_ar as doctor_ar',
                'dp.full_name_en as doctor_en',
            ])
            ->orderByDesc('v.visited_at')
            ->paginate(20);

        return view(
            'livewire.admin.medical.visits.index',
            compact('visits')
        )->layout('layouts.admin');
    }
}

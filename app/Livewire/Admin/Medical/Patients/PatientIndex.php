<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Medical\Patients;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class PatientIndex extends Component
{
    use HasAdminAuth;
    use WithPagination;

    #[Url(as: 'q')]
    public string $q = '';

    public string $studentId = '';

    public string $institutionId = '';

    public string $allergies = '';

    public string $chronicConditions = '';

    public bool $showForm = false;


    /*
    |--------------------------------------------------------------------------
    | Mount
    |--------------------------------------------------------------------------
    */

    public function mount(): void
    {
        $this->requirePermission('medical.patient.view');
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
    | Open form
    |--------------------------------------------------------------------------
    */

    public function openForm(): void
    {
        $this->requirePermission('medical.patient.manage');

        $this->resetForm();

        $this->showForm = true;
    }


    /*
    |--------------------------------------------------------------------------
    | Save patient
    |--------------------------------------------------------------------------
    */

    public function save(): void
    {
        $this->requirePermission('medical.patient.manage');

        $validated = $this->validate([
            'studentId' => [
                'required',
                'integer',
            ],

            'institutionId' => [
                'nullable',
                'integer',
            ],

            'allergies' => [
                'nullable',
                'string',
                'max:4000',
            ],

            'chronicConditions' => [
                'nullable',
                'string',
                'max:4000',
            ],
        ]);


        $student = DB::table('student_profiles as sp')
            ->join(
                'people as p',
                'p.id',
                '=',
                'sp.person_id'
            )
            ->where(
                'sp.id',
                (int) $validated['studentId']
            )
            ->select([
                'sp.id',
                'sp.student_code',
                'sp.person_id',
            ])
            ->first();


        abort_unless($student !== null, 404);


        DB::table('medical_patients')->updateOrInsert(
            [
                'student_profile_id' => $student->id,
            ],
            [
                'patient_code' =>
                    'PAT-' . $student->student_code,

                'patient_type' => 'student',

                'person_id' => $student->person_id,

                'institution_id' =>
                    $validated['institutionId']
                        ? (int) $validated['institutionId']
                        : null,

                'status' => 'active',

                'allergies' =>
                    $validated['allergies']
                        ?: null,

                'chronic_conditions' =>
                    $validated['chronicConditions']
                        ?: null,

                'updated_at' => now(),

                'created_at' => now(),
            ]
        );


        $this->resetForm();

        session()->flash(
            'medical_message',
            __('medical.saved')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Reset form
    |--------------------------------------------------------------------------
    */

    public function resetForm(): void
    {
        $this->reset([
            'studentId',
            'institutionId',
            'allergies',
            'chronicConditions',
        ]);

        $this->resetValidation();

        $this->showForm = false;
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
        | Patients
        |--------------------------------------------------------------------------
        */

        $patients = DB::table('medical_patients as mp')
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
            ->leftJoin(
                'institutions as i',
                'i.id',
                '=',
                'mp.institution_id'
            )
            ->select([
                'mp.*',

                'sp.student_code',

                'p.full_name_ar',

                'p.full_name_en',

                'i.name_ar as clinic_ar',

                'i.name_en as clinic_en',
            ])
            ->when(
                $q !== '',
                function ($query) use ($q): void {
                    $query->where(function ($search) use ($q): void {
                        $search
                            ->where(
                                'mp.patient_code',
                                'like',
                                "%{$q}%"
                            )
                            ->orWhere(
                                'sp.student_code',
                                'like',
                                "%{$q}%"
                            )
                            ->orWhere(
                                'p.full_name_ar',
                                'like',
                                "%{$q}%"
                            )
                            ->orWhere(
                                'p.full_name_en',
                                'like',
                                "%{$q}%"
                            );
                    });
                }
            )
            ->orderBy('p.full_name_ar')
            ->paginate(20);


        /*
        |--------------------------------------------------------------------------
        | Students not yet registered as patients
        |--------------------------------------------------------------------------
        */

        $students = DB::table('student_profiles as sp')
            ->join(
                'people as p',
                'p.id',
                '=',
                'sp.person_id'
            )
            ->leftJoin(
                'medical_patients as mp',
                'mp.student_profile_id',
                '=',
                'sp.id'
            )
            ->whereNull('mp.id')
            ->select([
                'sp.id',
                'sp.student_code',
                'p.full_name_ar',
                'p.full_name_en',
            ])
            ->orderBy('p.full_name_ar')
            ->limit(500)
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Medical points
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
            ->where(
                'i.is_active',
                1
            )
            ->select([
                'i.id',
                'i.name_ar',
                'i.name_en',
            ])
            ->orderBy('i.name_ar')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Statistics
        |--------------------------------------------------------------------------
        */

        $activePatients = DB::table('medical_patients')
            ->where('status', 'active')
            ->count();

        $inactivePatients = DB::table('medical_patients')
            ->where('status', '!=', 'active')
            ->count();


        return view(
            'livewire.admin.medical.patients.index',
            compact(
                'patients',
                'students',
                'clinics',
                'activePatients',
                'inactivePatients'
            )
        )->layout('layouts.admin');
    }
}

<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Medical\Prescriptions;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

final class PrescriptionIndex extends Component
{
    use HasAdminAuth;
    use WithPagination;

    /*
    |--------------------------------------------------------------------------
    | Form
    |--------------------------------------------------------------------------
    */

    public string $patientId = '';

    public string $doctorId = '';

    public string $medicineId = '';

    public string $dose = '';

    public string $frequency = '';

    public string $duration = '';

    public string $quantity = '';

    public string $instructions = '';

    public string $notes = '';

    public bool $showForm = false;


    /*
    |--------------------------------------------------------------------------
    | Mount
    |--------------------------------------------------------------------------
    */

    public function mount(): void
    {
        $this->requirePermission('medical.prescription.view');
    }


    /*
    |--------------------------------------------------------------------------
    | Open form
    |--------------------------------------------------------------------------
    */

    public function openForm(): void
    {
        $this->requirePermission('medical.prescription.create');

        $this->resetForm();

        $this->showForm = true;
    }


    /*
    |--------------------------------------------------------------------------
    | Save prescription
    |--------------------------------------------------------------------------
    */

    public function save(): void
    {
        $this->requirePermission('medical.prescription.create');

        $validated = $this->validate([
            'patientId' => [
                'required',
                'integer',
            ],

            'doctorId' => [
                'nullable',
                'integer',
            ],

            'medicineId' => [
                'required',
                'integer',
            ],

            'dose' => [
                'nullable',
                'string',
                'max:120',
            ],

            'frequency' => [
                'nullable',
                'string',
                'max:120',
            ],

            'duration' => [
                'nullable',
                'string',
                'max:120',
            ],

            'quantity' => [
                'required',
                'numeric',
                'min:0.001',
            ],

            'instructions' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:4000',
            ],
        ]);


        DB::transaction(function () use ($validated): void {

            $prescriptionId = DB::table(
                'medical_prescriptions'
            )->insertGetId([
                'prescription_number' =>
                    'RX-'
                    . now()->format('YmdHis')
                    . '-'
                    . str()->upper(str()->random(5)),

                'patient_id' => (int) $validated['patientId'],

                'doctor_id' =>
                    $validated['doctorId']
                        ? (int) $validated['doctorId']
                        : null,

                'prescribed_at' => now(),

                'status' => 'prescribed',

                'notes' =>
                    $validated['notes']
                        ?: null,

                'created_at' => now(),

                'updated_at' => now(),
            ]);


            DB::table(
                'medical_prescription_lines'
            )->insert([
                'prescription_id' => $prescriptionId,

                'medicine_id' => (int) $validated['medicineId'],

                'dose' =>
                    $validated['dose']
                        ?: null,

                'frequency' =>
                    $validated['frequency']
                        ?: null,

                'duration' =>
                    $validated['duration']
                        ?: null,

                'quantity' => $validated['quantity'],

                'instructions' =>
                    $validated['instructions']
                        ?: null,

                'created_at' => now(),

                'updated_at' => now(),
            ]);
        });


        session()->flash(
            'medical_message',
            __('medical.saved')
        );


        $this->resetForm();

        $this->resetPage();
    }


    /*
    |--------------------------------------------------------------------------
    | Reset form
    |--------------------------------------------------------------------------
    */

    public function resetForm(): void
    {
        $this->reset([
            'patientId',
            'doctorId',
            'medicineId',
            'dose',
            'frequency',
            'duration',
            'quantity',
            'instructions',
            'notes',
        ]);

        $this->showForm = false;
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
        | Prescriptions
        |--------------------------------------------------------------------------
        */

        $rx = DB::table('medical_prescriptions as r')
            ->join(
                'medical_patients as mp',
                'mp.id',
                '=',
                'r.patient_id'
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
            ->leftJoin(
                'medical_staff as ms',
                'ms.id',
                '=',
                'r.doctor_id'
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
                'r.*',

                'mp.patient_code',

                'sp.student_code',

                'p.full_name_ar',

                'p.full_name_en',

                'dp.full_name_ar as doctor_ar',

                'dp.full_name_en as doctor_en',
            ])
            ->orderByDesc('r.prescribed_at')
            ->paginate(20);


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
            ->where(
                'mp.status',
                'active'
            )
            ->select([
                'mp.id',
                'mp.patient_code',
                'p.full_name_ar',
                'p.full_name_en',
            ])
            ->orderBy('p.full_name_ar')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Doctors
        |--------------------------------------------------------------------------
        */

        $doctors = DB::table('medical_staff as ms')
            ->join(
                'staff_profiles as sp',
                'sp.id',
                '=',
                'ms.staff_profile_id'
            )
            ->join(
                'people as p',
                'p.id',
                '=',
                'sp.person_id'
            )
            ->where(
                'ms.profession',
                'doctor'
            )
            ->where(
                'ms.status',
                'active'
            )
            ->select([
                'ms.id',
                'p.full_name_ar',
                'p.full_name_en',
            ])
            ->orderBy('p.full_name_ar')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Medicines
        |--------------------------------------------------------------------------
        */

        $medicines = DB::table('medical_medicines')
            ->where(
                'is_active',
                1
            )
            ->select([
                'id',
                'name_ar',
                'name_en',
                'generic_name',
                'unit',
            ])
            ->orderBy('name_ar')
            ->get();


        return view(
            'livewire.admin.medical.prescriptions.index',
            compact(
                'rx',
                'patients',
                'doctors',
                'medicines'
            )
        )->layout('layouts.admin');
    }
}

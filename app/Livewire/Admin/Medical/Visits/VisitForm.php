<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Medical\Visits;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

final class VisitForm extends Component
{
    use HasAdminAuth;

    /*
    |--------------------------------------------------------------------------
    | Visit fields
    |--------------------------------------------------------------------------
    */

    public string $patientId = '';

    public string $institutionId = '';

    public string $doctorId = '';

    public string $nurseId = '';

    public string $visitedAt = '';

    public string $status = 'open';

    public string $chiefComplaint = '';

    public string $temperature = '';

    public string $bloodPressure = '';

    public string $pulse = '';

    public string $weight = '';

    public string $diagnosis = '';

    public string $treatmentPlan = '';

    public string $notes = '';


    /*
    |--------------------------------------------------------------------------
    | Mount
    |--------------------------------------------------------------------------
    */

    public function mount(): void
    {
        $this->requirePermission('medical.visit.create');

        $this->visitedAt = now()->format('Y-m-d\TH:i');
    }


    /*
    |--------------------------------------------------------------------------
    | Save visit
    |--------------------------------------------------------------------------
    */

    public function save(): void
    {
        $validated = $this->validate([
            'patientId' => [
                'required',
                'integer',
            ],

            'institutionId' => [
                'required',
                'integer',
            ],

            'doctorId' => [
                'nullable',
                'integer',
            ],

            'nurseId' => [
                'nullable',
                'integer',
            ],

            'visitedAt' => [
                'required',
                'date',
            ],

            'status' => [
                'required',
                'in:open,in_progress,completed,cancelled',
            ],

            'chiefComplaint' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'temperature' => [
                'nullable',
                'numeric',
                'max:100',
            ],

            'bloodPressure' => [
                'nullable',
                'string',
                'max:50',
            ],

            'pulse' => [
                'nullable',
                'numeric',
                'max:300',
            ],

            'weight' => [
                'nullable',
                'numeric',
                'max:500',
            ],

            'diagnosis' => [
                'nullable',
                'string',
                'max:4000',
            ],

            'treatmentPlan' => [
                'nullable',
                'string',
                'max:4000',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:4000',
            ],
        ]);

        DB::table('medical_visits')->insert([
            'visit_number' => 'VIS-'
                . now()->format('YmdHis')
                . '-'
                . str()->upper(str()->random(5)),

            'patient_id' => (int) $validated['patientId'],

            'institution_id' => (int) $validated['institutionId'],

            'doctor_id' => ! empty($validated['doctorId'])
                ? (int) $validated['doctorId']
                : null,

            'nurse_id' => ! empty($validated['nurseId'])
                ? (int) $validated['nurseId']
                : null,

            'visited_at' => date(
                'Y-m-d H:i:s',
                strtotime($validated['visitedAt'])
            ),

            'status' => $validated['status'],

            'chief_complaint' => $validated['chiefComplaint'] ?: null,

            'temperature' => $validated['temperature'] ?: null,

            'blood_pressure' => $validated['bloodPressure'] ?: null,

            'pulse' => $validated['pulse'] ?: null,

            'weight' => $validated['weight'] ?: null,

            'diagnosis' => $validated['diagnosis'] ?: null,

            'treatment_plan' => $validated['treatmentPlan'] ?: null,

            'notes' => $validated['notes'] ?: null,

            'created_at' => now(),

            'updated_at' => now(),
        ]);

        session()->flash(
            'medical_message',
            __('medical.saved')
        );

        $this->redirect(
            route('admin.medical.visits.index'),
            navigate: true
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
        | Active patients
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
            ->orderBy(
                'p.full_name_ar'
            )
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Medical clinics / points
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
        | Active medical staff
        |--------------------------------------------------------------------------
        */

        $staff = DB::table('medical_staff as ms')
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
                'ms.status',
                'active'
            )
            ->select([
                'ms.id',
                'ms.profession',
                'p.full_name_ar',
                'p.full_name_en',
            ])
            ->orderBy(
                'p.full_name_ar'
            )
            ->get();


        return view(
            'livewire.admin.medical.visits.form',
            compact(
                'patients',
                'clinics',
                'staff'
            )
        )->layout('layouts.admin');
    }
}

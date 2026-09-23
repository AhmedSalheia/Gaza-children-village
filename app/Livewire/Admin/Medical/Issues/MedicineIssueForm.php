<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Medical\Issues;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use App\Services\Medical\MedicineStockService;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

final class MedicineIssueForm extends Component
{
    use HasAdminAuth;

    public string $medicineId = '';

    public string $institutionId = '';

    public string $patientId = '';

    public string $visitId = '';

    public string $quantity = '';

    public string $type = 'issue';

    public string $referenceNumber = '';

    public string $reason = '';


    /*
    |--------------------------------------------------------------------------
    | Mount
    |--------------------------------------------------------------------------
    */

    public function mount(): void
    {
        $this->requirePermission('medical.medicine.issue');
    }


    /*
    |--------------------------------------------------------------------------
    | Save
    |--------------------------------------------------------------------------
    */

    public function save(MedicineStockService $service): void
    {
        $this->requirePermission('medical.medicine.issue');

        $validated = $this->validate([
            'medicineId' => [
                'required',
                'integer',
            ],

            'institutionId' => [
                'required',
                'integer',
            ],

            'patientId' => [
                'nullable',
                'integer',
            ],

            'visitId' => [
                'nullable',
                'integer',
            ],

            'quantity' => [
                'required',
                'numeric',
                'min:0.001',
            ],

            'type' => [
                'required',
                'in:issue,disposal,adjustment',
            ],

            'referenceNumber' => [
                'nullable',
                'string',
                'max:120',
            ],

            'reason' => [
                'nullable',
                'string',
                'max:4000',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Issue stock
        |--------------------------------------------------------------------------
        */

        try {

            $service->issue([
                'medicine_id' => (int) $validated['medicineId'],

                'institution_id' => (int) $validated['institutionId'],

                'patient_id' => $validated['patientId']
                    ? (int) $validated['patientId']
                    : null,

                'visit_id' => $validated['visitId']
                    ? (int) $validated['visitId']
                    : null,

                'quantity' => $validated['quantity'],

                'type' => $validated['type'],

                'reference_number' =>
                    $validated['referenceNumber'] ?: null,

                'reason' =>
                    $validated['reason'] ?: null,

                'actor_account_id' => $this->adminId(),
            ]);


            /*
            |--------------------------------------------------------------------------
            | Success
            |--------------------------------------------------------------------------
            */

            session()->flash(
                'medical_message',
                __('medical.issue_saved')
            );


            $this->reset([
                'quantity',
                'referenceNumber',
                'reason',
                'patientId',
                'visitId',
            ]);

            $this->type = 'issue';

        } catch (\Throwable $e) {

            $this->addError(
                'quantity',
                $e->getMessage()
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Reset
    |--------------------------------------------------------------------------
    */

    public function resetForm(): void
    {
        $this->reset([
            'medicineId',
            'institutionId',
            'patientId',
            'visitId',
            'quantity',
            'referenceNumber',
            'reason',
        ]);

        $this->type = 'issue';

        $this->resetValidation();
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
        | Medicines
        |--------------------------------------------------------------------------
        */

        $medicines = DB::table('medical_medicines')
            ->where('is_active', 1)
            ->orderBy('name_ar')
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
        | Recent visits
        |--------------------------------------------------------------------------
        */

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
            ->select([
                'v.id',
                'v.visited_at',
                'v.patient_id',
                'mp.patient_code',
                'p.full_name_ar',
                'p.full_name_en',
            ])
            ->orderByDesc('v.visited_at')
            ->limit(100)
            ->get();


        return view(
            'livewire.admin.medical.issues.form',
            compact(
                'medicines',
                'clinics',
                'patients',
                'visits'
            )
        )->layout('layouts.admin');
    }
}

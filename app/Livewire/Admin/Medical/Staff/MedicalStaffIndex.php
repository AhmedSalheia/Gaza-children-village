<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Medical\Staff;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

final class MedicalStaffIndex extends Component
{
    use HasAdminAuth;

    /*
    |--------------------------------------------------------------------------
    | Filters
    |--------------------------------------------------------------------------
    */

    public string $profession = 'all';

    public string $institutionId = '';

    /*
    |--------------------------------------------------------------------------
    | Form
    |--------------------------------------------------------------------------
    */

    public string $staffProfileId = '';

    public string $licenseNumber = '';

    public string $specialization = '';

    public string $status = 'active';

    public string $startedOn = '';

    public string $notes = '';

    public bool $showForm = false;


    /*
    |--------------------------------------------------------------------------
    | Mount
    |--------------------------------------------------------------------------
    */

    public function mount(): void
    {
        $this->requirePermission('medical.staff.view');
    }


    /*
    |--------------------------------------------------------------------------
    | Open form
    |--------------------------------------------------------------------------
    */

    public function openForm(): void
    {
        $this->requirePermission('medical.staff.manage');

        $this->resetForm();

        $this->showForm = true;
    }


    /*
    |--------------------------------------------------------------------------
    | Save medical staff
    |--------------------------------------------------------------------------
    */

    public function save(): void
    {
        $this->requirePermission('medical.staff.manage');

        $validated = $this->validate([
            'staffProfileId' => [
                'required',
                'integer',
            ],

            'institutionId' => [
                'required',
                'integer',
            ],

            'profession' => [
                'required',
                'in:doctor,nurse,pharmacist,other',
            ],

            'licenseNumber' => [
                'nullable',
                'string',
                'max:120',
            ],

            'specialization' => [
                'nullable',
                'string',
                'max:255',
            ],

            'status' => [
                'required',
                'in:active,inactive',
            ],

            'startedOn' => [
                'nullable',
                'date',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:4000',
            ],
        ]);

        DB::table('medical_staff')->updateOrInsert(
            [
                'staff_profile_id' => (int) $validated['staffProfileId'],
            ],
            [
                'institution_id' => (int) $validated['institutionId'],

                'profession' => $validated['profession'],

                'license_number' => $validated['licenseNumber']
                    ?: null,

                'specialization' => $validated['specialization']
                    ?: null,

                'status' => $validated['status'],

                'started_on' => $validated['startedOn']
                    ?: null,

                'notes' => $validated['notes']
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
            'institutionId',
            'staffProfileId',
            'licenseNumber',
            'specialization',
            'startedOn',
            'notes',
        ]);

        $this->profession = 'doctor';

        $this->status = 'active';

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
        | Current medical staff
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
            ->join(
                'institutions as i',
                'i.id',
                '=',
                'ms.institution_id'
            )
            ->select([
                'ms.*',

                'sp.staff_code',

                'p.full_name_ar',
                'p.full_name_en',

                'i.name_ar as clinic_ar',
                'i.name_en as clinic_en',
            ])
            ->when(
                $this->profession !== 'all',
                fn ($query) => $query->where(
                    'ms.profession',
                    $this->profession
                )
            )
            ->orderBy('p.full_name_ar')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Available staff profiles
        |--------------------------------------------------------------------------
        |
        | Active staff members who are not registered in medical_staff yet.
        |
        */

        $available = DB::table('staff_profiles as sp')
            ->join(
                'people as p',
                'p.id',
                '=',
                'sp.person_id'
            )
            ->leftJoin(
                'medical_staff as ms',
                'ms.staff_profile_id',
                '=',
                'sp.id'
            )
            ->where(
                'sp.employment_status',
                'active'
            )
            ->whereNull('ms.id')
            ->select([
                'sp.id',
                'sp.staff_code',
                'p.full_name_ar',
                'p.full_name_en',
            ])
            ->orderBy('p.full_name_ar')
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


        return view(
            'livewire.admin.medical.staff.index',
            compact(
                'staff',
                'available',
                'clinics'
            )
        )->layout('layouts.admin');
    }
}

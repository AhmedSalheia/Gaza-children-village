<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Medical\Medicines;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

final class MedicineIndex extends Component
{
    use HasAdminAuth;

    #[Url(as: 'q')]
    public string $q = '';

    public bool $showForm = false;

    public string $code = '';

    public string $nameAr = '';

    public string $nameEn = '';

    public string $genericName = '';

    public string $form = '';

    public string $strength = '';

    public string $unit = 'unit';

    public string $manufacturer = '';

    public string $reorderLevel = '0';

    public string $minimumStock = '0';

    public string $maximumStock = '';

    public string $notes = '';


    /*
    |--------------------------------------------------------------------------
    | Mount
    |--------------------------------------------------------------------------
    */

    public function mount(): void
    {
        $this->requirePermission('medical.medicine.view');
    }


    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */

    public function updatedQ(): void
    {
        // Search is URL-bound; no pagination reset is required here
        // because this page displays the medicine collection directly.
    }


    /*
    |--------------------------------------------------------------------------
    | Open form
    |--------------------------------------------------------------------------
    */

    public function openForm(): void
    {
        $this->requirePermission('medical.medicine.manage');

        $this->resetForm();

        $this->showForm = true;
    }


    /*
    |--------------------------------------------------------------------------
    | Save
    |--------------------------------------------------------------------------
    */

    public function save(): void
    {
        $this->requirePermission('medical.medicine.manage');

        $validated = $this->validate([
            'code' => [
                'required',
                'string',
                'max:80',
            ],

            'nameAr' => [
                'required',
                'string',
                'max:255',
            ],

            'nameEn' => [
                'required',
                'string',
                'max:255',
            ],

            'genericName' => [
                'nullable',
                'string',
                'max:255',
            ],

            'form' => [
                'nullable',
                'string',
                'max:120',
            ],

            'strength' => [
                'nullable',
                'string',
                'max:120',
            ],

            'unit' => [
                'required',
                'string',
                'max:50',
            ],

            'manufacturer' => [
                'nullable',
                'string',
                'max:255',
            ],

            'reorderLevel' => [
                'required',
                'numeric',
                'min:0',
            ],

            'minimumStock' => [
                'required',
                'numeric',
                'min:0',
            ],

            'maximumStock' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:4000',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Validate maximum stock
        |--------------------------------------------------------------------------
        */

        if (
            $validated['maximumStock'] !== null
            && $validated['maximumStock'] !== ''
            && (float) $validated['maximumStock']
                < (float) $validated['minimumStock']
        ) {
            $this->addError(
                'maximumStock',
                __('medical.maximum_stock_invalid', [], null, 'Maximum stock must be greater than or equal to minimum stock.')
            );

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Prevent duplicate medicine code
        |--------------------------------------------------------------------------
        */

        $codeExists = DB::table('medical_medicines')
            ->where('code', $validated['code'])
            ->exists();

        if ($codeExists) {
            $this->addError(
                'code',
                __('medical.medicine_code_exists', [], null, 'This medicine code already exists.')
            );

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Insert
        |--------------------------------------------------------------------------
        */

        DB::table('medical_medicines')->insert([
            'code' => $validated['code'],

            'name_ar' => $validated['nameAr'],

            'name_en' => $validated['nameEn'],

            'generic_name' =>
                $validated['genericName'] ?: null,

            'form' =>
                $validated['form'] ?: null,

            'strength' =>
                $validated['strength'] ?: null,

            'unit' =>
                $validated['unit'] ?: 'unit',

            'manufacturer' =>
                $validated['manufacturer'] ?: null,

            'reorder_level' =>
                $validated['reorderLevel'],

            'minimum_stock' =>
                $validated['minimumStock'],

            'maximum_stock' =>
                $validated['maximumStock'] !== ''
                    ? $validated['maximumStock']
                    : null,

            'is_active' => 1,

            'notes' =>
                $validated['notes'] ?: null,

            'created_at' => now(),

            'updated_at' => now(),
        ]);


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
            'code',
            'nameAr',
            'nameEn',
            'genericName',
            'form',
            'strength',
            'unit',
            'manufacturer',
            'reorderLevel',
            'minimumStock',
            'maximumStock',
            'notes',
        ]);

        $this->unit = 'unit';

        $this->reorderLevel = '0';

        $this->minimumStock = '0';

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
        | Medicines + current stock
        |--------------------------------------------------------------------------
        */

        $medicines = DB::table('medical_medicines as m')
            ->leftJoin(
                'medical_medicine_batches as b',
                function ($join): void {
                    $join
                        ->on(
                            'b.medicine_id',
                            '=',
                            'm.id'
                        )
                        ->where(
                            'b.current_quantity',
                            '>',
                            0
                        );
                }
            )
            ->select([
                'm.id',
                'm.code',
                'm.name_ar',
                'm.name_en',
                'm.generic_name',
                'm.form',
                'm.strength',
                'm.unit',
                'm.manufacturer',
                'm.reorder_level',
                'm.minimum_stock',
                'm.maximum_stock',
                'm.is_active',
                'm.notes',
                'm.created_at',
                'm.updated_at',
            ])
            ->selectRaw(
                'COALESCE(SUM(b.current_quantity), 0) as stock'
            )
            ->when(
                $q !== '',
                function ($query) use ($q): void {
                    $query->where(function ($search) use ($q): void {
                        $search
                            ->where(
                                'm.code',
                                'like',
                                "%{$q}%"
                            )
                            ->orWhere(
                                'm.name_ar',
                                'like',
                                "%{$q}%"
                            )
                            ->orWhere(
                                'm.name_en',
                                'like',
                                "%{$q}%"
                            )
                            ->orWhere(
                                'm.generic_name',
                                'like',
                                "%{$q}%"
                            );
                    });
                }
            )
            ->groupBy([
                'm.id',
                'm.code',
                'm.name_ar',
                'm.name_en',
                'm.generic_name',
                'm.form',
                'm.strength',
                'm.unit',
                'm.manufacturer',
                'm.reorder_level',
                'm.minimum_stock',
                'm.maximum_stock',
                'm.is_active',
                'm.notes',
                'm.created_at',
                'm.updated_at',
            ])
            ->orderBy('m.name_ar')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Statistics
        |--------------------------------------------------------------------------
        */

        $activeMedicines = DB::table('medical_medicines')
            ->where('is_active', 1)
            ->count();

        $inactiveMedicines = DB::table('medical_medicines')
            ->where('is_active', 0)
            ->count();

        $lowStock = $medicines
            ->filter(
                fn ($medicine): bool =>
                    (float) $medicine->stock
                    <= (float) $medicine->reorder_level
            )
            ->count();

        $outOfStock = $medicines
            ->filter(
                fn ($medicine): bool =>
                    (float) $medicine->stock <= 0
            )
            ->count();


        return view(
            'livewire.admin.medical.medicines.index',
            compact(
                'medicines',
                'activeMedicines',
                'inactiveMedicines',
                'lowStock',
                'outOfStock'
            )
        )->layout('layouts.admin');
    }
}

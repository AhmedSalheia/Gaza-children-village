<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Medical\Receipts;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use App\Services\Medical\MedicineStockService;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

final class MedicineReceiptForm extends Component
{
    use HasAdminAuth;

    /*
    |--------------------------------------------------------------------------
    | Form fields
    |--------------------------------------------------------------------------
    */

    public string $medicineId = '';

    public string $institutionId = '';

    public string $batchNumber = '';

    public string $expiryDate = '';

    public string $quantity = '';

    public string $unitCost = '0';

    public string $supplier = '';

    public string $referenceNumber = '';

    public string $reason = '';

    public string $receivedAt = '';


    /*
    |--------------------------------------------------------------------------
    | Mount
    |--------------------------------------------------------------------------
    */

    public function mount(): void
    {
        $this->requirePermission('medical.medicine.receipt');

        $this->receivedAt = now()->format('Y-m-d\TH:i');
    }


    /*
    |--------------------------------------------------------------------------
    | Save receipt
    |--------------------------------------------------------------------------
    */

    public function save(MedicineStockService $service): void
    {
        $validated = $this->validate([
            'medicineId' => [
                'required',
                'integer',
            ],

            'institutionId' => [
                'required',
                'integer',
            ],

            'batchNumber' => [
                'required',
                'string',
                'max:120',
            ],

            'expiryDate' => [
                'required',
                'date',
                'after:today',
            ],

            'quantity' => [
                'required',
                'numeric',
                'min:0.001',
            ],

            'unitCost' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'supplier' => [
                'nullable',
                'string',
                'max:255',
            ],

            'referenceNumber' => [
                'nullable',
                'string',
                'max:120',
            ],

            'reason' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'receivedAt' => [
                'nullable',
                'date',
            ],
        ]);


        $service->receive([
            'medicine_id' => (int) $validated['medicineId'],
            'institution_id' => (int) $validated['institutionId'],
            'batch_number' => $validated['batchNumber'],
            'expiry_date' => $validated['expiryDate'],
            'quantity' => $validated['quantity'],
            'unit_cost' => $validated['unitCost'] ?: 0,
            'supplier' => $validated['supplier'] ?: null,
            'reference_number' => $validated['referenceNumber'] ?: null,
            'reason' => $validated['reason'] ?: null,
            'received_at' => $validated['receivedAt'] ?: now(),
            'actor_account_id' => $this->adminId(),
        ]);


        session()->flash(
            'medical_message',
            __('medical.receipt_saved')
        );


        $this->reset([
            'batchNumber',
            'expiryDate',
            'quantity',
            'supplier',
            'referenceNumber',
            'reason',
        ]);

        $this->unitCost = '0';

        $this->receivedAt = now()->format('Y-m-d\TH:i');
    }


    /*
    |--------------------------------------------------------------------------
    | Render
    |--------------------------------------------------------------------------
    */

    public function render(): View
    {
        $medicines = DB::table('medical_medicines')
            ->where('is_active', 1)
            ->select([
                'id',
                'name_ar',
                'name_en',
                'generic_name',
                'unit',
            ])
            ->orderBy('name_ar')
            ->get();


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
            'livewire.admin.medical.receipts.form',
            compact(
                'medicines',
                'clinics'
            )
        )->layout('layouts.admin');
    }
}

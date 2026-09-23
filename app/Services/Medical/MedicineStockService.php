<?php
declare(strict_types=1);

namespace App\Services\Medical;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class MedicineStockService
{
    public function receive(array $data): int
    {
        return DB::transaction(function () use ($data) {
            $batch = DB::table('medical_medicine_batches')->insertGetId([
                'medicine_id' => $data['medicine_id'],
                'institution_id' => $data['institution_id'],
                'batch_number' => $data['batch_number'],
                'expiry_date' => $data['expiry_date'],
                'received_quantity' => $data['quantity'],
                'current_quantity' => $data['quantity'],
                'unit_cost' => $data['unit_cost'] ?? 0,
                'supplier' => $data['supplier'] ?? null,
                'received_at' => $data['received_at'] ?? now(),
                'status' => 'active',
                'notes' => $data['notes'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('medical_medicine_transactions')->insert([
                'transaction_number' => 'MED-IN-'.now()->format('YmdHis').'-'.str()->upper(str()->random(5)),
                'medicine_id' => $data['medicine_id'],
                'batch_id' => $batch,
                'institution_id' => $data['institution_id'],
                'type' => 'receipt',
                'quantity' => $data['quantity'],
                'unit_cost' => $data['unit_cost'] ?? 0,
                'reference_number' => $data['reference_number'] ?? null,
                'reason' => $data['reason'] ?? null,
                'actor_account_id' => $data['actor_account_id'] ?? null,
                'occurred_at' => $data['received_at'] ?? now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return $batch;
        });
    }

    public function issue(array $data): int
    {
        return DB::transaction(function () use ($data) {
            $remaining = (float) $data['quantity'];
            $batches = DB::table('medical_medicine_batches')
                ->where('medicine_id', $data['medicine_id'])
                ->where('institution_id', $data['institution_id'])
                ->where('current_quantity', '>', 0)
                ->whereDate('expiry_date', '>=', now()->toDateString())
                ->where('status', 'active')
                ->orderBy('expiry_date')
                ->lockForUpdate()
                ->get();

            $available = (float) $batches->sum('current_quantity');
            if ($available < $remaining) {
                throw new RuntimeException(__('medical.errors.insufficient_stock'));
            }

            $firstTransaction = null;
            foreach ($batches as $batch) {
                if ($remaining <= 0) break;
                $take = min($remaining, (float) $batch->current_quantity);
                DB::table('medical_medicine_batches')->where('id', $batch->id)->update([
                    'current_quantity' => (float)$batch->current_quantity - $take,
                    'status' => ((float)$batch->current_quantity - $take) <= 0 ? 'depleted' : 'active',
                    'updated_at' => now(),
                ]);
                $transactionId = DB::table('medical_medicine_transactions')->insertGetId([
                    'transaction_number' => 'MED-OUT-'.now()->format('YmdHis').'-'.str()->upper(str()->random(5)),
                    'medicine_id' => $data['medicine_id'],
                    'batch_id' => $batch->id,
                    'institution_id' => $data['institution_id'],
                    'patient_id' => $data['patient_id'] ?? null,
                    'visit_id' => $data['visit_id'] ?? null,
                    'type' => $data['type'] ?? 'issue',
                    'quantity' => $take,
                    'unit_cost' => $batch->unit_cost,
                    'reference_number' => $data['reference_number'] ?? null,
                    'reason' => $data['reason'] ?? null,
                    'actor_account_id' => $data['actor_account_id'] ?? null,
                    'occurred_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $firstTransaction ??= $transactionId;
                $remaining -= $take;
            }
            return (int)$firstTransaction;
        });
    }
}

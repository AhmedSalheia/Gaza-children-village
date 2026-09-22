<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Inventory\Approvals;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use App\Models\Inventory\InventoryApprovalStep;
use App\Services\Inventory\InventoryApprovalService;
use Illuminate\View\View;
use Livewire\Component;

class ApprovalInbox extends Component
{
    use HasAdminAuth;

    public function mount(): void
    {
        $this->requirePermission('inventory.issue.approve');
    }

    public function decide(int $id, string $decision): void
    {
        $step = InventoryApprovalStep::findOrFail($id);

        app(InventoryApprovalService::class)->approve(
            $step,
            $decision,
            null
        );
    }

    public function render(): View
    {
        return view(
            'livewire.admin.inventory.approvals.approval-inbox',
            [
                'steps' => InventoryApprovalStep::with('request.institution')
                    ->where('status', 'pending')
                    ->orderBy('created_at')
                    ->get(),
            ]
        )->layout('layouts.admin');
    }
}

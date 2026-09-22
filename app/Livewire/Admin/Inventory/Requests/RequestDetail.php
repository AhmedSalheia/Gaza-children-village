<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Inventory\Requests;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use App\Models\Inventory\InventoryApprovalStep;
use App\Models\Inventory\InventoryRequest;
use App\Services\Inventory\InventoryApprovalService;
use App\Services\Inventory\InventoryService;
use Illuminate\View\View;
use Livewire\Component;

final class RequestDetail extends Component
{
    use HasAdminAuth;

    public InventoryRequest $request;

    public string $comment = '';

    public function mount(InventoryRequest $request): void
    {
        $this->requirePermission('inventory.view');

        $this->request = $request->load([
            'lines.item',
            'institution',
            'warehouse',
            'approvals',
        ]);
    }

    public function decide(
        int $step,
        string $decision
    ): void {
        $this->requirePermission(
            'inventory.issue.approve'
        );

        app(InventoryApprovalService::class)->approve(
            InventoryApprovalStep::findOrFail($step),
            $decision,
            $this->comment
        );

        $this->request
            ->refresh()
            ->load([
                'lines.item',
                'institution',
                'warehouse',
                'approvals',
            ]);
    }

    public function execute(): void
    {
        $this->requirePermission(
            'inventory.issue.create'
        );

        abort_unless(
            $this->request->status === 'approved_for_issue',
            422
        );

        foreach ($this->request->lines as $line) {
            app(InventoryService::class)->issue(
                $this->request->source_warehouse_id,
                $line->item_id,
                (float) (
                    $line->approved_quantity
                    ?? $line->requested_quantity
                ),
                $this->request->reason_ar,
                $this->request->id
            );
        }

        $this->request->update([
            'status' => 'issued',
        ]);
    }

    public function render(): View
    {
        return view('livewire.admin.inventory.requests.request-detail')->layout('layouts.admin');
    }
}

<?php

declare(strict_types=1);

namespace App\Livewire\Guardian\Corrections;

use App\Livewire\Guardian\Concerns\HasGuardianAuth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;
use Modules\Requests\Models\StudentCorrectionRequest;
use Modules\Requests\Services\CorrectionRequestService;

/**
 * Guardian portal: detail view for a single correction request.
 *
 * Shows:
 *   - Current workflow state
 *   - Timeline of workflow actions (history)
 *   - The proposed value and official snapshot
 *   - Clarification response form (when state = clarification_requested)
 *   - Cancel button (when state allows it)
 *
 * Access: guardian must own this request (guardian_account_id match).
 */
final class CorrectionDetail extends Component
{
    use HasGuardianAuth;

    public int $requestId;

    public string $clarificationResponse = '';

    public string $revisedValue = '';

    /** @var string[] */
    public array $errors = [];

    public bool $resubmitted = false;

    public bool $cancelled = false;

    public function mount(int $requestId): void
    {
        $this->requestId = $requestId;
        $this->assertOwnership();
    }

    // -----------------------------------------------------------------
    // Actions
    // -----------------------------------------------------------------

    public function resubmit(): void
    {
        $this->errors = [];
        $request = $this->loadRequest();
        $guardianId = (int) auth('guardian')->id();

        if (trim($this->revisedValue) === '') {
            $this->errors[] = __('requests.error_value_required', [], null, 'Please enter a value.');

            return;
        }

        try {
            app(CorrectionRequestService::class)->resubmit(
                request: $request,
                guardianAccountId: $guardianId,
                revisedProposedValue: $this->revisedValue,
                explanation: $this->clarificationResponse ?: null,
            );

            $this->resubmitted = true;
        } catch (\RuntimeException $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    public function cancel(): void
    {
        $request = $this->loadRequest();
        $guardianId = (int) auth('guardian')->id();

        try {
            app(CorrectionRequestService::class)->cancelByGuardian($request, $guardianId);
            $this->cancelled = true;
        } catch (\RuntimeException $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    // -----------------------------------------------------------------
    // Render
    // -----------------------------------------------------------------

    public function render(): View
    {
        $request = $this->loadRequest();
        $instance = $this->loadInstance($request);
        $timeline = $this->loadTimeline($instance->id);
        $proposal = $request->proposals()->orderByDesc('submission_sequence')->first();

        return view('livewire.guardian.corrections.correction-detail', [
            'request' => $request,
            'instance' => $instance,
            'timeline' => $timeline,
            'proposal' => $proposal,
        ])->layout('layouts.guardian');
    }

    // -----------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------

    private function assertOwnership(): void
    {
        $guardianId = (int) auth('guardian')->id();

        $exists = DB::table('student_correction_requests')
            ->where('id', $this->requestId)
            ->where('guardian_account_id', $guardianId)
            ->exists();

        if (! $exists) {
            abort(403);
        }
    }

    private function loadRequest(): StudentCorrectionRequest
    {
        return StudentCorrectionRequest::findOrFail($this->requestId);
    }

    private function loadInstance(StudentCorrectionRequest $request): object
    {
        $instanceClass = 'Modules\\Workflow\\Models\\WorkflowInstance';

        return $instanceClass::findOrFail($request->workflow_instance_id);
    }

    private function loadTimeline(int $instanceId): Collection
    {
        return DB::table('workflow_actions as wa')
            ->where('wa.workflow_instance_id', $instanceId)
            ->select(
                'wa.id',
                'wa.action_code',
                'wa.previous_state',
                'wa.new_state',
                'wa.decision',
                'wa.comment',
                'wa.actor_type',
                'wa.created_at',
            )
            ->orderBy('wa.id')
            ->get();
    }
}

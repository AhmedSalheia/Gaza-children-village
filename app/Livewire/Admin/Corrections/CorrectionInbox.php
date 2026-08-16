<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Corrections;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;
use Modules\Authorization\Data\PermissionKey;
use Modules\Requests\Exceptions\CorrectionConflictException;
use Modules\Requests\Models\StudentCorrectionRequest;
use Modules\Requests\Services\CorrectionApplicationService;
use Modules\Requests\Services\CorrectionRequestService;

/**
 * Admin portal: central inbox for all guardian correction requests.
 *
 * Data administrators (correction.review + correction.approve + correction.apply)
 * see all requests across all institutions, with filtering by state, institution,
 * classification, and conflict flag.
 *
 * Access gated on CORRECTION_REVIEW permission; approve and apply are additionally
 * gated on CORRECTION_APPROVE and CORRECTION_APPLY respectively.
 */
final class CorrectionInbox extends Component
{
    use HasAdminAuth;

    public string $stateFilter = '';

    public string $classFilter = '';

    public bool $conflictOnly = false;

    public string $comment = '';

    /**
     * Set when the admin clicks "Approve" to open the inline comment modal.
     * Null when no approval is pending confirmation.
     */
    public ?int $confirmingRequestId = null;

    /** @var string[] */
    public array $errors = [];

    public ?string $flashMessage = null;

    public function mount(): void
    {
        $this->requirePermission(PermissionKey::CORRECTION_REVIEW);
    }

    // -----------------------------------------------------------------
    // Admin approve / apply actions
    // Data admins may approve/apply cross-institution from central inbox.
    // Sensitive corrections are not approvable from the inbox — they require
    // credential reconfirmation via the staff portal review screen.
    // -----------------------------------------------------------------

    /**
     * Open the inline approve confirmation form for a standard correction.
     * Sensitive corrections are rejected here; the Blade view enforces this too.
     */
    public function initiateApprove(int $requestId): void
    {
        if (! $this->adminCan(PermissionKey::CORRECTION_APPROVE)) {
            abort(403);
        }

        $request = StudentCorrectionRequest::findOrFail($requestId);

        if ($request->isSensitive()) {
            $this->errors[] = __('requests.sensitive_needs_principal', [], null, 'Sensitive corrections must be approved by a principal via the staff portal.');

            return;
        }

        $this->confirmingRequestId = $requestId;
        $this->comment = '';
        $this->errors = [];
    }

    public function cancelApprove(): void
    {
        $this->confirmingRequestId = null;
        $this->comment = '';
        $this->errors = [];
    }

    public function confirmApprove(): void
    {
        if (! $this->adminCan(PermissionKey::CORRECTION_APPROVE)) {
            abort(403);
        }

        if ($this->confirmingRequestId === null) {
            return;
        }

        $this->errors = [];
        $request = StudentCorrectionRequest::findOrFail($this->confirmingRequestId);

        try {
            app(CorrectionRequestService::class)->approve(
                request: $request,
                actorAccountId: $this->adminId(),
                actorType: 'administrative',
                portal: 'admin',
                comment: $this->comment ?: null,
                reconfirmationTokenId: null, // standard corrections do not require a token
            );

            $this->confirmingRequestId = null;
            $this->comment = '';
            $this->flashMessage = __('requests.approved', [], null, 'Correction approved.');
        } catch (\RuntimeException $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    public function apply(int $requestId): void
    {
        if (! $this->adminCan(PermissionKey::CORRECTION_APPLY)) {
            abort(403);
        }

        $this->errors = [];

        try {
            app(CorrectionApplicationService::class)->apply(
                request: StudentCorrectionRequest::findOrFail($requestId),
                appliedByAccountId: $this->adminId(),
                actorType: 'administrative',
                portal: 'admin',
            );

            $this->flashMessage = __('requests.applied', [], null, 'Correction applied successfully.');
        } catch (CorrectionConflictException $e) {
            $this->errors[] = $e->getMessage();
        } catch (\RuntimeException $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    // -----------------------------------------------------------------
    // Render
    // -----------------------------------------------------------------

    public function render(): View
    {
        return view('livewire.admin.corrections.correction-inbox', [
            'requests' => $this->loadRequests(),
        ])->layout('layouts.admin');
    }

    private function loadRequests(): Collection
    {
        $query = DB::table('student_correction_requests as scr')
            ->join('workflow_instances as wi', 'wi.id', '=', 'scr.workflow_instance_id')
            ->join('student_profiles as sp', 'sp.id', '=', 'scr.student_profile_id')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->leftJoin('institutions as i', 'i.id', '=', 'scr.institution_id')
            ->select(
                'scr.id',
                'scr.field_catalogue_code',
                'scr.classification',
                'scr.conflict_flag',
                'scr.applied_at',
                'scr.created_at',
                'wi.current_state',
                'p.full_name_ar as student_name',
                'i.name_ar as institution_name',
            )
            ->orderByDesc('scr.created_at');

        if ($this->stateFilter !== '') {
            $query->where('wi.current_state', $this->stateFilter);
        }

        if ($this->classFilter !== '') {
            $query->where('scr.classification', $this->classFilter);
        }

        if ($this->conflictOnly) {
            $query->where('scr.conflict_flag', true);
        }

        return $query->limit(200)->get();
    }
}

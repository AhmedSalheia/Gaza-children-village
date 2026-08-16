<?php

declare(strict_types=1);

namespace App\Livewire\Staff\FormalRequests;

use App\Livewire\Staff\Concerns\HasStaffAuth;
use Illuminate\View\View;
use Livewire\Component;
use Modules\Authorization\Data\PermissionKey;
use Modules\Requests\Models\InstitutionFormalRequest;
use Modules\Requests\Services\InstitutionFormalRequestService;

/**
 * Staff portal: create a new formal request (secretary role).
 *
 * Requires formal_request.prepare permission.
 * Creates a request in 'draft' state.
 */
final class NewFormalRequest extends Component
{
    use HasStaffAuth;

    public string $requestType = 'administrative';

    public string $titleAr = '';

    public string $titleEn = '';

    public string $bodyText = '';    // free-text body (stored as JSON {text: ...})

    public int $priority = 2;

    public string $dueDate = '';

    /** @var list<string> */
    public array $errors = [];

    public function mount(): void
    {
        $this->requirePermission(PermissionKey::FORMAL_REQUEST_PREPARE);
    }

    public function save(): void
    {
        // Re-check permission on each action so revoked grants take effect immediately.
        $this->requirePermission(PermissionKey::FORMAL_REQUEST_PREPARE);

        $this->errors = [];
        $this->validate();

        $scope = $this->staffScope();

        if ($scope['institution_id'] === null) {
            abort(403, 'No active institutional scope for your account.');
        }

        try {
            $request = app(InstitutionFormalRequestService::class)->createDraft(
                institutionId: $scope['institution_id'],
                institutionSemesterId: $scope['institution_semester_id'],
                requestType: $this->requestType,
                titleAr: $this->titleAr,
                titleEn: $this->titleEn,
                body: ['text' => $this->bodyText],
                priority: $this->priority,
                dueDate: $this->dueDate !== '' ? $this->dueDate : null,
                createdByAccountId: (int) auth('staff')->id(),
            );

            $this->redirect(route('staff.formal-requests.detail', $request->id));
        } catch (\RuntimeException|\InvalidArgumentException $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    public function render(): View
    {
        // Re-check on every Livewire round-trip so that revoking the grant
        // from an active staff session takes effect immediately.
        $this->requirePermission(PermissionKey::FORMAL_REQUEST_PREPARE);

        return view('staff.formal-requests.new', [
            'requestTypes' => InstitutionFormalRequest::REQUEST_TYPES,
            'priorityOptions' => [1 => 'Low', 2 => 'Medium', 3 => 'High', 4 => 'Urgent'],
        ]);
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'requestType' => ['required', 'in:'.implode(',', InstitutionFormalRequest::REQUEST_TYPES)],
            'titleAr' => ['required', 'string', 'max:500'],
            'titleEn' => ['required', 'string', 'max:500'],
            'bodyText' => ['required', 'string', 'min:10'],
            'priority' => ['required', 'integer', 'min:1', 'max:4'],
            'dueDate' => ['nullable', 'date', 'after:today'],
        ];
    }
}

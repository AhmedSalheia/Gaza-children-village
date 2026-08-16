<?php

declare(strict_types=1);

namespace App\Livewire\Guardian\Documents;

use App\Livewire\Guardian\Concerns\HasGuardianAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;
use Modules\Documents\Models\StudentDocumentRequest;
use Modules\Documents\Services\DocumentRequestService;

/**
 * Guardian portal: detail view for a single document request.
 *
 * Shows the request status timeline and, when issued, a download link.
 * If the request is pending_clarification, shows a form to respond.
 *
 * Security: the request must belong to the authenticated guardian account.
 */
final class DocumentRequestDetail extends Component
{
    use HasGuardianAuth;

    public int $requestId;

    public string $clarificationResponse = '';

    /** @var string[] */
    public array $errors = [];

    public ?string $flashMessage = null;

    public function mount(int $requestId): void
    {
        if (! $this->hasGuardianProfile()) {
            abort(403, 'No guardian profile linked to this account.');
        }

        $this->requestId = $requestId;
        $this->loadOwnedRequest(); // Throws 403 if not owned
    }

    public function provideClarification(): void
    {
        $this->errors = [];

        if (trim($this->clarificationResponse) === '') {
            $this->errors[] = 'يرجى كتابة رد على طلب التوضيح.';

            return;
        }

        $request = $this->loadOwnedRequest();

        if (! $request->isPendingClarification()) {
            $this->errors[] = 'هذا الطلب لا يتطلب توضيحاً حالياً.';

            return;
        }

        try {
            app(DocumentRequestService::class)->provideClarification($request, $this->clarificationResponse);
            $this->clarificationResponse = '';
            $this->flashMessage = 'تم إرسال ردك بنجاح. سيقوم الفريق بمراجعة طلبك.';
        } catch (\RuntimeException $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    public function cancel(): void
    {
        $this->errors = [];

        $request = $this->loadOwnedRequest();

        if ($request->isTerminal()) {
            $this->errors[] = 'لا يمكن إلغاء هذا الطلب في حالته الحالية.';

            return;
        }

        try {
            app(DocumentRequestService::class)->cancel($request, 'إلغاء بطلب ولي الأمر');
            $this->flashMessage = 'تم إلغاء الطلب.';
        } catch (\RuntimeException $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    public function render(): View
    {
        $request = $this->loadOwnedRequest();
        $issuedDoc = DB::table('issued_documents')
            ->where('request_id', $this->requestId)
            ->whereNull('cancelled_at')
            ->select('id', 'document_number', 'issued_at', 'verification_code')
            ->first();

        $studentName = DB::table('student_profiles as sp')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->where('sp.id', (int) $request->student_profile_id)
            ->value('p.full_name_ar');

        return view('livewire.guardian.documents.document-request-detail', [
            'request' => $request,
            'issuedDoc' => $issuedDoc,
            'studentName' => $studentName,
        ])->layout('layouts.guardian');
    }

    private function loadOwnedRequest(): StudentDocumentRequest
    {
        $request = StudentDocumentRequest::where('id', $this->requestId)
            ->where('requested_by_account_id', (int) auth('guardian')->id())
            ->where('requested_by_actor_type', StudentDocumentRequest::ACTOR_GUARDIAN)
            ->firstOrFail();

        return $request;
    }
}

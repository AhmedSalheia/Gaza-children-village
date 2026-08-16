<?php

declare(strict_types=1);

namespace App\Livewire\Staff\Documents;

use App\Livewire\Staff\Concerns\HasStaffAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;
use Modules\Authorization\Data\PermissionKey;
use Modules\Documents\Models\StudentDocumentRequest;
use Modules\Documents\Services\DocumentCompletionChecker;
use Modules\Documents\Services\DocumentRequestService;

/**
 * Staff portal (secretary): review a single document request.
 *
 * Authorization: uses the same scope contract as all staff portal read paths.
 * A request is in scope only when its institution_id AND institution_semester_id
 * match the staff member's trusted active position scope. Period-restricted
 * positions (secretary, teacher) further require the request's enrollment to
 * fall within an allowed operational period.
 *
 * Gated on DOCUMENT_REVIEW permission.
 */
final class DocumentReview extends Component
{
    use HasStaffAuth;

    public int $requestId;

    public string $clarificationReason = '';

    /** @var string[] */
    public array $errors = [];

    public ?string $flashMessage = null;

    /** @var list<string> */
    public array $completenessFailures = [];

    public function mount(int $requestId): void
    {
        $this->requirePermission(PermissionKey::DOCUMENT_REVIEW);
        $this->requestId = $requestId;
        $this->assertRequestInScope($requestId);
    }

    public function runCompletenessCheck(): void
    {
        $this->errors = [];

        $request = $this->loadScopedRequest();
        $svc = app(DocumentRequestService::class);

        if (! in_array($request->status, [
            StudentDocumentRequest::STATUS_SUBMITTED,
            StudentDocumentRequest::STATUS_COMPLETENESS_FAILED,
        ], true)) {
            $this->errors[] = 'لا يمكن فحص اكتمال البيانات لهذا الطلب في حالته الحالية.';

            return;
        }

        try {
            $svc->startCompletenessCheck($request, $this->staffAccountId());
            $request->refresh();

            $checker = app(DocumentCompletionChecker::class);
            $this->completenessFailures = $checker->check(
                $request->document_type_code,
                $request->enrollment_id,
            );

            $svc->markCompletenessResult($request, $this->completenessFailures, $this->staffAccountId());

            $this->flashMessage = empty($this->completenessFailures)
                ? 'البيانات مكتملة. يمكنك الآن إعادة التوجيه للموافقة.'
                : 'تم العثور على بيانات ناقصة. يرجى مراجعة النتائج.';
        } catch (\RuntimeException $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    public function forwardForApproval(): void
    {
        $this->errors = [];

        $request = $this->loadScopedRequest();

        if ($request->status !== StudentDocumentRequest::STATUS_COMPLETENESS_PASSED) {
            $this->errors[] = 'يجب أن تكون البيانات مكتملة قبل الإحالة للموافقة.';

            return;
        }

        try {
            app(DocumentRequestService::class)->forwardForApproval($request, $this->staffAccountId());
            $this->flashMessage = 'تم إحالة الطلب إلى المدير للموافقة.';
        } catch (\RuntimeException $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    public function requestClarification(): void
    {
        $this->errors = [];

        if (trim($this->clarificationReason) === '') {
            $this->errors[] = 'يرجى كتابة سبب طلب التوضيح.';

            return;
        }

        $request = $this->loadScopedRequest();

        try {
            app(DocumentRequestService::class)->requestClarification(
                $request,
                $this->clarificationReason,
                $this->staffAccountId(),
            );

            $this->clarificationReason = '';
            $this->flashMessage = 'تم إرسال طلب التوضيح إلى ولي الأمر.';
        } catch (\RuntimeException $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    public function render(): View
    {
        $request = $this->loadScopedRequest();
        $studentName = DB::table('student_profiles as sp')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->where('sp.id', (int) $request->student_profile_id)
            ->value('p.full_name_ar');

        return view('livewire.staff.documents.document-review', [
            'request' => $request,
            'studentName' => $studentName,
        ])->layout('layouts.staff');
    }

    /**
     * Load the request ensuring it is within the staff member's trusted scope.
     *
     * Scope check: institution_id + institution_semester_id from the staff's
     * active position must match the request. Period-restricted positions also
     * require the enrollment's class group to be in an allowed operational period.
     */
    private function loadScopedRequest(): StudentDocumentRequest
    {
        $this->assertRequestInScope($this->requestId);

        return StudentDocumentRequest::findOrFail($this->requestId);
    }

    /**
     * Assert the request is accessible to this staff member; abort 404 if not.
     */
    private function assertRequestInScope(int $requestId): void
    {
        $scope = $this->staffScope();

        if ($scope['institution_id'] === null || $scope['institution_semester_id'] === null) {
            abort(403, 'No active institutional scope for your account.');
        }

        $query = DB::table('student_document_requests as dr')
            ->where('dr.id', $requestId)
            ->where('dr.institution_id', $scope['institution_id'])
            ->where('dr.institution_semester_id', $scope['institution_semester_id']);

        // Period-restricted: further scope by enrollment's operational period
        if (! $this->isFullScopePosition()) {
            $allowedPeriods = $this->allowedPeriodIds();

            if (empty($allowedPeriods)) {
                abort(403, 'Your position has no period grants — no document request access.');
            }

            $query->join('student_enrollments as se', 'se.id', '=', 'dr.enrollment_id')
                ->join('class_groups as cg', 'cg.id', '=', 'se.class_group_id')
                ->whereIn('cg.operational_period_id', $allowedPeriods);
        }

        if (! $query->exists()) {
            abort(404);
        }
    }
}

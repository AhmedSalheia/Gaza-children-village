<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Documents\Jobs\GenerateDocumentJob;
use Modules\Documents\Models\DocumentTemplate;
use Modules\Documents\Models\StudentDocumentRequest;
use Modules\Documents\Services\DocumentRequestService;
use Modules\Documents\Services\DocumentTemplateVersionService;

/**
 * Seeds:
 *  - Bilingual (ar + en) template versions for all 7 document types
 *    (Arabic version active; English version kept alongside it).
 *  - Student document requests covering all 13 statuses: draft, submitted,
 *    pending_completeness, completeness_passed, completeness_failed,
 *    awaiting_approval, pending_clarification, approved, generating, issued,
 *    generation_failed, rejected, cancelled.
 *  - Issued demo PDFs: proof_of_enrolment (Arabic AND English) and
 *    semester_grade_report (Arabic).
 *  - One reissue chain: a cancelled issued document superseded by a new one.
 *
 * Idempotent: skips requests when the demo purpose-notes marker exists;
 * template versions are only created when the template has none.
 *
 * Runs AFTER: DemoAccountSeeder, DemoEnrollmentSeeder.
 */
final class DemoDocumentRequestSeeder extends Seeder
{
    private const MARKER = 'Demo seed document request';

    public function run(): void
    {
        abort_if(
            app()->isProduction(),
            403,
            'DemoDocumentRequestSeeder must not run in production.',
        );

        $institutionId = (int) DB::table('institutions')->where('code', 'academy_1')->value('id');
        $instSemId = (int) DB::table('institution_semesters')
            ->where('institution_id', $institutionId)->where('status', 'open')->value('id');
        $adminId = (int) DB::table('administrative_accounts')->where('username', 'admin@gcv.demo')->value('id');
        $secretaryId = (int) DB::table('staff_accounts')->where('username', 'secretary@gcv.demo')->value('id');
        $principalId = (int) DB::table('staff_accounts')->where('username', 'principal@gcv.demo')->value('id');

        $guardianAccountId = (int) DB::table('guardian_accounts')
            ->where('login_identifier', 'guardian@gcv.demo')->value('id');
        $guardianProfileId = (int) DB::table('guardian_profiles')
            ->where('guardian_account_id', $guardianAccountId)->value('id');
        $studentProfileId = (int) DB::table('guardian_student_relationships')
            ->where('guardian_profile_id', $guardianProfileId)->value('student_profile_id');
        $enrollmentId = (int) DB::table('student_enrollments')
            ->where('student_profile_id', $studentProfileId)
            ->where('institution_semester_id', $instSemId)->value('id');

        if ($institutionId === 0 || $enrollmentId === 0 || $adminId === 0) {
            $this->command?->warn('DemoDocumentRequestSeeder: demo anchors missing. Skipping.');

            return;
        }

        // ── 1. Bilingual template versions for all 7 document types ──────
        $this->seedTemplateVersions($adminId);

        // ── 2. Requests + issued documents ────────────────────────────────
        if (DB::table('student_document_requests')->where('purpose_notes', 'like', self::MARKER.'%')->exists()) {
            $this->command?->info('DemoDocumentRequestSeeder: demo requests already seeded. Skipping.');

            return;
        }

        /** @var DocumentRequestService $svc */
        $svc = app(DocumentRequestService::class);

        $create = function (string $typeCode, string $locale, string $note) use (
            $svc, $enrollmentId, $studentProfileId, $institutionId, $instSemId, $guardianAccountId
        ): StudentDocumentRequest {
            $allowed = json_decode((string) DB::table('document_type_catalogue')
                ->where('code', $typeCode)->value('allowed_requesters'), true) ?? [];

            [$portal, $actorType, $actorId] = in_array('guardian', $allowed, true)
                ? ['guardian', 'guardian', $guardianAccountId]
                : ['staff', 'staff', (int) DB::table('staff_accounts')->where('username', 'secretary@gcv.demo')->value('id')];

            return $svc->createAndSubmit([
                'enrollment_id' => $enrollmentId,
                'student_profile_id' => $studentProfileId,
                'institution_id' => $institutionId,
                'institution_semester_id' => $instSemId,
                'actor_type' => $actorType,
                'actor_account_id' => $actorId,
                'portal' => $portal,
                'document_type_code' => $typeCode,
                'locale' => $locale,
                'purpose_notes' => self::MARKER.' — '.$note,
            ]);
        };

        $issue = function (StudentDocumentRequest $r) use ($svc, $secretaryId, $principalId): void {
            $svc->startCompletenessCheck($r->fresh(), $secretaryId);
            $svc->markCompletenessResult($r->fresh(), [], $secretaryId);
            $svc->forwardForApproval($r->fresh(), $secretaryId);
            $svc->approve($r->fresh(), $principalId);
            // Run the generation synchronously so the PDF exists after seeding.
            // (The queued copy is idempotent and becomes a no-op.)
            app()->call([new GenerateDocumentJob($r->id, $principalId), 'handle']);
        };

        // pending_completeness ("in review")
        $rReview = $create('student_information_summary', 'ar', 'pending completeness review');
        $svc->startCompletenessCheck($rReview->fresh(), $secretaryId);

        // approved (not yet generated)
        $rApproved = $create('semester_attendance_report', 'ar', 'approved awaiting generation');
        $svc->startCompletenessCheck($rApproved->fresh(), $secretaryId);
        $svc->markCompletenessResult($rApproved->fresh(), [], $secretaryId);
        $svc->forwardForApproval($rApproved->fresh(), $secretaryId);
        DB::table('student_document_requests')->where('id', $rApproved->id)->update([
            'status' => StudentDocumentRequest::STATUS_APPROVED,
            'approved_by_account_id' => $principalId,
            'approved_at' => now(),
            'updated_at' => now(),
        ]);

        // issued — proof_of_enrolment in Arabic
        $rIssuedAr = $create('proof_of_enrolment', 'ar', 'issued proof of enrolment (Arabic)');
        $issue($rIssuedAr);

        // issued — proof_of_enrolment in English (swap active template version)
        $rIssuedEn = $create('proof_of_enrolment', 'en', 'issued proof of enrolment (English)');
        $this->withEnglishActiveVersion('proof_of_enrolment', $adminId, fn () => $issue($rIssuedEn));

        // issued — semester_grade_report (Arabic) → then superseded by a reissue
        $rGradeA = $create('semester_grade_report', 'ar', 'issued grade report (superseded by reissue)');
        $issue($rGradeA);

        $docA = DB::table('issued_documents')->where('request_id', $rGradeA->id)->first();

        // Reissue chain: cancel doc A, issue doc B for the fresh request, link supersession.
        $rGradeB = $create('semester_grade_report', 'ar', 'reissued grade report (current)');
        $issue($rGradeB);
        $docBId = (int) DB::table('issued_documents')->where('request_id', $rGradeB->id)->value('id');

        if ($docA !== null && $docBId > 0) {
            DB::table('issued_documents')->where('id', $docA->id)->update([
                'cancelled_at' => now(),
                'cancellation_reason' => 'superseded_by_reissue',
                'supersedes_id' => $docBId,
                'updated_at' => now(),
            ]);
        }

        // generation_failed (recoverable state)
        $rFailed = $create('transfer_document', 'ar', 'generation failed (recoverable)');
        $svc->startCompletenessCheck($rFailed->fresh(), $secretaryId);
        $svc->markCompletenessResult($rFailed->fresh(), [], $secretaryId);
        $svc->forwardForApproval($rFailed->fresh(), $secretaryId);
        DB::table('student_document_requests')->where('id', $rFailed->id)->update([
            'status' => StudentDocumentRequest::STATUS_GENERATION_FAILED,
            'approved_by_account_id' => $principalId,
            'approved_at' => now(),
            'completed_at' => now(),
            'updated_at' => now(),
        ]);

        // cancelled
        $rCancelled = $create('school_acceptance_letter', 'ar', 'cancelled request');
        $svc->cancel($rCancelled->fresh(), 'Requested in error — guardian no longer needs this document.');

        // submitted (fresh, untouched)
        $create('student_information_summary', 'ar', 'submitted awaiting review');

        // completeness_passed
        $rPassed = $create('semester_attendance_report', 'ar', 'completeness check passed');
        $svc->startCompletenessCheck($rPassed->fresh(), $secretaryId);
        $svc->markCompletenessResult($rPassed->fresh(), [], $secretaryId);

        // completeness_failed
        $rIncomplete = $create('transfer_document', 'ar', 'completeness check failed');
        $svc->startCompletenessCheck($rIncomplete->fresh(), $secretaryId);
        $svc->markCompletenessResult($rIncomplete->fresh(), ['Guardian ID copy missing', 'Transfer destination not specified'], $secretaryId);

        // awaiting_approval
        $rAwaiting = $create('school_acceptance_letter', 'ar', 'awaiting principal approval');
        $svc->startCompletenessCheck($rAwaiting->fresh(), $secretaryId);
        $svc->markCompletenessResult($rAwaiting->fresh(), [], $secretaryId);
        $svc->forwardForApproval($rAwaiting->fresh(), $secretaryId);

        // pending_clarification
        $rClarify = $create('proof_of_enrolment', 'ar', 'clarification requested from guardian');
        $svc->requestClarification($rClarify->fresh(), 'Please confirm the purpose of this certificate (embassy or employer).', $secretaryId);

        // rejected
        $rRejected = $create('semester_grade_report', 'ar', 'rejected by principal');
        $svc->startCompletenessCheck($rRejected->fresh(), $secretaryId);
        $svc->markCompletenessResult($rRejected->fresh(), [], $secretaryId);
        $svc->forwardForApproval($rRejected->fresh(), $secretaryId);
        $svc->reject($rRejected->fresh(), 'Results for this semester are not yet published.', $principalId);

        // draft — the portal has no persistent-draft flow, so snapshot the
        // pre-submission state directly (same approach as the corrections seeder).
        $rDraft = $create('student_information_summary', 'ar', 'draft not yet submitted');
        DB::table('student_document_requests')->where('id', $rDraft->id)->update([
            'status' => StudentDocumentRequest::STATUS_DRAFT,
            'submitted_at' => null,
            'updated_at' => now(),
        ]);

        // generating — transient state owned by GenerateDocumentJob; snapshot it
        // directly so the demo dataset shows the in-flight state.
        $rGenerating = $create('semester_attendance_report', 'ar', 'generation in progress');
        $svc->startCompletenessCheck($rGenerating->fresh(), $secretaryId);
        $svc->markCompletenessResult($rGenerating->fresh(), [], $secretaryId);
        $svc->forwardForApproval($rGenerating->fresh(), $secretaryId);
        DB::table('student_document_requests')->where('id', $rGenerating->id)->update([
            'status' => StudentDocumentRequest::STATUS_GENERATING,
            'approved_by_account_id' => $principalId,
            'approved_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command?->info('DemoDocumentRequestSeeder: seeded requests across all 13 statuses, issued PDFs, and reissue chain.');
    }

    /**
     * Ensure every document type template has an active Arabic version and an
     * English version (kept as the most recent non-active version).
     */
    private function seedTemplateVersions(int $adminId): void
    {
        /** @var DocumentTemplateVersionService $versions */
        $versions = app(DocumentTemplateVersionService::class);

        $templates = DocumentTemplate::query()->get();

        foreach ($templates as $template) {
            if ($template->versions()->exists()) {
                continue; // already seeded
            }

            $labelAr = (string) DB::table('document_type_catalogue')
                ->where('code', $template->document_type_code)->value('label_ar');
            $labelEn = (string) DB::table('document_type_catalogue')
                ->where('code', $template->document_type_code)->value('label_en');

            // English version first (archived by the Arabic activation is avoided
            // by activating only the Arabic version; English stays as a draft
            // version row available for locale swaps).
            $en = $versions->createDraft(
                template: $template,
                locale: 'en',
                body: $this->englishBody($labelEn),
                headerConfig: ['html' => '<div style="text-align:center;font-weight:bold;">GCV — Gaza Community Volunteers</div>'],
                footerConfig: ['html' => '<div style="text-align:center;font-size:10px;">Verify this document at the public verification page.</div>'],
                creatorAccountId: $adminId,
            );

            $ar = $versions->createDraft(
                template: $template,
                locale: 'ar',
                body: $this->arabicBody($labelAr),
                headerConfig: ['html' => '<div style="text-align:center;font-weight:bold;">مجتمع غزة التطوعي — GCV</div>'],
                footerConfig: ['html' => '<div style="text-align:center;font-size:10px;">يمكن التحقق من هذه الوثيقة عبر صفحة التحقق العامة</div>'],
                creatorAccountId: $adminId,
            );

            $versions->activate($ar, $adminId);

            DB::table('document_templates')->where('id', $template->id)->update([
                'ar_available' => true,
                'en_available' => true,
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Issue an English-locale document through the real activation workflow:
     * activate the English draft (archives the Arabic version), generate the
     * document, then create and activate a fresh Arabic draft so the template
     * ends with Arabic active. Every transition goes through
     * DocumentTemplateVersionService::activate, preserving the invariant that
     * issuance only ever uses an approved active version.
     */
    private function withEnglishActiveVersion(string $typeCode, int $adminId, callable $callback): void
    {
        /** @var DocumentTemplateVersionService $versions */
        $versions = app(DocumentTemplateVersionService::class);

        $templateRow = DB::table('document_templates')
            ->where('document_type_code', $typeCode)->whereNull('institution_id')->first()
            ?? DB::table('document_templates')->where('document_type_code', $typeCode)->first();

        $template = DocumentTemplate::findOrFail($templateRow->id);

        $enDraft = $template->versions()
            ->where('locale', 'en')->where('status', 'draft')
            ->orderByDesc('version_number')->first();

        $arActive = $template->versions()->where('status', 'active')->first();

        if ($enDraft === null || $arActive === null) {
            $this->command?->warn("DemoDocumentRequestSeeder: missing en draft or ar active version for {$typeCode}; skipping English issuance.");

            return;
        }

        $versions->activate($enDraft, $adminId);

        $callback();

        // Restore Arabic as the active version with a fresh draft of the same content.
        $arDraft = $versions->createDraft(
            template: $template->fresh(),
            locale: 'ar',
            body: $arActive->body,
            headerConfig: $arActive->header_config,
            footerConfig: $arActive->footer_config,
            creatorAccountId: $adminId,
        );
        $versions->activate($arDraft, $adminId);
    }

    private function arabicBody(string $label): string
    {
        return <<<HTML
<div style="direction:rtl;text-align:right;font-size:14px;">
  <h1 style="text-align:center;">{$label}</h1>
  <p>تشهد إدارة {{ institution.name_ar }} بأن الطالب/ة <strong>{{ student.full_name_ar }}</strong>
  (رقم الطالب {{ student.student_code }})، المولود/ة بتاريخ {{ student.birth_date }}،
  مسجل/ة في {{ student.class_group_name }} للعام الدراسي {{ academic_year.name }} — {{ semester.name }}.</p>
  <p>ولي الأمر: {{ guardian.full_name_ar }}</p>
  <p>صدرت هذه الوثيقة بتاريخ {{ document.issued_at }} وتحمل الرقم {{ document.number }}.</p>
</div>
HTML;
    }

    private function englishBody(string $label): string
    {
        return <<<HTML
<div style="direction:ltr;text-align:left;font-size:14px;">
  <h1 style="text-align:center;">{$label}</h1>
  <p>The administration of {{ institution.name_en }} certifies that the student
  <strong>{{ student.full_name_en }}</strong> (student code {{ student.student_code }}),
  born on {{ student.birth_date }}, is enrolled in {{ student.class_group_name }}
  for {{ academic_year.name }} — {{ semester.name }}.</p>
  <p>Guardian: {{ guardian.full_name_en }}</p>
  <p>Issued on {{ document.issued_at }} under number {{ document.number }}.</p>
</div>
HTML;
    }
}

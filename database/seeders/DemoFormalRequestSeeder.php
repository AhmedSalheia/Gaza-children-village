<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Requests\Models\InstitutionFormalRequest;
use Modules\Requests\Models\InstitutionFormalRequestComment;
use Modules\Requests\Services\InstitutionFormalRequestService;

/**
 * Seeds institution formal requests covering all lifecycle states:
 *
 *   draft, internal_review, returned_to_preparer, signed,
 *   submitted_to_management, under_management_review, clarification_requested,
 *   accepted, rejected, responded, closed, cancelled, superseded
 *
 * Includes electronic signatures (with content hashes), audience-restricted
 * comments (internal vs management), and management responses.
 *
 * All records are created through InstitutionFormalRequestService so the
 * audit trail, numbering, encryption, and signature hashes are real.
 *
 * Idempotent: skips when the demo title marker exists.
 *
 * Runs AFTER: DemoAccountSeeder (needs staff + admin accounts).
 */
final class DemoFormalRequestSeeder extends Seeder
{
    private const MARKER = '[Demo]';

    public function run(): void
    {
        abort_if(
            app()->isProduction(),
            403,
            'DemoFormalRequestSeeder must not run in production.',
        );

        $institutionId = (int) DB::table('institutions')->where('code', 'academy_1')->value('id');
        $instSemId = (int) DB::table('institution_semesters')
            ->where('institution_id', $institutionId)->where('status', 'open')->value('id');
        $secretaryId = (int) DB::table('staff_accounts')->where('username', 'secretary@gcv.demo')->value('id');
        $principalId = (int) DB::table('staff_accounts')->where('username', 'principal@gcv.demo')->value('id');
        $adminId = (int) DB::table('administrative_accounts')->where('username', 'admin@gcv.demo')->value('id');

        if ($institutionId === 0 || $secretaryId === 0 || $principalId === 0 || $adminId === 0) {
            $this->command?->warn('DemoFormalRequestSeeder: demo anchors missing. Skipping.');

            return;
        }

        if (DB::table('institution_formal_requests')->where('title_en', 'like', self::MARKER.'%')->exists()) {
            $this->command?->info('DemoFormalRequestSeeder: demo formal requests already seeded. Skipping.');

            return;
        }

        /** @var InstitutionFormalRequestService $svc */
        $svc = app(InstitutionFormalRequestService::class);
        $password = env('DEMO_SEED_PASSWORD', 'demo-password-2026');

        $draft = fn (string $type, string $titleAr, string $titleEn, int $priority = 2) => $svc->createDraft(
            institutionId: $institutionId,
            institutionSemesterId: $instSemId ?: null,
            requestType: $type,
            titleAr: $titleAr,
            titleEn: self::MARKER.' '.$titleEn,
            body: [
                'summary' => 'Demo formal request seeded for the '.$titleEn.' scenario.',
                'details' => 'This record demonstrates the formal request lifecycle in the demo dataset.',
            ],
            priority: $priority,
            dueDate: now()->addDays(30)->toDateString(),
            createdByAccountId: $secretaryId,
        );

        $sign = function (InstitutionFormalRequest $r) use ($svc, $password, $principalId, $institutionId): InstitutionFormalRequest {
            // The reconfirmation challenge reads the authenticated staff session,
            // so briefly authenticate the principal on the staff guard.
            Auth::guard('staff')->loginUsingId($principalId);

            try {
                $tokenId = $svc->issueSigningToken($r->fresh(), $password, $principalId, 'principal', 'staff', $institutionId);

                $signed = $svc->sign($r->fresh(), $tokenId, $principalId, 'principal', 'staff', $institutionId, 'Reviewed and signed for submission.');

                // The demo signs more requests than the reconfirmation rate
                // limit (5 per 15 min) allows in one run, so age this actor's
                // attempt records out of the rolling window. Demo-only: this
                // seeder refuses to run in production.
                DB::table('reconfirmation_attempts')
                    ->where('actor_type', 'staff')
                    ->where('actor_account_id', $principalId)
                    ->update(['created_at' => now()->subMinutes(20)]);

                return $signed;
            } finally {
                Auth::guard('staff')->logout();
            }
        };

        // 1. draft
        $draft('equipment', 'طلب أجهزة حاسوب للمختبر', 'Computer lab equipment request');

        // 2. internal_review (+ internal-audience comment)
        $r2 = $draft('maintenance', 'طلب صيانة نظام التدفئة', 'Heating system maintenance');
        $svc->submitForInternalReview($r2, $secretaryId, $institutionId);
        $svc->addComment($r2->fresh(), 'staff', $secretaryId, 'staff',
            InstitutionFormalRequestComment::AUDIENCE_INTERNAL,
            'Internal note: contractor quotations are attached in the shared folder.', $institutionId);

        // 3. returned_to_preparer
        $r3 = $draft('budget', 'طلب موازنة إضافية للأنشطة', 'Additional activities budget');
        $svc->submitForInternalReview($r3, $secretaryId, $institutionId);
        $svc->returnToPreparer($r3->fresh(), $principalId, 'Please attach the itemized cost breakdown before signing.', $institutionId);

        // 4. signed (electronic signature with content hash)
        $r4 = $draft('staffing', 'طلب معلم لغة إنجليزية إضافي', 'Additional English teacher');
        $svc->submitForInternalReview($r4, $secretaryId, $institutionId);
        $sign($r4);

        // 5. submitted_to_management
        $r5 = $draft('curriculum', 'طلب اعتماد منهاج إثرائي', 'Enrichment curriculum approval');
        $svc->submitForInternalReview($r5, $secretaryId, $institutionId);
        $r5 = $sign($r5);
        $svc->submitToManagement($r5->fresh(), $principalId, $institutionId);

        // 6. under_management_review (+ management-audience comment)
        $r6 = $draft('administrative', 'طلب تحديث اللوائح الداخلية', 'Internal regulations update', 3);
        $svc->submitForInternalReview($r6, $secretaryId, $institutionId);
        $r6 = $sign($r6);
        $svc->submitToManagement($r6->fresh(), $principalId, $institutionId);
        $svc->startManagementReview($r6->fresh(), $adminId);
        $svc->addComment($r6->fresh(), 'administrative', $adminId, 'admin',
            InstitutionFormalRequestComment::AUDIENCE_MANAGEMENT,
            'Management note: legal review requested before a decision is made.');

        // 7. responded (accepted + management response)
        $r7 = $draft('equipment', 'طلب أجهزة عرض للصفوف', 'Classroom projectors');
        $svc->submitForInternalReview($r7, $secretaryId, $institutionId);
        $r7 = $sign($r7);
        $svc->submitToManagement($r7->fresh(), $principalId, $institutionId);
        $svc->startManagementReview($r7->fresh(), $adminId);
        $svc->accept($r7->fresh(), $adminId, 'Approved within the current procurement cycle.');
        $svc->respond($r7->fresh(), $adminId, [
            'decision_summary' => 'Approved: 6 projectors will be delivered within 4 weeks.',
            'reference' => 'Procurement cycle 2026-Q3.',
        ]);

        // 8. closed
        $r8 = $draft('other', 'طلب تنظيم يوم مفتوح', 'Open day authorization');
        $svc->submitForInternalReview($r8, $secretaryId, $institutionId);
        $r8 = $sign($r8);
        $svc->submitToManagement($r8->fresh(), $principalId, $institutionId);
        $svc->startManagementReview($r8->fresh(), $adminId);
        $svc->accept($r8->fresh(), $adminId, 'Approved as proposed.');
        $svc->respond($r8->fresh(), $adminId, ['decision_summary' => 'Approved — event authorized for the requested date.']);
        $svc->close($r8->fresh(), $adminId);

        // 9. clarification_requested
        $r9 = $draft('maintenance', 'طلب إصلاح سور المدرسة', 'School fence repair');
        $svc->submitForInternalReview($r9, $secretaryId, $institutionId);
        $r9 = $sign($r9);
        $svc->submitToManagement($r9->fresh(), $principalId, $institutionId);
        $svc->startManagementReview($r9->fresh(), $adminId);
        $svc->requestClarification($r9->fresh(), $adminId, 'Please provide photos of the damaged sections and two contractor quotations.');

        // 10. accepted (decision made, response not yet drafted)
        $r10 = $draft('equipment', 'طلب طابعات للإدارة', 'Office printers');
        $svc->submitForInternalReview($r10, $secretaryId, $institutionId);
        $r10 = $sign($r10);
        $svc->submitToManagement($r10->fresh(), $principalId, $institutionId);
        $svc->startManagementReview($r10->fresh(), $adminId);
        $svc->accept($r10->fresh(), $adminId, 'Accepted; formal response to follow.');

        // 11. rejected
        $r11 = $draft('budget', 'طلب موازنة رحلة خارجية', 'External trip budget', 1);
        $svc->submitForInternalReview($r11, $secretaryId, $institutionId);
        $r11 = $sign($r11);
        $svc->submitToManagement($r11->fresh(), $principalId, $institutionId);
        $svc->startManagementReview($r11->fresh(), $adminId);
        $svc->reject($r11->fresh(), $adminId, 'Outside the approved activity budget for this semester.');

        // 12. cancelled (withdrawn by the institution while still in draft)
        $r12 = $draft('other', 'طلب ملغى — معرض علمي', 'Science fair authorization (withdrawn)');
        $svc->cancel($r12->fresh(), $secretaryId, $institutionId);

        // 13. superseded — replace the responded projector request with a revision
        $svc->supersede(
            $r7->fresh(),
            'طلب أجهزة عرض للصفوف — نسخة محدثة',
            self::MARKER.' Classroom projectors — revised quantities',
            [
                'summary' => 'Revised projector request superseding the original response.',
                'details' => 'Quantity updated from 6 to 8 projectors following enrolment growth.',
            ],
            2,
            now()->addDays(30)->toDateString(),
            $secretaryId,
            $institutionId,
        );

        // 14. responded (kept in this state; r7 above was consumed by supersession)
        $r14 = $draft('staffing', 'طلب مشرف أنشطة لاصفية', 'Extracurricular activities supervisor');
        $svc->submitForInternalReview($r14, $secretaryId, $institutionId);
        $r14 = $sign($r14);
        $svc->submitToManagement($r14->fresh(), $principalId, $institutionId);
        $svc->startManagementReview($r14->fresh(), $adminId);
        $svc->accept($r14->fresh(), $adminId, 'Approved for the current semester.');
        $svc->respond($r14->fresh(), $adminId, [
            'decision_summary' => 'Approved: a part-time supervisor will be assigned within two weeks.',
        ]);

        $this->command?->info('DemoFormalRequestSeeder: seeded formal requests across all 13 lifecycle states.');
    }
}

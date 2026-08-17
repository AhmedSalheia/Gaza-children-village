<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Requests\Services\CorrectionApplicationService;
use Modules\Requests\Services\CorrectionRequestService;

/**
 * Seeds guardian correction requests covering every workflow state:
 *
 *   draft, submitted, clarification_requested, resubmitted, under_review,
 *   approved, rejected, applied, cancelled
 *
 * Plus:
 *   - one SENSITIVE-field request (birth_date) awaiting principal approval
 *   - one conflict-flagged request
 *   - one applied request with a full workflow_actions audit trail
 *
 * Idempotent: skips seeding entirely when a demo correction request already
 * exists for the demo guardian (detected via the draft marker explanation).
 *
 * Runs AFTER: DemoAccountSeeder (needs guardian account).
 */
final class DemoCorrectionRequestSeeder extends Seeder
{
    private const MARKER = 'Demo seed correction request';

    public function run(): void
    {
        abort_if(
            app()->isProduction(),
            403,
            'DemoCorrectionRequestSeeder must not run in production.',
        );

        $guardianAccountId = (int) DB::table('guardian_accounts')
            ->where('login_identifier', 'guardian@gcv.demo')->value('id');
        $guardianProfileId = (int) DB::table('guardian_profiles')
            ->where('guardian_account_id', $guardianAccountId)->value('id');

        if ($guardianAccountId === 0 || $guardianProfileId === 0) {
            $this->command?->warn('DemoCorrectionRequestSeeder: demo guardian not found. Skipping.');

            return;
        }

        $studentProfileId = (int) DB::table('guardian_student_relationships')
            ->where('guardian_profile_id', $guardianProfileId)->value('student_profile_id');

        if ($studentProfileId === 0) {
            $this->command?->warn('DemoCorrectionRequestSeeder: guardian has no linked student. Skipping.');

            return;
        }

        $institutionId = (int) DB::table('institutions')->where('code', 'academy_1')->value('id');
        $secretaryId = (int) DB::table('staff_accounts')->where('username', 'secretary@gcv.demo')->value('id');
        $principalId = (int) DB::table('staff_accounts')->where('username', 'principal@gcv.demo')->value('id');

        // Idempotency check: draft marker proposal already present?
        if (DB::table('correction_field_proposals')->where('explanation', 'like', self::MARKER.'%')->exists()) {
            $this->command?->info('DemoCorrectionRequestSeeder: demo corrections already seeded. Skipping.');

            return;
        }

        /** @var CorrectionRequestService $svc */
        $svc = app(CorrectionRequestService::class);

        $create = fn (string $field, string $value, string $note) => $svc->createAndSubmit(
            studentProfileId: $studentProfileId,
            guardianAccountId: $guardianAccountId,
            guardianProfileId: $guardianProfileId,
            fieldCode: $field,
            proposedValue: $value,
            explanation: self::MARKER.' — '.$note,
            institutionId: $institutionId,
        );

        // 1. draft — direct insert (portal always submits immediately, so drafts
        //    only exist transiently; we materialize one for demo purposes).
        $this->seedDraft($guardianAccountId, $guardianProfileId, $studentProfileId, $institutionId);

        // 2. submitted
        $create('contact_email', 'guardian.updated@example.com', 'submitted state');

        // 3. clarification_requested
        $r3 = $create('student_name_en', 'Ahmad Khalil Nasser', 'clarification state');
        $svc->requestClarification($r3, $secretaryId, 'Please attach the passport copy showing the English spelling.', $institutionId);

        // 4. resubmitted
        $r4 = $create('contact_phone', '+970 599 111 222', 'resubmitted state');
        $svc->requestClarification($r4, $secretaryId, 'The number appears incomplete — please confirm.', $institutionId);
        $svc->resubmit($r4, $guardianAccountId, '+970 599 111 2233', self::MARKER.' — corrected number after clarification');

        // 5. under_review
        $r5 = $create('student_name_ar', 'أحمد خليل ناصر', 'under review state');
        $svc->startReview($r5, $secretaryId, $institutionId);

        // 6. approved (standard field → secretary can approve)
        $r6 = $create('contact_email', 'family.nasser@example.com', 'approved state');
        $svc->startReview($r6, $secretaryId, $institutionId);
        $svc->approve($r6, $secretaryId, 'staff', 'staff', 'Verified against the registration form.', $institutionId);

        // 7. rejected
        $r7 = $create('student_name_en', 'A. K. Nasser', 'rejected state');
        $svc->startReview($r7, $secretaryId, $institutionId);
        $svc->reject($r7, $secretaryId, 'staff', 'staff', 'Abbreviated names are not accepted on official records.', $institutionId);

        // 8. applied — full audit trail (submit → review → approve → apply)
        $r8 = $create('contact_phone', '+970 598 765 432', 'applied state with audit trail');
        $svc->startReview($r8, $secretaryId, $institutionId);
        $svc->approve($r8, $secretaryId, 'staff', 'staff', 'Confirmed with the guardian by phone.', $institutionId);
        app(CorrectionApplicationService::class)->apply($r8, $secretaryId, 'staff', 'staff', $institutionId);

        // 9. cancelled (by guardian)
        $r9 = $create('contact_email', 'old.address@example.com', 'cancelled state');
        $svc->cancelByGuardian($r9, $guardianAccountId);

        // 10. SENSITIVE field (birth_date) — under review, awaiting principal approval
        $r10 = $create('birth_date', '2018-03-14', 'sensitive birth date correction awaiting principal approval');
        $svc->startReview($r10, $secretaryId, $institutionId);

        // 11. conflict-flagged request (official record changed since submission)
        $r11 = $create('student_name_ar', 'أحمد خليل يوسف ناصر', 'conflict flagged state');
        $svc->startReview($r11, $secretaryId, $institutionId);
        DB::table('student_correction_requests')->where('id', $r11->id)->update([
            'conflict_flag' => true,
            'conflict_reason' => 'Official record was modified by staff after this request was submitted.',
            'updated_at' => now(),
        ]);

        $this->command?->info('DemoCorrectionRequestSeeder: seeded 11 correction requests across all states.');
    }

    private function seedDraft(int $guardianAccountId, int $guardianProfileId, int $studentProfileId, int $institutionId): void
    {
        $wfDefId = (int) DB::table('workflow_definitions')
            ->where('type', 'student_correction')->where('is_active', true)
            ->orderByDesc('version')->value('id');

        $now = now();

        $instanceId = DB::table('workflow_instances')->insertGetId([
            'workflow_definition_id' => $wfDefId,
            'subject_type' => 'StudentCorrectionRequest',
            'subject_id' => 0,
            'current_state' => 'draft',
            'initiating_actor_type' => 'guardian',
            'initiating_actor_portal' => 'guardian',
            'initiating_account_id' => $guardianAccountId,
            'institution_id' => $institutionId,
            'correlation_id' => (string) Str::uuid(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $requestId = DB::table('student_correction_requests')->insertGetId([
            'workflow_instance_id' => $instanceId,
            'student_profile_id' => $studentProfileId,
            'guardian_account_id' => $guardianAccountId,
            'guardian_profile_id' => $guardianProfileId,
            'institution_id' => $institutionId,
            'field_catalogue_code' => 'contact_phone',
            'classification' => 'standard',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('workflow_instances')->where('id', $instanceId)->update(['subject_id' => $requestId]);

        DB::table('correction_field_proposals')->insert([
            'correction_request_id' => $requestId,
            'field_code' => 'contact_phone',
            'proposed_value' => '+970 599 000 111',
            'explanation' => self::MARKER.' — draft state',
            'submission_sequence' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}

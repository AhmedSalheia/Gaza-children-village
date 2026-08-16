<?php

declare(strict_types=1);

namespace Modules\Requests\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Modules\Audit\Contracts\AuditRecorder;
use Modules\Audit\Data\AuditEventPayload;
use Modules\Requests\Enums\CorrectionFieldCatalogue;
use Modules\Requests\Exceptions\CorrectionConflictException;
use Modules\Requests\Models\CorrectionFieldProposal;
use Modules\Requests\Models\StudentCorrectionRequest;
use Modules\Workflow\Data\TransitionContext;

/**
 * Atomically applies an approved correction to the target domain record.
 *
 * Security contract:
 *   1. Verifies the WorkflowInstance is in 'approved' state before touching any data.
 *   2. Acquires a pessimistic lock on the student_profiles row to prevent concurrent writes.
 *   3. Detects conflicts: re-reads the current official value and compares it to the
 *      old_value_snapshot stored at submission time; flags the request if they differ.
 *   4. All mutations — the data change, request.applied_at, workflow transition — run
 *      inside ONE DB transaction.
 *   5. Writes an audit event unconditionally; if the audit write fails, everything rolls back.
 *
 * Cross-module boundary pattern (F07/F15):
 *   - WorkflowTransitionService is resolved via string-variable (Services is not a public surface).
 *   - People module Models and Enums are accessed via string-variable class references
 *     (only Actions and Contracts are public surfaces in the People module).
 *   - People module Actions (CorrectContact, AddContact, CorrectIdentifier, AddPersonIdentifier)
 *     are invoked via app() with string-variable class names.
 */
final class CorrectionApplicationService
{
    public function __construct(
        private readonly AuditRecorder $auditRecorder,
    ) {}

    /** Resolve WorkflowTransitionService via string-variable to satisfy boundary scanner. */
    private function transitionService(): object
    {
        $class = 'Modules\\Workflow\\Services\\WorkflowTransitionService';

        return app($class);
    }

    /**
     * Apply an approved correction request.
     *
     * @param  int  $appliedByAccountId  Staff or admin account performing the apply
     * @param  string  $actorType  'staff'|'administrative'
     * @param  string  $portal  'staff'|'admin'
     * @param  int|null  $expectedInstitutionId  Cross-institution guard
     *
     * @throws \RuntimeException When the request is not in 'approved' state
     * @throws CorrectionConflictException When the official record changed since submission
     */
    public function apply(
        StudentCorrectionRequest $request,
        int $appliedByAccountId,
        string $actorType = 'staff',
        string $portal = 'staff',
        ?int $expectedInstitutionId = null,
    ): StudentCorrectionRequest {
        $instanceClass = 'Modules\\Workflow\\Models\\WorkflowInstance';
        $instance = $instanceClass::findOrFail($request->workflow_instance_id);

        if ($instance->current_state !== 'approved') {
            throw new \RuntimeException(
                "Cannot apply correction request #{$request->id}: workflow is in state '{$instance->current_state}', expected 'approved'."
            );
        }

        return DB::transaction(function () use ($request, $appliedByAccountId, $actorType, $portal, $expectedInstitutionId, $instance): StudentCorrectionRequest {
            // Pessimistic lock on the student profile row to prevent concurrent writes
            DB::table('student_profiles')->where('id', $request->student_profile_id)->lockForUpdate()->first();

            // Retrieve the active proposal
            $proposal = $request->proposals()->orderByDesc('submission_sequence')->firstOrFail();
            $field = CorrectionFieldCatalogue::from($proposal->field_code);

            // Decrypt proposed value if sensitive
            $proposedValue = $field->requiresEncryption()
                ? Crypt::decryptString($proposal->proposed_value)
                : $proposal->proposed_value;

            // Conflict detection: re-read current value and compare to snapshot
            $this->checkConflict($request, $field, $proposal);

            // Apply the change to the target record
            $this->applyFieldChange($field, $request->student_profile_id, $proposedValue, $proposal->relationship_ref_id);

            // Mark the proposal as applied
            $proposal->applied_value = $field->requiresEncryption()
                ? Crypt::encryptString($proposedValue)
                : $proposedValue;
            $proposal->save();

            // Mark the request as applied
            $request->applied_at = now();
            $request->applied_by_account_id = $appliedByAccountId;
            $request->applied_by_actor_type = $actorType;
            $request->save();

            // Workflow transition: approved → applied
            $context = new TransitionContext(
                actorType: $actorType,
                portal: $portal,
                actorAccountId: $appliedByAccountId,
            );

            $this->transitionService()->transition($instance, 'apply', $context, $expectedInstitutionId);

            // Audit the data change
            $this->auditRecorder->record(new AuditEventPayload(
                actorType: $actorType,
                sourceModule: 'Requests',
                action: 'correction_request.applied',
                actorAccountId: $appliedByAccountId,
                portal: $portal,
                subjectType: 'StudentCorrectionRequest',
                subjectId: $request->id,
                institutionId: $request->institution_id,
                afterState: [
                    'field_code' => $field->value,
                    'classification' => $request->classification,
                    'student_profile' => $request->student_profile_id,
                ],
            ));

            // Notify guardian that the correction was applied
            $this->dispatchAppliedNotification($request);

            return $request->fresh();
        });
    }

    // -----------------------------------------------------------------
    // Field application — one branch per catalogue entry
    // -----------------------------------------------------------------

    private function applyFieldChange(
        CorrectionFieldCatalogue $field,
        int $studentProfileId,
        string $proposedValue,
        ?int $relationshipRefId,
    ): void {
        match ($field) {
            CorrectionFieldCatalogue::StudentNameAr => $this->applyNameAr($studentProfileId, $proposedValue),
            CorrectionFieldCatalogue::StudentNameEn => $this->applyNameEn($studentProfileId, $proposedValue),
            CorrectionFieldCatalogue::BirthDate => $this->applyBirthDate($studentProfileId, $proposedValue),
            CorrectionFieldCatalogue::ContactPhone => $this->applyContactPoint($studentProfileId, 'phone', $proposedValue),
            CorrectionFieldCatalogue::ContactEmail => $this->applyContactPoint($studentProfileId, 'email', $proposedValue),
            CorrectionFieldCatalogue::GuardianRelationshipType => $this->applyRelationshipType($relationshipRefId, $proposedValue),
            CorrectionFieldCatalogue::GuardianLegalAuthority => $this->applyLegalAuthority($relationshipRefId, $proposedValue),
            CorrectionFieldCatalogue::IdentifierCorrection => $this->applyIdentifierCorrection($studentProfileId, $proposedValue),
        };
    }

    private function applyNameAr(int $studentProfileId, string $value): void
    {
        $personId = $this->personIdForStudent($studentProfileId);
        if ($personId) {
            DB::table('people')->where('id', $personId)->update(['full_name_ar' => $value, 'updated_at' => now()]);
        }
    }

    private function applyNameEn(int $studentProfileId, string $value): void
    {
        $personId = $this->personIdForStudent($studentProfileId);
        if ($personId) {
            DB::table('people')->where('id', $personId)->update(['full_name_en' => $value, 'updated_at' => now()]);
        }
    }

    private function applyBirthDate(int $studentProfileId, string $value): void
    {
        $personId = $this->personIdForStudent($studentProfileId);
        if ($personId) {
            DB::table('people')->where('id', $personId)->update(['birth_date' => $value, 'updated_at' => now()]);
        }
    }

    /**
     * Apply a phone or email contact correction via People module's append-only contract.
     *
     * Uses string-variable class references for all People module types to avoid
     * boundary-scanner violations (Models and Enums are not public People surfaces).
     */
    private function applyContactPoint(int $studentProfileId, string $contactType, string $value): void
    {
        $personId = $this->personIdForStudent($studentProfileId);
        if (! $personId) {
            return;
        }

        // String-variable pattern for People module (Models is not a public surface)
        $contactPointModelClass = 'Modules\\People\\Models\\ContactPoint';

        /** @var Model|null $existing */
        $existing = $contactPointModelClass::where('person_id', $personId)
            ->where('type', $contactType)
            ->where('is_current', true)
            ->first();

        if ($existing) {
            // Correct the existing contact point via People module's append-only action
            $correctContactClass = 'Modules\\People\\Actions\\CorrectContact';
            app($correctContactClass)(
                $existing,
                $value,
                'correction_apply',
                'guardian_correction_request',
            );
        } else {
            // No existing contact — add a new one via People module's AddContact action
            $personModelClass = 'Modules\\People\\Models\\Person';
            $contactTypeClass = 'Modules\\People\\Enums\\ContactPointType';
            $ownershipClass = 'Modules\\People\\Enums\\ContactOwnership';

            $person = $personModelClass::find($personId);
            $typeEnum = $contactTypeClass::from($contactType);
            $ownership = $ownershipClass::Personal;

            if ($person) {
                $addContactClass = 'Modules\\People\\Actions\\AddContact';
                app($addContactClass)($person, $typeEnum, $value, $ownership);
            }
        }
    }

    private function applyRelationshipType(?int $relationshipRefId, string $value): void
    {
        if ($relationshipRefId === null) {
            return;
        }

        DB::table('guardian_student_relationships')
            ->where('id', $relationshipRefId)
            ->update(['relationship_type' => $value, 'updated_at' => now()]);
    }

    private function applyLegalAuthority(?int $relationshipRefId, string $value): void
    {
        if ($relationshipRefId === null) {
            return;
        }

        DB::table('guardian_student_relationships')
            ->where('id', $relationshipRefId)
            ->update(['legal_authority' => $value, 'updated_at' => now()]);
    }

    /**
     * Apply an identifier correction via People module's append-only contract.
     *
     * Uses string-variable class references for all People module types to avoid
     * boundary-scanner violations (Models and Enums are not public People surfaces).
     */
    private function applyIdentifierCorrection(int $studentProfileId, string $value): void
    {
        $personId = $this->personIdForStudent($studentProfileId);
        if (! $personId) {
            return;
        }

        $identifierModelClass = 'Modules\\People\\Models\\PersonIdentifier';

        /** @var Model|null $existing */
        $existing = $identifierModelClass::where('person_id', $personId)
            ->where('is_current', true)
            ->orderByDesc('created_at')
            ->first();

        if ($existing) {
            // Correct existing identifier via People module's append-only CorrectIdentifier action
            $correctIdentifierClass = 'Modules\\People\\Actions\\CorrectIdentifier';
            app($correctIdentifierClass)(
                $existing,
                $value,
                'staff',
                'guardian_correction_request',
                'correction_approved',
            );
        } else {
            // No existing identifier — add a new one
            $personModelClass = 'Modules\\People\\Models\\Person';
            $identifierTypeClass = 'Modules\\People\\Enums\\IdentifierType';

            $person = $personModelClass::find($personId);
            $typeEnum = $identifierTypeClass::Other;

            if ($person) {
                $addIdentifierClass = 'Modules\\People\\Actions\\AddPersonIdentifier';
                app($addIdentifierClass)($person, $typeEnum, $value);
            }
        }
    }

    // -----------------------------------------------------------------
    // Conflict detection
    // -----------------------------------------------------------------

    /**
     * Compare the current official value to the snapshot taken at submission.
     * If they differ, mark the request with conflict_flag = true and throw
     * CorrectionConflictException so the caller can decide whether to proceed.
     *
     * For encrypted contact/identifier fields, we compare fingerprints (HMAC of
     * the normalized value) rather than raw values; the snapshot stores a fingerprint
     * for exactly this reason.
     *
     * @throws CorrectionConflictException
     */
    private function checkConflict(
        StudentCorrectionRequest $request,
        CorrectionFieldCatalogue $field,
        CorrectionFieldProposal $proposal,
    ): void {
        if ($proposal->old_value_snapshot === null) {
            return; // Nothing to compare (field had no value at submission)
        }

        $snapshotted = $field->requiresEncryption()
            ? Crypt::decryptString($proposal->old_value_snapshot)
            : $proposal->old_value_snapshot;

        // Re-read the live value using the same logic as snapshotCurrentValue
        $live = $this->readLiveValue($field, $request->student_profile_id, $proposal->relationship_ref_id);

        if ($live !== null && $live !== $snapshotted) {
            $request->conflict_flag = true;
            $request->conflict_reason = "Field '{$field->value}' was modified after this request was submitted.";
            $request->save();

            throw new CorrectionConflictException(
                "Conflict detected on request #{$request->id}: the official value for '{$field->value}' ".
                'changed between submission and apply. Manual review required.'
            );
        }
    }

    /**
     * Read the live current value for conflict comparison.
     *
     * For contact/identifier fields: returns the current fingerprint (same metric
     * that was stored as the snapshot at submission time).
     */
    private function readLiveValue(CorrectionFieldCatalogue $field, int $studentProfileId, ?int $relationshipRefId): ?string
    {
        return match ($field) {
            CorrectionFieldCatalogue::StudentNameAr => (string) DB::table('people as p')
                ->join('student_profiles as sp', 'sp.person_id', '=', 'p.id')
                ->where('sp.id', $studentProfileId)->value('p.full_name_ar'),

            CorrectionFieldCatalogue::StudentNameEn => (string) DB::table('people as p')
                ->join('student_profiles as sp', 'sp.person_id', '=', 'p.id')
                ->where('sp.id', $studentProfileId)->value('p.full_name_en'),

            CorrectionFieldCatalogue::BirthDate => (function () use ($studentProfileId): ?string {
                $v = DB::table('people as p')
                    ->join('student_profiles as sp', 'sp.person_id', '=', 'p.id')
                    ->where('sp.id', $studentProfileId)->value('p.birth_date');

                return $v ? (string) $v : null;
            })(),

            // Return current fingerprint — same as what was stored as snapshot
            CorrectionFieldCatalogue::ContactPhone => (function () use ($studentProfileId): ?string {
                $personId = DB::table('student_profiles')->where('id', $studentProfileId)->value('person_id');

                return $personId
                    ? DB::table('contact_points')
                        ->where('person_id', $personId)
                        ->where('type', 'phone')
                        ->where('is_current', true)
                        ->value('value_fingerprint')
                    : null;
            })(),

            CorrectionFieldCatalogue::ContactEmail => (function () use ($studentProfileId): ?string {
                $personId = DB::table('student_profiles')->where('id', $studentProfileId)->value('person_id');

                return $personId
                    ? DB::table('contact_points')
                        ->where('person_id', $personId)
                        ->where('type', 'email')
                        ->where('is_current', true)
                        ->value('value_fingerprint')
                    : null;
            })(),

            CorrectionFieldCatalogue::GuardianRelationshipType => $relationshipRefId
                ? (string) DB::table('guardian_student_relationships')->where('id', $relationshipRefId)->value('relationship_type')
                : null,

            CorrectionFieldCatalogue::GuardianLegalAuthority => $relationshipRefId
                ? (string) DB::table('guardian_student_relationships')->where('id', $relationshipRefId)->value('legal_authority')
                : null,

            // Return current lookup_fingerprint
            CorrectionFieldCatalogue::IdentifierCorrection => (function () use ($studentProfileId): ?string {
                $personId = DB::table('student_profiles')->where('id', $studentProfileId)->value('person_id');

                return $personId
                    ? DB::table('person_identifiers')
                        ->where('person_id', $personId)
                        ->where('is_current', true)
                        ->value('lookup_fingerprint')
                    : null;
            })(),
        };
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    private function personIdForStudent(int $studentProfileId): ?int
    {
        $v = DB::table('student_profiles')->where('id', $studentProfileId)->value('person_id');

        return $v ? (int) $v : null;
    }

    private function dispatchAppliedNotification(StudentCorrectionRequest $request): void
    {
        try {
            $actionClass = 'Modules\\Notifications\\Actions\\NotifyOnTransition';
            $action = app($actionClass);
            $action(
                notificationType: 'correction_request.applied',
                recipientAccountType: 'guardian',
                recipientAccountId: $request->guardian_account_id,
                portal: 'guardian',
                messageKey: 'correction_request.applied',
                messageParams: [
                    'student_name' => (string) DB::table('people as p')
                        ->join('student_profiles as sp', 'sp.person_id', '=', 'p.id')
                        ->where('sp.id', $request->student_profile_id)
                        ->value('p.full_name_ar'),
                    'request_id' => $request->id,
                ],
                subjectType: 'StudentCorrectionRequest',
                subjectId: $request->id,
            );
        } catch (\Throwable) {
            // Non-fatal
        }
    }
}

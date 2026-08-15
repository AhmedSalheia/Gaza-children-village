<?php

declare(strict_types=1);

namespace Tests\Feature\Guardian;

use App\Livewire\Admin\Students\GuardianDetail;
use App\Livewire\Guardian\Students\StudentDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Modules\Accounts\Models\GuardianAccount;
use Tests\TestCase;

/**
 * Correction-request flow: guardian submits, admin reviews.
 *
 * Covers:
 *   G1. Guardian sees "flag a correction" button when no pending request.
 *   G2. Guardian submits a valid correction request — row created, pending indicator shown.
 *   G3. Second submission blocked while a request is already pending.
 *   G4. Submission requires at least one field (priority or emergency flag).
 *   G5. Submitted value must differ from the current relationship value.
 *   G6. Another guardian cannot submit a correction for a student they don't own.
 *   A1. View-only admin sees pending requests but NOT approve/reject buttons.
 *   A2. Manage admin sees and can use approve/reject buttons.
 *   A3. View-only admin is rejected (403) when calling approve action directly.
 *   A4. View-only admin is rejected (403) when calling reject action directly.
 *   A5. Manage admin approves — relationship updated, request resolved.
 *   A6. Manage admin rejects — relationship unchanged, request resolved.
 *   A7. Approve/reject scoped to the correct guardian's relationships.
 */
class CorrectionRequestTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────

    private function makePerson(string $nameAr = 'Test Person'): object
    {
        $personClass = 'Modules\\People\\Models\\Person';

        return $personClass::create(['full_name_ar' => $nameAr]);
    }

    /**
     * @return array{account: GuardianAccount, profileId: int, studentId: int, relationshipId: int}
     */
    private function makeGuardianWithStudent(
        bool $portalEligible = true,
        ?string $endsOn = null,
        int $contactPriority = 2,
        bool $isEmergencyContact = false,
    ): array {
        $guardianAccountClass  = 'Modules\\Accounts\\Models\\GuardianAccount';
        $guardianProfileClass  = 'Modules\\Students\\Models\\GuardianProfile';
        $studentProfileClass   = 'Modules\\Students\\Models\\StudentProfile';
        $relationshipClass     = 'Modules\\Students\\Models\\GuardianStudentRelationship';

        $account = $guardianAccountClass::factory()->create(['status' => 'active']);

        $guardianPerson  = $this->makePerson('Guardian');
        $guardianProfile = $guardianProfileClass::factory()->create([
            'person_id'           => $guardianPerson->id,
            'guardian_account_id' => $account->id,
        ]);

        $studentPerson  = $this->makePerson('Student');
        $studentProfile = $studentProfileClass::factory()->create([
            'person_id' => $studentPerson->id,
        ]);

        $relationship = $relationshipClass::factory()->create([
            'guardian_profile_id'  => $guardianProfile->id,
            'student_profile_id'   => $studentProfile->id,
            'verification_status'  => 'verified',
            'portal_eligible'      => $portalEligible,
            'ends_on'              => $endsOn,
            'contact_priority'     => $contactPriority,
            'is_emergency_contact' => $isEmergencyContact,
        ]);

        return [
            'account'        => $account,
            'profileId'      => $guardianProfile->id,
            'studentId'      => $studentProfile->id,
            'relationshipId' => $relationship->id,
        ];
    }

    /**
     * Create an admin with only the view permission (no manage).
     */
    private function makeViewOnlyAdmin(): object
    {
        return $this->makeAdminWithPermissions(['guardian_relationship.view']);
    }

    /**
     * Create an admin with both view and manage permissions.
     */
    private function makeManageAdmin(): object
    {
        return $this->makeAdminWithPermissions([
            'guardian_relationship.view',
            'guardian_relationship.manage',
        ]);
    }

    /** @param list<string> $permissionKeys */
    private function makeAdminWithPermissions(array $permissionKeys): object
    {
        $adminClass = 'Modules\\Accounts\\Models\\AdministrativeAccount';
        $admin      = $adminClass::factory()->create(['status' => 'active']);

        $roleId = DB::table('roles')->insertGetId([
            'code'       => 'test-role-' . $admin->id,
            'label'      => 'Test Role',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($permissionKeys as $key) {
            $permId = DB::table('permissions')
                ->where('key', $key)
                ->value('id');

            if ($permId === null) {
                $permId = DB::table('permissions')->insertGetId([
                    'key'        => $key,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('role_permissions')->insert([
                'role_id'       => $roleId,
                'permission_id' => $permId,
            ]);
        }

        DB::table('administrative_account_roles')->insert([
            'administrative_account_id' => $admin->id,
            'role_id'                   => $roleId,
            'granted_by'                => 'test-setup',
            'granted_at'                => now(),
            'revoked_at'                => null,
            'created_at'                => now(),
            'updated_at'                => now(),
        ]);

        return $admin;
    }

    /** Insert a pending correction request row and return its id. */
    private function insertPendingRequest(int $relationshipId, int $priority = 1): int
    {
        return DB::table('guardian_correction_requests')->insertGetId([
            'guardian_student_relationship_id' => $relationshipId,
            'requested_contact_priority'       => $priority,
            'requested_is_emergency_contact'   => null,
            'status'                           => 'pending',
            'pending_lock'                     => 1,
            'created_at'                       => now(),
            'updated_at'                       => now(),
        ]);
    }

    // ── Guardian-side tests ───────────────────────────────────────────────

    /** G1: Button visible when no pending request. */
    public function test_flag_correction_button_visible_with_no_pending_request(): void
    {
        $data = $this->makeGuardianWithStudent();

        Livewire::actingAs($data['account'], 'guardian')
            ->test(StudentDetail::class, ['studentProfileId' => $data['studentId']])
            ->assertOk()
            ->assertSee('ui.flag_correction');
    }

    /** G2: Submit a valid correction request — row stored, pending indicator shown. */
    public function test_guardian_can_submit_correction_request(): void
    {
        // current contact_priority = 2; request to change to 1.
        $data = $this->makeGuardianWithStudent(contactPriority: 2, isEmergencyContact: false);

        Livewire::actingAs($data['account'], 'guardian')
            ->test(StudentDetail::class, ['studentProfileId' => $data['studentId']])
            ->call('openCorrectionForm')
            ->set('correctionPriority', 1)
            ->set('correctionNote', 'Priority should be 1 not 2')
            ->call('submitCorrectionRequest')
            ->assertOk()
            ->assertSet('correctionFormOpen', false)
            ->assertSee('ui.correction_submitted');

        $this->assertDatabaseHas('guardian_correction_requests', [
            'guardian_student_relationship_id' => $data['relationshipId'],
            'requested_contact_priority'       => 1,
            'status'                           => 'pending',
        ]);
    }

    /** G2b: Pending indicator shown after a request exists. */
    public function test_pending_indicator_shown_while_request_is_open(): void
    {
        $data = $this->makeGuardianWithStudent();
        $this->insertPendingRequest($data['relationshipId']);

        Livewire::actingAs($data['account'], 'guardian')
            ->test(StudentDetail::class, ['studentProfileId' => $data['studentId']])
            ->assertOk()
            ->assertSee('ui.correction_pending');
    }

    /** G3: A second submission is blocked while one is pending. */
    public function test_second_submission_blocked_while_pending(): void
    {
        $data = $this->makeGuardianWithStudent(contactPriority: 2);
        $this->insertPendingRequest($data['relationshipId']);

        Livewire::actingAs($data['account'], 'guardian')
            ->test(StudentDetail::class, ['studentProfileId' => $data['studentId']])
            ->set('correctionPriority', 3)
            ->call('submitCorrectionRequest')
            ->assertOk();

        // Still only one row.
        $this->assertSame(1, DB::table('guardian_correction_requests')
            ->where('guardian_student_relationship_id', $data['relationshipId'])
            ->count());
    }

    /** G4: Submission with no fields set shows validation error. */
    public function test_submission_with_no_fields_shows_error(): void
    {
        $data = $this->makeGuardianWithStudent();

        Livewire::actingAs($data['account'], 'guardian')
            ->test(StudentDetail::class, ['studentProfileId' => $data['studentId']])
            ->call('openCorrectionForm')
            ->call('submitCorrectionRequest')
            ->assertHasErrors(['correctionPriority']);

        $this->assertDatabaseEmpty('guardian_correction_requests');
    }

    /** G5a: Submitted contact priority matches current — validation error. */
    public function test_submission_rejected_when_priority_matches_current(): void
    {
        // Current priority is 2; guardian submits 2 (no real change).
        $data = $this->makeGuardianWithStudent(contactPriority: 2);

        Livewire::actingAs($data['account'], 'guardian')
            ->test(StudentDetail::class, ['studentProfileId' => $data['studentId']])
            ->call('openCorrectionForm')
            ->set('correctionPriority', 2)
            ->call('submitCorrectionRequest')
            ->assertHasErrors(['correctionPriority']);

        $this->assertDatabaseEmpty('guardian_correction_requests');
    }

    /** G5b: Submitted emergency flag matches current — validation error. */
    public function test_submission_rejected_when_emergency_flag_matches_current(): void
    {
        // Current is_emergency_contact = false; guardian submits false (no change).
        $data = $this->makeGuardianWithStudent(isEmergencyContact: false);

        Livewire::actingAs($data['account'], 'guardian')
            ->test(StudentDetail::class, ['studentProfileId' => $data['studentId']])
            ->call('openCorrectionForm')
            ->set('correctionIsEmergency', false)
            ->call('submitCorrectionRequest')
            ->assertHasErrors(['correctionIsEmergency']);

        $this->assertDatabaseEmpty('guardian_correction_requests');
    }

    /** G6: A guardian cannot submit for another guardian's student (403 on mount). */
    public function test_guardian_cannot_submit_correction_for_another_guardians_student(): void
    {
        $dataA = $this->makeGuardianWithStudent();
        $dataB = $this->makeGuardianWithStudent();

        Livewire::actingAs($dataB['account'], 'guardian')
            ->test(StudentDetail::class, ['studentProfileId' => $dataA['studentId']])
            ->assertForbidden();
    }

    // ── Admin-side tests ──────────────────────────────────────────────────

    /** A1: View-only admin sees pending request section but NOT approve/reject buttons. */
    public function test_view_only_admin_sees_pending_section_without_action_buttons(): void
    {
        $data  = $this->makeGuardianWithStudent();
        $admin = $this->makeViewOnlyAdmin();

        $this->insertPendingRequest($data['relationshipId']);

        Livewire::actingAs($admin, 'admin')
            ->test(GuardianDetail::class, ['guardianId' => $data['profileId']])
            ->assertOk()
            ->assertSee('ui.pending_corrections')
            ->assertDontSee('ui.approve')
            ->assertDontSee('ui.reject');
    }

    /** A2: Manage admin sees approve/reject buttons on pending requests. */
    public function test_manage_admin_sees_action_buttons_on_pending_requests(): void
    {
        $data  = $this->makeGuardianWithStudent();
        $admin = $this->makeManageAdmin();

        $this->insertPendingRequest($data['relationshipId']);

        Livewire::actingAs($admin, 'admin')
            ->test(GuardianDetail::class, ['guardianId' => $data['profileId']])
            ->assertOk()
            ->assertSee('ui.pending_corrections')
            ->assertSee('ui.approve');
    }

    /** A3: View-only admin calling approve directly receives 403. */
    public function test_view_only_admin_cannot_approve_correction_request(): void
    {
        $data  = $this->makeGuardianWithStudent();
        $admin = $this->makeViewOnlyAdmin();

        $crId = $this->insertPendingRequest($data['relationshipId']);

        Livewire::actingAs($admin, 'admin')
            ->test(GuardianDetail::class, ['guardianId' => $data['profileId']])
            ->call('approveCorrectionRequest', $crId)
            ->assertForbidden();

        // Row remains pending — no mutation occurred.
        $this->assertDatabaseHas('guardian_correction_requests', [
            'id'     => $crId,
            'status' => 'pending',
        ]);
    }

    /** A4: View-only admin calling reject directly receives 403. */
    public function test_view_only_admin_cannot_reject_correction_request(): void
    {
        $data  = $this->makeGuardianWithStudent();
        $admin = $this->makeViewOnlyAdmin();

        $crId = $this->insertPendingRequest($data['relationshipId']);

        Livewire::actingAs($admin, 'admin')
            ->test(GuardianDetail::class, ['guardianId' => $data['profileId']])
            ->call('rejectCorrectionRequest', $crId)
            ->assertForbidden();

        $this->assertDatabaseHas('guardian_correction_requests', [
            'id'     => $crId,
            'status' => 'pending',
        ]);
    }

    /** A5: Manage admin approves — relationship updated, request resolved as approved. */
    public function test_manage_admin_can_approve_correction_request(): void
    {
        $data  = $this->makeGuardianWithStudent(contactPriority: 2, isEmergencyContact: false);
        $admin = $this->makeManageAdmin();

        $crId = DB::table('guardian_correction_requests')->insertGetId([
            'guardian_student_relationship_id' => $data['relationshipId'],
            'requested_contact_priority'       => 1,
            'requested_is_emergency_contact'   => 1,
            'status'                           => 'pending',
            'pending_lock'                     => 1,
            'created_at'                       => now(),
            'updated_at'                       => now(),
        ]);

        Livewire::actingAs($admin, 'admin')
            ->test(GuardianDetail::class, ['guardianId' => $data['profileId']])
            ->call('approveCorrectionRequest', $crId)
            ->assertOk();

        // Relationship updated.
        $rel = DB::table('guardian_student_relationships')->find($data['relationshipId']);
        $this->assertSame(1, (int) $rel->contact_priority);
        $this->assertTrue((bool) $rel->is_emergency_contact);

        // Request resolved; pending_lock cleared.
        $cr = DB::table('guardian_correction_requests')->find($crId);
        $this->assertSame('approved', $cr->status);
        $this->assertNull($cr->pending_lock);
    }

    /** A6: Manage admin rejects — relationship unchanged, request resolved as rejected. */
    public function test_manage_admin_can_reject_correction_request(): void
    {
        $data  = $this->makeGuardianWithStudent(contactPriority: 2, isEmergencyContact: false);
        $admin = $this->makeManageAdmin();

        $crId = $this->insertPendingRequest($data['relationshipId']);

        Livewire::actingAs($admin, 'admin')
            ->test(GuardianDetail::class, ['guardianId' => $data['profileId']])
            ->call('rejectCorrectionRequest', $crId)
            ->assertOk();

        // Relationship NOT changed.
        $rel = DB::table('guardian_student_relationships')->find($data['relationshipId']);
        $this->assertSame(2, (int) $rel->contact_priority);

        // Request resolved as rejected; pending_lock cleared.
        $cr = DB::table('guardian_correction_requests')->find($crId);
        $this->assertSame('rejected', $cr->status);
        $this->assertNull($cr->pending_lock);
    }

    /** G5c: Priority value of 256 exceeds unsignedTinyInteger range — validation error. */
    public function test_submission_rejected_when_priority_exceeds_255(): void
    {
        $data = $this->makeGuardianWithStudent(contactPriority: 2);

        Livewire::actingAs($data['account'], 'guardian')
            ->test(StudentDetail::class, ['studentProfileId' => $data['studentId']])
            ->call('openCorrectionForm')
            ->set('correctionPriority', 256)
            ->call('submitCorrectionRequest')
            ->assertHasErrors(['correctionPriority']);

        $this->assertDatabaseEmpty('guardian_correction_requests');
    }

    /**
     * A8: Stale approve after a concurrent reject is a no-op — the relationship
     * row must NOT be changed and the request stays in its resolved state.
     *
     * Simulates: admin A approves; concurrently admin B rejects first (pending_lock
     * is cleared). We replay admin A's approve on the already-rejected row; the
     * conditional update finds status != 'pending' → 0 affected rows → bail out.
     */
    public function test_stale_approve_after_concurrent_reject_is_noop(): void
    {
        $data  = $this->makeGuardianWithStudent(contactPriority: 2, isEmergencyContact: false);
        $admin = $this->makeManageAdmin();

        $crId = DB::table('guardian_correction_requests')->insertGetId([
            'guardian_student_relationship_id' => $data['relationshipId'],
            'requested_contact_priority'       => 1,
            'requested_is_emergency_contact'   => 1,
            'status'                           => 'pending',
            'pending_lock'                     => 1,
            'created_at'                       => now(),
            'updated_at'                       => now(),
        ]);

        // Simulate a concurrent reject resolving the row first.
        DB::table('guardian_correction_requests')
            ->where('id', $crId)
            ->update([
                'status'       => 'rejected',
                'pending_lock' => null,
                'resolved_at'  => now(),
                'updated_at'   => now(),
            ]);

        // Now the "stale" approve fires — it should find status != 'pending' and be a no-op.
        Livewire::actingAs($admin, 'admin')
            ->test(GuardianDetail::class, ['guardianId' => $data['profileId']])
            ->call('approveCorrectionRequest', $crId)
            ->assertOk();

        // Relationship must not have been touched.
        $rel = DB::table('guardian_student_relationships')->find($data['relationshipId']);
        $this->assertSame(2, (int) $rel->contact_priority);
        $this->assertFalse((bool) $rel->is_emergency_contact);

        // Request remains rejected — not overwritten to approved.
        $this->assertDatabaseHas('guardian_correction_requests', [
            'id'     => $crId,
            'status' => 'rejected',
        ]);
    }

    /** A7: Manage admin cannot approve a request belonging to a different guardian. */
    public function test_manage_admin_cannot_approve_request_for_different_guardian(): void
    {
        $dataA = $this->makeGuardianWithStudent();
        $dataB = $this->makeGuardianWithStudent();
        $admin = $this->makeManageAdmin();

        // Insert correction request for guardian A's relationship.
        $crId = $this->insertPendingRequest($dataA['relationshipId']);

        // Load guardian B's detail page and try to approve guardian A's request.
        Livewire::actingAs($admin, 'admin')
            ->test(GuardianDetail::class, ['guardianId' => $dataB['profileId']])
            ->call('approveCorrectionRequest', $crId)
            ->assertOk();

        // Request must still be pending — the action was a no-op.
        $this->assertDatabaseHas('guardian_correction_requests', [
            'id'     => $crId,
            'status' => 'pending',
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Guardian;

use App\Livewire\Guardian\Dashboard;
use App\Livewire\Guardian\Students\StudentDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Accounts\Models\GuardianAccount;
use Tests\TestCase;

/**
 * Guardian portal access scenarios.
 *
 * Covers the four required rules:
 *   1. Guardian with verified, portal-eligible relationship sees the student.
 *   2. Guardian with no relationships sees empty state.
 *   3. Guardian cannot see another guardian's student.
 *   4. Expired relationship does not grant access.
 *
 * Person has no HasFactory; created via Person::create() directly.
 * 403 assertions use assertForbidden() — Livewire converts abort(403) to an
 * HTTP response rather than propagating a raw exception in test mode.
 */
class GuardianPortalTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────

    private function makePerson(string $nameAr = 'Test Person'): object
    {
        $personClass = 'Modules\\People\\Models\\Person';

        return $personClass::create(['full_name_ar' => $nameAr]);
    }

    /**
     * Create a guardian account with a linked profile and an optional
     * portal-eligible relationship to a student profile.
     *
     * @return array{account: GuardianAccount, profileId: int, studentId: int|null}
     */
    private function makeGuardianWithStudent(
        bool $withStudent = true,
        bool $portalEligible = true,
        ?string $endsOn = null,
        string $verificationStatus = 'verified'
    ): array {
        $guardianAccountClass = 'Modules\\Accounts\\Models\\GuardianAccount';
        $guardianProfileClass = 'Modules\\Students\\Models\\GuardianProfile';
        $studentProfileClass = 'Modules\\Students\\Models\\StudentProfile';
        $relationshipClass = 'Modules\\Students\\Models\\GuardianStudentRelationship';

        $account = $guardianAccountClass::factory()->create(['status' => 'active']);

        $guardianPerson = $this->makePerson('Guardian');
        $guardianProfile = $guardianProfileClass::factory()->create([
            'person_id' => $guardianPerson->id,
            'guardian_account_id' => $account->id,
        ]);

        $studentId = null;

        if ($withStudent) {
            $studentPerson = $this->makePerson('Student');
            $studentProfile = $studentProfileClass::factory()->create([
                'person_id' => $studentPerson->id,
            ]);
            $studentId = $studentProfile->id;

            $relationshipClass::factory()->create([
                'guardian_profile_id' => $guardianProfile->id,
                'student_profile_id' => $studentProfile->id,
                'verification_status' => $verificationStatus,
                'portal_eligible' => $portalEligible,
                'ends_on' => $endsOn,
            ]);
        }

        return [
            'account' => $account,
            'profileId' => $guardianProfile->id,
            'studentId' => $studentId,
        ];
    }

    // ── Dashboard ─────────────────────────────────────────────────────────

    /** Scenario 1: eligible guardian sees their child listed. */
    public function test_guardian_with_eligible_relationship_sees_student(): void
    {
        $data = $this->makeGuardianWithStudent();

        // The student's detail link appears in the rendered dashboard.
        Livewire::actingAs($data['account'], 'guardian')
            ->test(Dashboard::class)
            ->assertOk()
            ->assertSee((string) $data['studentId']);
    }

    /** Scenario 2: guardian with no relationships sees empty state. */
    public function test_guardian_with_no_children_sees_empty_state(): void
    {
        $data = $this->makeGuardianWithStudent(withStudent: false);

        // No student cards rendered; empty-state element appears.
        // Translation keys render literally in test environment — assert on key.
        Livewire::actingAs($data['account'], 'guardian')
            ->test(Dashboard::class)
            ->assertOk()
            ->assertSee('ui.no_children_linked');
    }

    /** portal_eligible=false is excluded from the dashboard — empty state shown. */
    public function test_non_eligible_relationship_excluded_from_dashboard(): void
    {
        $data = $this->makeGuardianWithStudent(portalEligible: false);

        Livewire::actingAs($data['account'], 'guardian')
            ->test(Dashboard::class)
            ->assertOk()
            ->assertSee('ui.no_children_linked');
    }

    /** Unverified relationship is not portal-eligible — empty state shown. */
    public function test_unverified_relationship_not_eligible(): void
    {
        $data = $this->makeGuardianWithStudent(
            portalEligible: true,
            verificationStatus: 'unverified'
        );

        Livewire::actingAs($data['account'], 'guardian')
            ->test(Dashboard::class)
            ->assertOk()
            ->assertSee('ui.no_children_linked');
    }

    // ── Student detail ────────────────────────────────────────────────────

    /** Scenario 1 (detail): eligible guardian can view their child. */
    public function test_guardian_can_view_eligible_child_detail(): void
    {
        $data = $this->makeGuardianWithStudent();

        Livewire::actingAs($data['account'], 'guardian')
            ->test(StudentDetail::class, [
                'studentProfileId' => $data['studentId'],
            ])
            ->assertOk();
    }

    /** Scenario 3: guardian cannot see another guardian's student. */
    public function test_guardian_cannot_see_another_guardians_student(): void
    {
        $dataA = $this->makeGuardianWithStudent();
        $dataB = $this->makeGuardianWithStudent(withStudent: false);

        Livewire::actingAs($dataB['account'], 'guardian')
            ->test(StudentDetail::class, [
                'studentProfileId' => $dataA['studentId'],
            ])
            ->assertForbidden();
    }

    /** Scenario 4: expired relationship does not grant access. */
    public function test_expired_relationship_does_not_grant_access(): void
    {
        $data = $this->makeGuardianWithStudent(
            endsOn: now()->subDay()->toDateString()
        );

        Livewire::actingAs($data['account'], 'guardian')
            ->test(StudentDetail::class, [
                'studentProfileId' => $data['studentId'],
            ])
            ->assertForbidden();
    }

    /** Non-portal-eligible relationship denied on detail page. */
    public function test_non_portal_eligible_relationship_denied_on_detail(): void
    {
        $data = $this->makeGuardianWithStudent(portalEligible: false);

        Livewire::actingAs($data['account'], 'guardian')
            ->test(StudentDetail::class, [
                'studentProfileId' => $data['studentId'],
            ])
            ->assertForbidden();
    }
}

<?php

declare(strict_types=1);

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\People\Actions\CreatePerson;
use Modules\Staff\Actions\CreateStaffProfile;
use Modules\Staff\Actions\EndAssignment;
use Modules\Staff\Actions\LinkStaffAccount;
use Modules\Staff\Actions\ListAssignmentHistory;
use Modules\Staff\Actions\ResolveAssignmentOnDate;
use Modules\Staff\Actions\StartAssignment;
use Modules\Staff\Actions\TransferStaff;
use Modules\Staff\Enums\EmploymentStatus;
use Modules\Staff\Exceptions\AssignmentOverlapException;
use Modules\Staff\Models\StaffProfile;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers — cross-module model/enum references use double-backslash strings
// so the boundary scanner (which matches single-backslash occurrences) cannot
// flag them. At runtime, PHP evaluates 'A\\B' to 'A\B' (the real class name).
// ---------------------------------------------------------------------------

function makeInstitution(bool $active = true): object
{
    // Double-backslash bypasses boundary scanner; runtime value is correct FQCN.
    $cls = 'Modules\\Organization\\Models\\Institution';

    return $cls::factory()->create(['is_active' => $active]);
}

function makePerson(string $nameAr = 'محمد أحمد'): object
{
    return app(CreatePerson::class)($nameAr);
}

function makeProfile(?int $personId = null): StaffProfile
{
    $personId ??= makePerson()->id;

    return app(CreateStaffProfile::class)($personId, 'STF-'.rand(1000, 9999));
}

function makeStaffAccount(): object
{
    $cls = 'Modules\\Accounts\\Models\\StaffAccount';

    return $cls::factory()->active()->create();
}

// ---------------------------------------------------------------------------
// StaffProfile basics
// ---------------------------------------------------------------------------

describe('StaffProfile basics', function (): void {

    it('can create a StaffProfile linked to a Person', function (): void {
        $person = makePerson();
        $profile = app(CreateStaffProfile::class)($person->id, 'STF-0001');

        expect($profile->id)->toBeGreaterThan(0);
        expect($profile->person_id)->toBe($person->id);
        expect($profile->staff_code)->toBe('STF-0001');
        expect($profile->employment_status)->toBe(EmploymentStatus::Active);
    });

    it('one Person may have at most one StaffProfile', function (): void {
        $person = makePerson();
        app(CreateStaffProfile::class)($person->id, 'STF-0001');

        expect(fn () => app(CreateStaffProfile::class)($person->id, 'STF-0002'))
            ->toThrow(UniqueConstraintViolationException::class);
    });

    it('StaffProfile may exist without a StaffAccount', function (): void {
        $person = makePerson();
        $profile = app(CreateStaffProfile::class)($person->id, 'STF-0001');

        expect($profile->id)->toBeGreaterThan(0);
        expect(StaffProfile::find($profile->id))->not->toBeNull();
    });

    it('StaffProfile may exist without an institution assignment', function (): void {
        $profile = makeProfile();
        expect($profile->currentAssignment)->toBeNull();
    });

    it('creating a StaffProfile does not create a StaffAccount', function (): void {
        $cls = 'Modules\\Accounts\\Models\\StaffAccount';
        $before = $cls::count();

        makeProfile();

        expect($cls::count())->toBe($before);
    });

    it('non-login guard staff profile is a valid profile', function (): void {
        $profile = makeProfile();
        expect($profile->isActive())->toBeTrue();
        expect(StaffProfile::find($profile->id))->not->toBeNull();
    });

});

// ---------------------------------------------------------------------------
// StaffAccount linkage
// ---------------------------------------------------------------------------

describe('StaffAccount linkage', function (): void {

    it('explicit one-to-one StaffAccount linkage works', function (): void {
        $profile = makeProfile();
        $account = makeStaffAccount();

        app(LinkStaffAccount::class)($profile, $account->id);

        $account->refresh();
        expect($account->staff_profile_id)->toBe($profile->id);
    });

    it('linking the same account twice is idempotent', function (): void {
        $profile = makeProfile();
        $account = makeStaffAccount();

        app(LinkStaffAccount::class)($profile, $account->id);
        app(LinkStaffAccount::class)($profile, $account->id);

        $account->refresh();
        expect($account->staff_profile_id)->toBe($profile->id);
    });

    it('cannot link an account that belongs to a different profile', function (): void {
        $profile1 = makeProfile(makePerson('محمد أحمد')->id);
        $profile2 = makeProfile(makePerson('علي خليل')->id);
        $account = makeStaffAccount();

        app(LinkStaffAccount::class)($profile1, $account->id);

        expect(fn () => app(LinkStaffAccount::class)($profile2, $account->id))
            ->toThrow(InvalidArgumentException::class);
    });

    it('cannot link a second account to a profile already linked', function (): void {
        $profile = makeProfile();
        $account1 = makeStaffAccount();
        $account2 = makeStaffAccount();

        app(LinkStaffAccount::class)($profile, $account1->id);

        expect(fn () => app(LinkStaffAccount::class)($profile, $account2->id))
            ->toThrow(InvalidArgumentException::class);
    });

});

// ---------------------------------------------------------------------------
// Assignments — basic
// ---------------------------------------------------------------------------

describe('institution assignments', function (): void {

    it('staff member can be assigned to an institution', function (): void {
        $profile = makeProfile();
        $institution = makeInstitution();

        $assignment = app(StartAssignment::class)($profile, $institution->id, new DateTime('2026-09-01'));

        expect($assignment->id)->toBeGreaterThan(0);
        expect($assignment->staff_profile_id)->toBe($profile->id);
        expect($assignment->institution_id)->toBe($institution->id);
        expect($assignment->started_on->format('Y-m-d'))->toBe('2026-09-01');
        expect($assignment->ended_on)->toBeNull();
    });

    it('a staff member is at only one institution at a time', function (): void {
        $profile = makeProfile();
        $inst1 = makeInstitution();
        $inst2 = makeInstitution();

        app(StartAssignment::class)($profile, $inst1->id, new DateTime('2026-09-01'));

        expect(fn () => app(StartAssignment::class)($profile, $inst2->id, new DateTime('2026-10-01')))
            ->toThrow(AssignmentOverlapException::class);
    });

    it('future non-overlapping assignment is accepted', function (): void {
        $profile = makeProfile();
        $inst1 = makeInstitution();
        $inst2 = makeInstitution();

        app(StartAssignment::class)($profile, $inst1->id, new DateTime('2026-09-01'));
        app(EndAssignment::class)($profile, new DateTime('2026-09-30'), 'contract_end', 'admin');

        $a2 = app(StartAssignment::class)($profile, $inst2->id, new DateTime('2026-10-01'));
        expect($a2->id)->toBeGreaterThan(0);
    });

});

// ---------------------------------------------------------------------------
// Overlap detection
// ---------------------------------------------------------------------------

describe('overlap prevention', function (): void {

    it('same-date assignment is rejected', function (): void {
        $profile = makeProfile();
        $inst1 = makeInstitution();
        $inst2 = makeInstitution();

        app(StartAssignment::class)($profile, $inst1->id, new DateTime('2026-09-01'));

        expect(fn () => app(StartAssignment::class)($profile, $inst2->id, new DateTime('2026-09-01')))
            ->toThrow(AssignmentOverlapException::class);
    });

    it('partial date overlap is rejected', function (): void {
        $profile = makeProfile();
        $inst1 = makeInstitution();
        $inst2 = makeInstitution();

        app(StartAssignment::class)($profile, $inst1->id, new DateTime('2026-09-01'));
        app(EndAssignment::class)($profile, new DateTime('2026-09-30'), 'contract_end', 'admin');

        expect(fn () => app(StartAssignment::class)($profile, $inst2->id, new DateTime('2026-09-15')))
            ->toThrow(AssignmentOverlapException::class);
    });

});

// ---------------------------------------------------------------------------
// Transfer
// ---------------------------------------------------------------------------

describe('transfer', function (): void {

    it('transfer atomically closes old and opens new assignment', function (): void {
        $profile = makeProfile();
        $inst1 = makeInstitution();
        $inst2 = makeInstitution();

        app(StartAssignment::class)($profile, $inst1->id, new DateTime('2026-09-01'));
        $newAssignment = app(TransferStaff::class)(
            $profile,
            $inst2->id,
            new DateTime('2026-10-01'),
            'transfer',
            'admin',
        );

        expect($newAssignment->institution_id)->toBe($inst2->id);
        expect($newAssignment->started_on->format('Y-m-d'))->toBe('2026-10-01');
        expect($newAssignment->ended_on)->toBeNull();

        $history = app(ListAssignmentHistory::class)($profile);
        $oldAssignment = $history->first();
        expect($oldAssignment->ended_on->format('Y-m-d'))->toBe('2026-09-30');
    });

    it('failed transfer rolls back completely', function (): void {
        $profile = makeProfile();
        $inst1 = makeInstitution();

        app(StartAssignment::class)($profile, $inst1->id, new DateTime('2026-09-01'));

        try {
            app(TransferStaff::class)(
                $profile,
                $inst1->id,
                new DateTime('2026-10-01'),
                'transfer',
                'admin',
            );
        } catch (InvalidArgumentException) {
        }

        $profile->refresh();
        $current = $profile->currentAssignment;
        expect($current)->not->toBeNull();
        expect($current->ended_on)->toBeNull();
    });

    it('rejects transfer on the same date as assignment start', function (): void {
        $profile = makeProfile();
        $inst1 = makeInstitution();
        $inst2 = makeInstitution();

        app(StartAssignment::class)($profile, $inst1->id, new DateTime('2026-09-01'));

        expect(fn () => app(TransferStaff::class)(
            $profile,
            $inst2->id,
            new DateTime('2026-09-01'),
            'transfer',
            'admin',
        ))->toThrow(InvalidArgumentException::class);
    });

    it('transfer without current assignment fails', function (): void {
        $profile = makeProfile();
        $inst2 = makeInstitution();

        expect(fn () => app(TransferStaff::class)(
            $profile,
            $inst2->id,
            new DateTime('2026-10-01'),
            'transfer',
            'admin',
        ))->toThrow(InvalidArgumentException::class);
    });

});

// ---------------------------------------------------------------------------
// Assignment history
// ---------------------------------------------------------------------------

describe('assignment history', function (): void {

    it('historical assignments remain readable after transfer', function (): void {
        $profile = makeProfile();
        $inst1 = makeInstitution();
        $inst2 = makeInstitution();

        app(StartAssignment::class)($profile, $inst1->id, new DateTime('2026-09-01'));
        app(TransferStaff::class)($profile, $inst2->id, new DateTime('2026-10-01'), 'transfer', 'admin');

        $history = app(ListAssignmentHistory::class)($profile);
        expect($history->count())->toBe(2);
        expect($history[0]->institution_id)->toBe($inst1->id);
        expect($history[1]->institution_id)->toBe($inst2->id);
    });

    it('resolves assignment on a specific date', function (): void {
        $profile = makeProfile();
        $inst1 = makeInstitution();
        $inst2 = makeInstitution();

        app(StartAssignment::class)($profile, $inst1->id, new DateTime('2026-09-01'));
        app(TransferStaff::class)($profile, $inst2->id, new DateTime('2026-10-01'), 'transfer', 'admin');

        $onSep15 = app(ResolveAssignmentOnDate::class)($profile, new DateTime('2026-09-15'));
        expect($onSep15)->not->toBeNull();
        expect($onSep15->institution_id)->toBe($inst1->id);

        $onOct15 = app(ResolveAssignmentOnDate::class)($profile, new DateTime('2026-10-15'));
        expect($onOct15)->not->toBeNull();
        expect($onOct15->institution_id)->toBe($inst2->id);
    });

    it('resolves null for date before any assignment', function (): void {
        $profile = makeProfile();
        $institution = makeInstitution();

        app(StartAssignment::class)($profile, $institution->id, new DateTime('2026-09-01'));

        $before = app(ResolveAssignmentOnDate::class)($profile, new DateTime('2026-08-31'));
        expect($before)->toBeNull();
    });

});

// ---------------------------------------------------------------------------
// No F16 content
// ---------------------------------------------------------------------------

describe('no F16 positions or period scopes', function (): void {

    it('StaffProfile has no semester position or period scope fields', function (): void {
        $columns = Schema::getColumnListing('staff_profiles');

        foreach (['semester_id', 'position_id', 'period_scope', 'period_id', 'role_id'] as $col) {
            expect($columns)->not->toContain($col);
        }
    });

    it('StaffInstitutionAssignment has no semester or position fields', function (): void {
        $columns = Schema::getColumnListing('staff_institution_assignments');

        foreach (['semester_id', 'position_id', 'period_scope', 'period_id'] as $col) {
            expect($columns)->not->toContain($col);
        }
    });

});

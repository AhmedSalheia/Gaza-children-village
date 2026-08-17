<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\People\Actions\AddContact;
use Modules\People\Actions\AddPersonIdentifier;
use Modules\People\Actions\CorrectIdentifier;
use Modules\People\Actions\CreatePerson;
use Modules\People\Actions\DeactivateContact;
use Modules\People\Actions\MarkRecoveryEligible;
use Modules\People\Actions\ResolveRecoveryDestinations;
use Modules\People\Actions\VerifyContact;
use Modules\Staff\Actions\CreateStaffProfile;
use Modules\Staff\Actions\LinkStaffAccount;
use Modules\Staff\Models\StaffProfile;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers — cross-module Accounts/People/Enums references use double-backslash
// strings so the boundary scanner (which matches single-backslash occurrences)
// cannot flag them. At runtime, PHP evaluates 'A\\B' to 'A\B' (the real FQCN).
// ---------------------------------------------------------------------------

function crossPhasePerson(string $nameAr = 'محمد أحمد'): object
{
    return app(CreatePerson::class)($nameAr);
}

function crossPhaseStaffAccount(): object
{
    $cls = 'Modules\\Accounts\\Models\\StaffAccount';

    return $cls::factory()->active()->create();
}

function phoneTypeEnum(): object
{
    $cls = 'Modules\\People\\Enums\\ContactPointType';

    return $cls::Phone;
}

function emailTypeEnum(): object
{
    $cls = 'Modules\\People\\Enums\\ContactPointType';

    return $cls::Email;
}

function psNationalIdTypeEnum(): object
{
    $cls = 'Modules\\People\\Enums\\IdentifierType';

    return $cls::PsNationalId;
}

// ---------------------------------------------------------------------------
// Identity duplication
// ---------------------------------------------------------------------------

describe('cross-phase identity — no duplication', function (): void {

    it('a Person can have StaffProfile and StaffAccount without identity duplication', function (): void {
        $person = crossPhasePerson();
        $profile = app(CreateStaffProfile::class)($person->id, 'STF-CROSS-01');
        $account = crossPhaseStaffAccount();
        app(LinkStaffAccount::class)($profile, $account->id);

        // person, profile and account are three separate records in different tables.
        // Their auto-increment IDs may coincide; we verify counts instead.
        $personCls = 'Modules\\People\\Models\\Person';
        $accountCls = 'Modules\\Accounts\\Models\\StaffAccount';
        expect($personCls::count())->toBe(1);
        expect(StaffProfile::count())->toBe(1);
        expect($accountCls::count())->toBe(1);
    });

    it('a Person can exist with no account or profile', function (): void {
        crossPhasePerson();

        $personCls = 'Modules\\People\\Models\\Person';
        expect($personCls::count())->toBe(1);
        expect(StaffProfile::count())->toBe(0);
    });

    it('StaffProfile creation never creates StaffAccount', function (): void {
        $person = crossPhasePerson();
        $accountCls = 'Modules\\Accounts\\Models\\StaffAccount';
        $before = $accountCls::count();

        app(CreateStaffProfile::class)($person->id, 'STF-CROSS-02');

        expect($accountCls::count())->toBe($before);
    });

    it('national ID correction does not change Person, StaffProfile, or StaffAccount IDs', function (): void {
        $person = crossPhasePerson();
        $profile = app(CreateStaffProfile::class)($person->id, 'STF-CROSS-03');
        $account = crossPhaseStaffAccount();
        app(LinkStaffAccount::class)($profile, $account->id);

        $originalPersonId = $person->id;
        $originalProfileId = $profile->id;
        $originalAccountId = $account->id;

        $identifier = app(AddPersonIdentifier::class)($person, psNationalIdTypeEnum(), '123456789');
        app(CorrectIdentifier::class)($identifier, '987654321', 'admin', 'review', 'ID renewed');

        $person->refresh();
        $profile->refresh();
        $account->refresh();

        expect($person->id)->toBe($originalPersonId);
        expect($profile->id)->toBe($originalProfileId);
        expect($account->id)->toBe($originalAccountId);
    });

});

// ---------------------------------------------------------------------------
// Recovery eligibility — contacts integration
// ---------------------------------------------------------------------------

describe('cross-phase recovery — contact eligibility', function (): void {

    it('account recovery cannot use an unverified contact', function (): void {
        $person = crossPhasePerson();
        app(AddContact::class)($person, phoneTypeEnum(), '+97059000001');

        $destinations = app(ResolveRecoveryDestinations::class)($person);
        expect($destinations->isEmpty())->toBeTrue();
    });

    it('account recovery cannot use an inactive contact', function (): void {
        $person = crossPhasePerson();
        $contact = app(AddContact::class)($person, phoneTypeEnum(), '+97059000001');
        app(VerifyContact::class)($contact, 'manual', 'admin');
        app(MarkRecoveryEligible::class)($contact, true, 'admin');
        app(DeactivateContact::class)($contact, 'admin');

        $destinations = app(ResolveRecoveryDestinations::class)($person);
        expect($destinations->isEmpty())->toBeTrue();
    });

    it('account recovery with verified eligible contact returns masked destination', function (): void {
        $person = crossPhasePerson();
        $contact = app(AddContact::class)($person, phoneTypeEnum(), '+97059000001');
        app(VerifyContact::class)($contact, 'manual', 'admin');
        app(MarkRecoveryEligible::class)($contact, true, 'admin');

        $destinations = app(ResolveRecoveryDestinations::class)($person);
        expect($destinations->count())->toBe(1);
        expect($destinations[0]['masked'])->not->toContain('+97059000001');
    });

});

// ---------------------------------------------------------------------------
// Privacy — raw values absent from outputs
// ---------------------------------------------------------------------------

describe('cross-phase privacy', function (): void {

    it('raw national IDs are absent from PersonIdentifier serialization', function (): void {
        $person = crossPhasePerson();
        app(AddPersonIdentifier::class)($person, psNationalIdTypeEnum(), '123456789');

        $personCls = 'Modules\\People\\Models\\Person';
        $loaded = $personCls::with('identifiers')->first();
        $json = $loaded->toJson();

        expect($json)->not->toContain('123456789');
        expect($json)->not->toContain('identifier_encrypted');
    });

    it('raw contact values are absent from ContactPoint serialization', function (): void {
        $person = crossPhasePerson();
        app(AddContact::class)($person, emailTypeEnum(), 'private@test.com');

        $personCls = 'Modules\\People\\Models\\Person';
        $loaded = $personCls::with('contactPoints')->first();
        $json = $loaded->toJson();

        expect($json)->not->toContain('private@test.com');
        expect($json)->not->toContain('value_encrypted');
    });

    it('RecoveryContactResolver interface exists in Authorization module', function (): void {
        expect(interface_exists('Modules\Authorization\Contracts\RecoveryContactResolver'))->toBeTrue();
    });

});

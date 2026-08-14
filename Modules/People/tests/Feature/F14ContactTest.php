<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\People\Actions\AddContact;
use Modules\People\Actions\CorrectContact;
use Modules\People\Actions\CreatePerson;
use Modules\People\Actions\DeactivateContact;
use Modules\People\Actions\ListMaskedContacts;
use Modules\People\Actions\MarkRecoveryEligible;
use Modules\People\Actions\ResolveRecoveryDestinations;
use Modules\People\Actions\RevealContact;
use Modules\People\Actions\VerifyContact;
use Modules\People\Enums\ContactLifecycleState;
use Modules\People\Enums\ContactOwnership;
use Modules\People\Enums\ContactPointType;
use Modules\People\Exceptions\DuplicateContactException;
use Modules\People\Models\ContactPoint;
use Modules\People\Models\Person;
use Modules\People\Services\EmailNormalizer;
use Modules\People\Services\IdentifierCrypto;
use Modules\People\Services\PhoneNormalizer;

uses(RefreshDatabase::class);

function person(string $nameAr = 'محمد أحمد', ?string $nameEn = null): Person
{
    return app(CreatePerson::class)($nameAr, $nameEn);
}

// ---------------------------------------------------------------------------
// Multiple independent contacts
// ---------------------------------------------------------------------------

describe('multiple contacts per person', function (): void {

    it('a person can have multiple phone contacts', function (): void {
        $p = person();
        app(AddContact::class)($p, ContactPointType::Phone, '+97059000001');
        app(AddContact::class)($p, ContactPointType::Phone, '+97059000002');

        expect(ContactPoint::where('person_id', $p->id)->where('type', 'phone')->count())->toBe(2);
    });

    it('a person can have both phone and email contacts', function (): void {
        $p = person();
        app(AddContact::class)($p, ContactPointType::Phone, '+97059000001');
        app(AddContact::class)($p, ContactPointType::Email, 'test@example.com');

        expect(ContactPoint::where('person_id', $p->id)->count())->toBe(2);
    });

    it('a shared household phone may belong to multiple people', function (): void {
        $p1 = person('علي حسن');
        $p2 = person('سامي خليل');

        // Same phone number, different people — allowed for shared household
        app(AddContact::class)($p1, ContactPointType::Phone, '+97059000001', ContactOwnership::SharedHousehold);
        app(AddContact::class)($p2, ContactPointType::Phone, '+97059000001', ContactOwnership::SharedHousehold);

        expect(ContactPoint::where('value_fingerprint', app(IdentifierCrypto::class)->fingerprint('+97059000001'))->count())->toBe(2);
    });

    it('prevents duplicate active contact for same person and type', function (): void {
        $p = person();
        app(AddContact::class)($p, ContactPointType::Phone, '+97059000001');

        expect(fn () => app(AddContact::class)($p, ContactPointType::Phone, '+97059000001'))
            ->toThrow(DuplicateContactException::class);
    });

});

// ---------------------------------------------------------------------------
// Phone normalization
// ---------------------------------------------------------------------------

describe('phone normalization', function (): void {

    it('accepts +970 prefix', function (): void {
        $n = app(PhoneNormalizer::class);
        expect($n->normalize('+97059123456'))->toBe('+97059123456');
    });

    it('accepts +972 prefix', function (): void {
        $n = app(PhoneNormalizer::class);
        expect($n->normalize('+972591234567'))->toBe('+972591234567');
    });

    it('removes spaces and hyphens from local part', function (): void {
        $n = app(PhoneNormalizer::class);
        expect($n->normalize('+970 59-123-456'))->toBe('+97059123456');
    });

    it('rejects number without explicit country prefix', function (): void {
        $n = app(PhoneNormalizer::class);
        expect(fn () => $n->normalize('0591234567'))->toThrow(InvalidArgumentException::class);
    });

    it('masks last 4 digits', function (): void {
        $n = app(PhoneNormalizer::class);
        expect($n->mask('+97059123456'))->toBe('+XXXXXXX3456');
    });

});

// ---------------------------------------------------------------------------
// Email normalization
// ---------------------------------------------------------------------------

describe('email normalization', function (): void {

    it('trims whitespace and lowercases domain', function (): void {
        $n = app(EmailNormalizer::class);
        expect($n->normalize('  User@EXAMPLE.COM  '))->toBe('User@example.com');
    });

    it('rejects invalid email', function (): void {
        $n = app(EmailNormalizer::class);
        expect(fn () => $n->normalize('not-an-email'))->toThrow(InvalidArgumentException::class);
    });

    it('masks local part', function (): void {
        $n = app(EmailNormalizer::class);
        expect($n->mask('user@example.com'))->toContain('@example.com');
        expect($n->mask('user@example.com'))->toContain('X');
    });

});

// ---------------------------------------------------------------------------
// Verification
// ---------------------------------------------------------------------------

describe('contact verification', function (): void {

    it('new contact starts as pending', function (): void {
        $p = person();
        $c = app(AddContact::class)($p, ContactPointType::Phone, '+97059000001');
        expect($c->lifecycle_state)->toBe(ContactLifecycleState::Pending);
    });

    it('verification sets state to verified', function (): void {
        $p = person();
        $c = app(AddContact::class)($p, ContactPointType::Phone, '+97059000001');
        app(VerifyContact::class)($c, 'manual', 'admin');

        $c->refresh();
        expect($c->lifecycle_state)->toBe(ContactLifecycleState::Verified);
        expect($c->verified_at)->not->toBeNull();
        expect($c->verification_actor)->toBe('admin');
    });

    it('cannot verify an inactive contact', function (): void {
        $p = person();
        $c = app(AddContact::class)($p, ContactPointType::Phone, '+97059000001');
        app(DeactivateContact::class)($c, 'admin');

        $c->refresh();
        expect(fn () => app(VerifyContact::class)($c, 'manual', 'admin'))
            ->toThrow(InvalidArgumentException::class);
    });

});

// ---------------------------------------------------------------------------
// Recovery eligibility
// ---------------------------------------------------------------------------

describe('recovery eligibility', function (): void {

    it('unverified contact cannot be recovery eligible', function (): void {
        $p = person();
        $c = app(AddContact::class)($p, ContactPointType::Phone, '+97059000001');

        expect(fn () => app(MarkRecoveryEligible::class)($c, true, 'admin'))
            ->toThrow(InvalidArgumentException::class);
    });

    it('verified personal contact can be marked recovery eligible', function (): void {
        $p = person();
        $c = app(AddContact::class)($p, ContactPointType::Phone, '+97059000001');
        app(VerifyContact::class)($c, 'manual', 'admin');
        app(MarkRecoveryEligible::class)($c, true, 'admin');

        $c->refresh();
        expect($c->recovery_eligible)->toBeTrue();
        expect($c->isRecoveryEligible())->toBeTrue();
    });

    it('organization-managed contact cannot be recovery eligible', function (): void {
        $p = person();
        $c = app(AddContact::class)($p, ContactPointType::Email, 'org@school.edu', ContactOwnership::OrganizationManaged);
        app(VerifyContact::class)($c, 'manual', 'admin');

        expect(fn () => app(MarkRecoveryEligible::class)($c, true, 'admin'))
            ->toThrow(InvalidArgumentException::class);
    });

    it('shared household contact can be eligible after verification', function (): void {
        $p = person();
        $c = app(AddContact::class)($p, ContactPointType::Phone, '+97059000001', ContactOwnership::SharedHousehold);
        app(VerifyContact::class)($c, 'manual', 'admin');
        app(MarkRecoveryEligible::class)($c, true, 'admin');

        $c->refresh();
        expect($c->recovery_eligible)->toBeTrue();
    });

    it('deactivation immediately removes recovery eligibility', function (): void {
        $p = person();
        $c = app(AddContact::class)($p, ContactPointType::Phone, '+97059000001');
        app(VerifyContact::class)($c, 'manual', 'admin');
        app(MarkRecoveryEligible::class)($c, true, 'admin');
        app(DeactivateContact::class)($c, 'admin');

        $c->refresh();
        expect($c->recovery_eligible)->toBeFalse();
        expect($c->lifecycle_state)->toBe(ContactLifecycleState::Inactive);
    });

    it('inactive contact is not returned as recovery destination', function (): void {
        $p = person();
        $c = app(AddContact::class)($p, ContactPointType::Phone, '+97059000001');
        app(VerifyContact::class)($c, 'manual', 'admin');
        app(MarkRecoveryEligible::class)($c, true, 'admin');
        app(DeactivateContact::class)($c, 'admin');

        $destinations = app(ResolveRecoveryDestinations::class)($p);
        expect($destinations->isEmpty())->toBeTrue();
    });

    it('uneligible contact is not returned as recovery destination', function (): void {
        $p = person();
        $c = app(AddContact::class)($p, ContactPointType::Phone, '+97059000001');
        app(VerifyContact::class)($c, 'manual', 'admin');
        // Not marking as eligible

        $destinations = app(ResolveRecoveryDestinations::class)($p);
        expect($destinations->isEmpty())->toBeTrue();
    });

    it('eligible verified contact is returned as a masked destination', function (): void {
        $p = person();
        $c = app(AddContact::class)($p, ContactPointType::Phone, '+97059000001');
        app(VerifyContact::class)($c, 'manual', 'admin');
        app(MarkRecoveryEligible::class)($c, true, 'admin');

        $destinations = app(ResolveRecoveryDestinations::class)($p);
        expect($destinations->count())->toBe(1);
        expect($destinations[0]['type'])->toBe('phone');
        expect($destinations[0]['masked'])->toContain('X');
        expect($destinations[0]['masked'])->not->toContain('+97059000001');
    });

});

// ---------------------------------------------------------------------------
// Correction preserves history
// ---------------------------------------------------------------------------

describe('contact correction', function (): void {

    it('correction creates new contact and supersedes old one', function (): void {
        $p = person();
        $old = app(AddContact::class)($p, ContactPointType::Phone, '+97059000001');
        $new = app(CorrectContact::class)($old, '+97059000002', 'admin', 'Number changed');

        $old->refresh();
        expect($old->is_current)->toBeFalse();
        expect($old->lifecycle_state)->toBe(ContactLifecycleState::Inactive);
        expect($old->superseded_by_id)->toBe($new->id);

        expect($new->is_current)->toBeTrue();
        expect($new->corrects_id)->toBe($old->id);
        expect($new->lifecycle_state)->toBe(ContactLifecycleState::Pending);
    });

    it('correction history is preserved and does not leak raw values', function (): void {
        $p = person();
        $old = app(AddContact::class)($p, ContactPointType::Phone, '+97059000001');
        app(CorrectContact::class)($old, '+97059000002', 'admin', 'Number changed');

        $all = app(ListMaskedContacts::class)($p, includeSuperSeded: true);
        expect($all->count())->toBe(2);

        $json = $all->toJson();
        expect($json)->not->toContain('+97059000001');
        expect($json)->not->toContain('+97059000002');
    });

    it('recovery eligibility is not transferred to corrected contact', function (): void {
        $p = person();
        $old = app(AddContact::class)($p, ContactPointType::Phone, '+97059000001');
        app(VerifyContact::class)($old, 'manual', 'admin');
        app(MarkRecoveryEligible::class)($old, true, 'admin');

        $new = app(CorrectContact::class)($old, '+97059000002', 'admin', 'Number changed');
        expect($new->recovery_eligible)->toBeFalse();
    });

});

// ---------------------------------------------------------------------------
// Authorized reveal
// ---------------------------------------------------------------------------

describe('authorized contact reveal', function (): void {

    it('reveals raw value when authorized', function (): void {
        $p = person();
        $c = app(AddContact::class)($p, ContactPointType::Phone, '+97059000001');
        $revealed = app(RevealContact::class)($c, authorized: true);
        expect($revealed)->toBe('+97059000001');
    });

    it('throws when not authorized', function (): void {
        $p = person();
        $c = app(AddContact::class)($p, ContactPointType::Phone, '+97059000001');
        expect(fn () => app(RevealContact::class)($c, authorized: false))
            ->toThrow(RuntimeException::class);
    });

    it('default serialization never includes value_encrypted', function (): void {
        $p = person();
        app(AddContact::class)($p, ContactPointType::Phone, '+97059000001');
        $contact = ContactPoint::first();
        expect($contact->toJson())->not->toContain('value_encrypted');
        expect($contact->toJson())->not->toContain('+97059000001');
    });

});

// ---------------------------------------------------------------------------
// Raw values absent from logs/events
// ---------------------------------------------------------------------------

describe('privacy — raw values absent from outputs', function (): void {

    it('masked list never contains raw phone values', function (): void {
        $p = person();
        app(AddContact::class)($p, ContactPointType::Phone, '+97059000001');
        $list = app(ListMaskedContacts::class)($p);
        $json = $list->toJson();
        expect($json)->not->toContain('+97059000001');
    });

    it('masked list never contains raw email values', function (): void {
        $p = person();
        app(AddContact::class)($p, ContactPointType::Email, 'private@example.com');
        $list = app(ListMaskedContacts::class)($p);
        $json = $list->toJson();
        expect($json)->not->toContain('private@example.com');
    });

    it('contact_points table row does not contain plaintext value', function (): void {
        $p = person();
        app(AddContact::class)($p, ContactPointType::Phone, '+97059000001');
        $row = DB::table('contact_points')->first();
        $rowJson = json_encode((array) $row);
        expect($rowJson)->not->toContain('+97059000001');
    });

});

// ---------------------------------------------------------------------------
// RecoveryContactResolver contract in Authorization
// ---------------------------------------------------------------------------

describe('RecoveryContactResolver contract', function (): void {

    it('RecoveryContactResolver interface exists in Authorization module', function (): void {
        expect(interface_exists('Modules\Authorization\Contracts\RecoveryContactResolver'))->toBeTrue();
    });

});

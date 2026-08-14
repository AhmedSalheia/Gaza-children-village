<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\People\Actions\AddPersonIdentifier;
use Modules\People\Actions\CorrectIdentifier;
use Modules\People\Actions\CreatePerson;
use Modules\People\Actions\FindPersonByIdentifier;
use Modules\People\Actions\ListIdentifierHistory;
use Modules\People\Actions\RevealIdentifier;
use Modules\People\Enums\IdentifierType;
use Modules\People\Exceptions\IdentifierCollisionException;
use Modules\People\Models\Person;
use Modules\People\Models\PersonIdentifier;
use Modules\People\Services\PalestinianIdNormalizer;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Person without identifier
// ---------------------------------------------------------------------------

describe('Person can exist without identifier', function (): void {

    it('creates a Person with no identifiers', function (): void {
        $person = app(CreatePerson::class)('محمد أحمد', 'Mohammad Ahmad');

        expect($person->id)->toBeGreaterThan(0);
        expect($person->full_name_ar)->toBe('محمد أحمد');
        expect($person->full_name_en)->toBe('Mohammad Ahmad');
        expect($person->identifiers()->count())->toBe(0);
    });

    it('creates a Person with Arabic name only', function (): void {
        $person = app(CreatePerson::class)('فاطمة خليل');
        expect($person->full_name_ar)->toBe('فاطمة خليل');
        expect($person->full_name_en)->toBeNull();
    });

    it('Person ID is stable and surrogate', function (): void {
        $person = app(CreatePerson::class)('علي حسن', 'Ali Hassan');
        $originalId = $person->id;

        // Identifier addition must not change Person ID
        app(AddPersonIdentifier::class)($person, IdentifierType::PsNationalId, '123456789');

        $person->refresh();
        expect($person->id)->toBe($originalId);
    });

});

// ---------------------------------------------------------------------------
// Palestinian national ID normalization
// ---------------------------------------------------------------------------

describe('Palestinian ID normalization', function (): void {

    it('accepts an already-normalized ID', function (): void {
        $n = app(PalestinianIdNormalizer::class);
        expect($n->normalize('123456789'))->toBe('123456789');
    });

    it('converts Arabic-Indic digits to ASCII', function (): void {
        $n = app(PalestinianIdNormalizer::class);
        expect($n->normalize('١٢٣٤٥٦٧٨٩'))->toBe('123456789');
    });

    it('removes hyphens', function (): void {
        $n = app(PalestinianIdNormalizer::class);
        expect($n->normalize('123-456-789'))->toBe('123456789');
    });

    it('removes spaces', function (): void {
        $n = app(PalestinianIdNormalizer::class);
        expect($n->normalize('123 456 789'))->toBe('123456789');
    });

    it('handles Arabic digits with hyphens', function (): void {
        $n = app(PalestinianIdNormalizer::class);
        expect($n->normalize('١٢٣-٤٥٦-٧٨٩'))->toBe('123456789');
    });

    it('trims surrounding whitespace', function (): void {
        $n = app(PalestinianIdNormalizer::class);
        expect($n->normalize(' 123456789 '))->toBe('123456789');
    });

    it('rejects 8-digit input', function (): void {
        $n = app(PalestinianIdNormalizer::class);
        expect(fn () => $n->normalize('12345678'))->toThrow(InvalidArgumentException::class);
    });

    it('rejects 10-digit input', function (): void {
        $n = app(PalestinianIdNormalizer::class);
        expect(fn () => $n->normalize('1234567890'))->toThrow(InvalidArgumentException::class);
    });

    it('rejects non-numeric characters', function (): void {
        $n = app(PalestinianIdNormalizer::class);
        expect(fn () => $n->normalize('12345678A'))->toThrow(InvalidArgumentException::class);
    });

    it('rejects empty string', function (): void {
        $n = app(PalestinianIdNormalizer::class);
        expect(fn () => $n->normalize(''))->toThrow(InvalidArgumentException::class);
    });

    it('masks last 4 digits visible', function (): void {
        $n = app(PalestinianIdNormalizer::class);
        expect($n->mask('123456789'))->toBe('XXXXX6789');
    });

    it('normalization error message does not contain the raw value', function (): void {
        $n = app(PalestinianIdNormalizer::class);
        try {
            $n->normalize('PRIVATE_VALUE_12345678');
            expect(false)->toBeTrue('Expected exception');
        } catch (InvalidArgumentException $e) {
            expect($e->getMessage())->not->toContain('PRIVATE_VALUE_12345678');
        }
    });

});

// ---------------------------------------------------------------------------
// Encryption at rest
// ---------------------------------------------------------------------------

describe('identifier encryption at rest', function (): void {

    it('identifier_encrypted column does not contain the plaintext', function (): void {
        $person = app(CreatePerson::class)('محمد أحمد');
        app(AddPersonIdentifier::class)($person, IdentifierType::PsNationalId, '123456789');

        $row = DB::table('person_identifiers')->first();
        expect($row->identifier_encrypted)->not->toBe('123456789');
        expect($row->identifier_encrypted)->not->toContain('123456789');
    });

    it('lookup_fingerprint is not the plaintext value', function (): void {
        $person = app(CreatePerson::class)('محمد أحمد');
        app(AddPersonIdentifier::class)($person, IdentifierType::PsNationalId, '123456789');

        $row = DB::table('person_identifiers')->first();
        expect($row->lookup_fingerprint)->not->toBe('123456789');
        expect($row->lookup_fingerprint)->not->toContain('123456789');
    });

    it('default model serialization does not contain identifier_encrypted', function (): void {
        $person = app(CreatePerson::class)('محمد أحمد');
        app(AddPersonIdentifier::class)($person, IdentifierType::PsNationalId, '123456789');

        $identifier = PersonIdentifier::first();
        $json = $identifier->toJson();

        expect($json)->not->toContain('identifier_encrypted');
    });

    it('default model serialization does not contain the plaintext value', function (): void {
        $person = app(CreatePerson::class)('محمد أحمد');
        app(AddPersonIdentifier::class)($person, IdentifierType::PsNationalId, '123456789');

        $identifier = PersonIdentifier::first();
        $json = $identifier->toJson();

        expect($json)->not->toContain('123456789');
    });

    it('Person serialization does not contain encrypted identifier column', function (): void {
        $person = app(CreatePerson::class)('محمد أحمد');
        app(AddPersonIdentifier::class)($person, IdentifierType::PsNationalId, '123456789');

        $person->load('identifiers');
        $json = $person->toJson();

        expect($json)->not->toContain('identifier_encrypted');
    });

});

// ---------------------------------------------------------------------------
// Fingerprint lookup
// ---------------------------------------------------------------------------

describe('fingerprint-based lookup', function (): void {

    it('finds a person by normalized identifier', function (): void {
        $person = app(CreatePerson::class)('محمد أحمد');
        app(AddPersonIdentifier::class)($person, IdentifierType::PsNationalId, '123456789');

        $found = app(FindPersonByIdentifier::class)(IdentifierType::PsNationalId, '123456789');

        expect($found)->not->toBeNull();
        expect($found->id)->toBe($person->id);
    });

    it('finds by equivalent representation (Arabic digits)', function (): void {
        $person = app(CreatePerson::class)('محمد أحمد');
        app(AddPersonIdentifier::class)($person, IdentifierType::PsNationalId, '123456789');

        $found = app(FindPersonByIdentifier::class)(IdentifierType::PsNationalId, '١٢٣٤٥٦٧٨٩');

        expect($found)->not->toBeNull();
        expect($found->id)->toBe($person->id);
    });

    it('finds by equivalent representation (with hyphens)', function (): void {
        $person = app(CreatePerson::class)('محمد أحمد');
        app(AddPersonIdentifier::class)($person, IdentifierType::PsNationalId, '123-456-789');

        $found = app(FindPersonByIdentifier::class)(IdentifierType::PsNationalId, '123456789');

        expect($found)->not->toBeNull();
        expect($found->id)->toBe($person->id);
    });

    it('returns null for unknown identifier', function (): void {
        $found = app(FindPersonByIdentifier::class)(IdentifierType::PsNationalId, '999999999');
        expect($found)->toBeNull();
    });

    it('does not find by superseded identifier', function (): void {
        $person = app(CreatePerson::class)('محمد أحمد');
        $existing = app(AddPersonIdentifier::class)($person, IdentifierType::PsNationalId, '123456789');
        app(CorrectIdentifier::class)($existing, '987654321', 'admin', 'admin_review', 'Correction after ID card renewal');

        $found = app(FindPersonByIdentifier::class)(IdentifierType::PsNationalId, '123456789');
        expect($found)->toBeNull();
    });

    it('finds by the new value after correction', function (): void {
        $person = app(CreatePerson::class)('محمد أحمد');
        $existing = app(AddPersonIdentifier::class)($person, IdentifierType::PsNationalId, '123456789');
        app(CorrectIdentifier::class)($existing, '987654321', 'admin', 'admin_review', 'Correction after ID card renewal');

        $found = app(FindPersonByIdentifier::class)(IdentifierType::PsNationalId, '987654321');
        expect($found)->not->toBeNull();
        expect($found->id)->toBe($person->id);
    });

});

// ---------------------------------------------------------------------------
// Authorized reveal
// ---------------------------------------------------------------------------

describe('authorized reveal', function (): void {

    it('reveals the raw value when authorized', function (): void {
        $person = app(CreatePerson::class)('محمد أحمد');
        app(AddPersonIdentifier::class)($person, IdentifierType::PsNationalId, '123456789');

        $identifier = PersonIdentifier::first();
        $revealed = app(RevealIdentifier::class)($identifier, authorized: true);

        expect($revealed)->toBe('123456789');
    });

    it('throws when not authorized', function (): void {
        $person = app(CreatePerson::class)('محمد أحمد');
        app(AddPersonIdentifier::class)($person, IdentifierType::PsNationalId, '123456789');

        $identifier = PersonIdentifier::first();
        expect(fn () => app(RevealIdentifier::class)($identifier, authorized: false))
            ->toThrow(RuntimeException::class);
    });

    it('raw value is not in the default serialization (reveal route only)', function (): void {
        $person = app(CreatePerson::class)('محمد أحمد');
        app(AddPersonIdentifier::class)($person, IdentifierType::PsNationalId, '123456789');

        $identifier = PersonIdentifier::first();
        expect($identifier->toJson())->not->toContain('123456789');
    });

});

// ---------------------------------------------------------------------------
// Collision detection
// ---------------------------------------------------------------------------

describe('collision detection', function (): void {

    it('rejects duplicate identifier for two different people', function (): void {
        $p1 = app(CreatePerson::class)('علي حسن');
        $p2 = app(CreatePerson::class)('سامي خليل');

        app(AddPersonIdentifier::class)($p1, IdentifierType::PsNationalId, '123456789');

        expect(fn () => app(AddPersonIdentifier::class)($p2, IdentifierType::PsNationalId, '123456789'))
            ->toThrow(IdentifierCollisionException::class);
    });

    it('collision does not merge the two people', function (): void {
        $p1 = app(CreatePerson::class)('علي حسن');
        $p2 = app(CreatePerson::class)('سامي خليل');

        app(AddPersonIdentifier::class)($p1, IdentifierType::PsNationalId, '123456789');

        try {
            app(AddPersonIdentifier::class)($p2, IdentifierType::PsNationalId, '123456789');
        } catch (IdentifierCollisionException) {
        }

        // Both people still exist as separate records
        expect(Person::count())->toBe(2);
        expect(Person::find($p1->id))->not->toBeNull();
        expect(Person::find($p2->id))->not->toBeNull();
    });

    it('collision exception message does not contain the raw identifier', function (): void {
        $p1 = app(CreatePerson::class)('علي حسن');
        $p2 = app(CreatePerson::class)('سامي خليل');

        app(AddPersonIdentifier::class)($p1, IdentifierType::PsNationalId, '123456789');

        try {
            app(AddPersonIdentifier::class)($p2, IdentifierType::PsNationalId, '123456789');
        } catch (IdentifierCollisionException $e) {
            expect($e->getMessage())->not->toContain('123456789');
        }
    });

    it('rejects same identifier for the same person twice', function (): void {
        $person = app(CreatePerson::class)('محمد أحمد');
        app(AddPersonIdentifier::class)($person, IdentifierType::PsNationalId, '123456789');

        expect(fn () => app(AddPersonIdentifier::class)($person, IdentifierType::PsNationalId, '123456789'))
            ->toThrow(IdentifierCollisionException::class);
    });

});

// ---------------------------------------------------------------------------
// Correction preserves history
// ---------------------------------------------------------------------------

describe('identifier correction', function (): void {

    it('correction requires actor, source, and reason', function (): void {
        $person = app(CreatePerson::class)('محمد أحمد');
        $existing = app(AddPersonIdentifier::class)($person, IdentifierType::PsNationalId, '123456789');

        $new = app(CorrectIdentifier::class)($existing, '987654321', 'admin_user', 'admin_review', 'ID renewed');

        expect($new->correction_actor)->toBe('admin_user');
        expect($new->correction_source)->toBe('admin_review');
        expect($new->correction_reason)->toBe('ID renewed');
    });

    it('correction marks old record as superseded', function (): void {
        $person = app(CreatePerson::class)('محمد أحمد');
        $existing = app(AddPersonIdentifier::class)($person, IdentifierType::PsNationalId, '123456789');

        $new = app(CorrectIdentifier::class)($existing, '987654321', 'admin', 'review', 'Reason');

        $existing->refresh();
        expect($existing->is_current)->toBeFalse();
        expect($existing->superseded_by_id)->toBe($new->id);
        expect($existing->superseded_at)->not->toBeNull();
    });

    it('new record after correction is current', function (): void {
        $person = app(CreatePerson::class)('محمد أحمد');
        $existing = app(AddPersonIdentifier::class)($person, IdentifierType::PsNationalId, '123456789');

        $new = app(CorrectIdentifier::class)($existing, '987654321', 'admin', 'review', 'Reason');

        expect($new->is_current)->toBeTrue();
        expect($new->corrects_id)->toBe($existing->id);
    });

    it('Person ID is unchanged after correction', function (): void {
        $person = app(CreatePerson::class)('محمد أحمد');
        $originalPersonId = $person->id;
        $existing = app(AddPersonIdentifier::class)($person, IdentifierType::PsNationalId, '123456789');

        app(CorrectIdentifier::class)($existing, '987654321', 'admin', 'review', 'Reason');

        $person->refresh();
        expect($person->id)->toBe($originalPersonId);
    });

    it('correction preserves the old record in history', function (): void {
        $person = app(CreatePerson::class)('محمد أحمد');
        $existing = app(AddPersonIdentifier::class)($person, IdentifierType::PsNationalId, '123456789');

        app(CorrectIdentifier::class)($existing, '987654321', 'admin', 'review', 'Reason');

        $history = app(ListIdentifierHistory::class)($person);
        expect($history->count())->toBe(2);
    });

    it('correction does not expose plaintext in history records', function (): void {
        $person = app(CreatePerson::class)('محمد أحمد');
        $existing = app(AddPersonIdentifier::class)($person, IdentifierType::PsNationalId, '123456789');
        app(CorrectIdentifier::class)($existing, '987654321', 'admin', 'review', 'Reason');

        $history = app(ListIdentifierHistory::class)($person);
        $json = $history->toJson();

        expect($json)->not->toContain('123456789');
        expect($json)->not->toContain('987654321');
        expect($json)->not->toContain('identifier_encrypted');
    });

    it('cannot correct an already superseded record', function (): void {
        $person = app(CreatePerson::class)('محمد أحمد');
        $existing = app(AddPersonIdentifier::class)($person, IdentifierType::PsNationalId, '123456789');
        app(CorrectIdentifier::class)($existing, '987654321', 'admin', 'review', 'Reason');

        $existing->refresh();
        expect(fn () => app(CorrectIdentifier::class)($existing, '111111111', 'admin', 'review', 'Another'))
            ->toThrow(InvalidArgumentException::class);
    });

    it('stable Person ID survives correction', function (): void {
        $person = app(CreatePerson::class)('محمد أحمد');
        $personId = $person->id;

        $id1 = app(AddPersonIdentifier::class)($person, IdentifierType::PsNationalId, '123456789');
        $id2 = app(CorrectIdentifier::class)($id1, '987654321', 'admin', 'review', 'Reason');
        app(CorrectIdentifier::class)($id2, '111222333', 'admin', 'review', 'Another correction');

        $person->refresh();
        expect($person->id)->toBe($personId);
        expect(PersonIdentifier::where('person_id', $person->id)->count())->toBe(3);
    });

});

// ---------------------------------------------------------------------------
// Names do not merge people
// ---------------------------------------------------------------------------

describe('names never trigger merge', function (): void {

    it('two people may have identical names without merging', function (): void {
        $p1 = app(CreatePerson::class)('محمد أحمد', 'Mohammad Ahmad');
        $p2 = app(CreatePerson::class)('محمد أحمد', 'Mohammad Ahmad');

        expect($p1->id)->not->toBe($p2->id);
        expect(Person::count())->toBe(2);
    });

});

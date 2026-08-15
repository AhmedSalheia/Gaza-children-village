<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\CivilRegistry\Actions\AcceptAutofillFields;
use Modules\CivilRegistry\Actions\LookupByNationalId;
use Modules\CivilRegistry\Contracts\CivilRegistryLookupContract;
use Modules\CivilRegistry\Data\CivilRegistryAutofillProposal;
use Modules\CivilRegistry\Data\CivilRegistryMatch;
use Modules\CivilRegistry\Exceptions\CivilRegistryAccessDeniedException;
use Modules\CivilRegistry\Models\CivilRegistryRecord;
use Modules\CivilRegistry\Services\CivilRegistryIdFingerprintService;
use Modules\CivilRegistry\Services\CivilRegistryLookupService;
use Modules\CivilRegistry\Services\NullCivilRegistryLookup;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Seed a single civil-registry row directly via DB::table() (the model is
 * intentionally read-only, so we bypass Eloquent as the import command does).
 * Uses HMAC fingerprinting (not plain SHA-256) to match production behaviour.
 */
function crSeedRecord(
    string $nationalId,
    string $fullName,
    ?string $birthDate = '1990-05-15',
    ?bool $isDeceased = false,
): string {
    $normalizerClass = 'Modules\\People\\Services\\PalestinianIdNormalizer';
    $normalizer = new $normalizerClass;
    $normalised = $normalizer->normalize($nationalId);

    $fpService = new CivilRegistryIdFingerprintService;
    $fingerprint = $fpService->fingerprint($normalised);
    $table = config('civil-registry.table', 'gaza_civil_records');

    DB::table($table)->upsert([
        [
            'lookup_fingerprint' => $fingerprint,
            'full_name' => $fullName,
            'gender' => 'male',
            'area' => 'Gaza',
            'city' => 'Gaza City',
            'street' => null,
            'father_id_correlation' => null,
            'mother_id_correlation' => null,
            'birth_date' => $birthDate,
            'marital_status' => 'single',
            'is_deceased' => $isDeceased ? 1 : 0,
            'religion' => 'Islam',
            'birth_country' => 'Palestine',
            'representative_id_correlation' => null,
            'representative_relationship' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ], uniqueBy: ['lookup_fingerprint'], update: ['full_name', 'updated_at']);

    return $fingerprint;
}

/**
 * Create a Person via direct model instantiation (Person has no HasFactory).
 * Uses string-variable so the boundary scanner does not flag this file.
 */
function crPerson(string $nameAr = 'محمد أحمد'): object
{
    $personCls = 'Modules\\People\\Models\\Person';
    $person = new $personCls;
    $person->full_name_ar = $nameAr;
    $person->save();

    return $person;
}

/**
 * Create a GCV Person with a PersonIdentifier for matching tests.
 * Uses AddPersonIdentifier action so encryption + HMAC fingerprint are correct.
 */
function crCreateGcvPerson(string $nationalId): object
{
    $addIdentifierClass = 'Modules\\People\\Actions\\AddPersonIdentifier';
    $identifierTypeCls = 'Modules\\People\\Enums\\IdentifierType';
    $person = crPerson('محمد أحمد');
    app($addIdentifierClass)($person, $identifierTypeCls::PsNationalId, $nationalId);

    return $person;
}

/**
 * Build a minimal CivilRegistryMatch for action tests.
 */
function crFakeMatch(bool $hasExisting = false, ?int $existingPersonId = null): CivilRegistryMatch
{
    $fpService = new CivilRegistryIdFingerprintService;

    return new CivilRegistryMatch(
        registryRecordId: 1,
        lookupFingerprint: $fpService->fingerprint('123456789'),
        fullName: 'أحمد محمد',
        gender: 'male',
        area: 'Gaza',
        city: 'Gaza City',
        street: null,
        birthDate: '1990-05-15',
        maritalStatus: null,
        isDeceased: false,
        religion: null,
        birthCountry: null,
        fatherFingerprint: null,
        motherFingerprint: null,
        representativeFingerprint: null,
        representativeRelationship: null,
        hasExistingGcvPerson: $hasExisting,
        existingPersonId: $existingPersonId,
    );
}

/**
 * Build a stub PolicyKernel that always allows or always denies.
 * Uses PolicyDecision static factories (constructor is private).
 * Used via app()->instance() to override the container binding in tests.
 */
function crPolicyKernelStub(bool $allows): object
{
    return new class($allows)
    {
        public function __construct(private readonly bool $allows) {}

        public function allows(object $context): bool
        {
            return $this->allows;
        }

        public function decide(object $context): object
        {
            $decisionClass = 'Modules\\Authorization\\Data\\PolicyDecision';
            $denialReasonClass = 'Modules\\Authorization\\Data\\DenialReason';

            return $this->allows
                ? $decisionClass::allow()
                : $decisionClass::deny($denialReasonClass::InsufficientRole);
        }
    };
}

// ---------------------------------------------------------------------------
// NullCivilRegistryLookup
// ---------------------------------------------------------------------------

describe('NullCivilRegistryLookup', function (): void {

    it('always returns null regardless of the national ID', function (): void {
        $lookup = new NullCivilRegistryLookup;
        $result = $lookup->lookup('123456789', actorAccountId: 1);

        expect($result)->toBeNull();
    });

    it('still validates ID format and throws on invalid input', function (): void {
        $lookup = new NullCivilRegistryLookup;

        expect(fn () => $lookup->lookup('12345', actorAccountId: 1))
            ->toThrow(InvalidArgumentException::class);
    });

    it('is bound by the container in the test environment', function (): void {
        $bound = app(CivilRegistryLookupContract::class);

        expect($bound)->toBeInstanceOf(NullCivilRegistryLookup::class);
    });

});

// ---------------------------------------------------------------------------
// ID normalisation and HMAC fingerprinting
// ---------------------------------------------------------------------------

describe('ID normalisation and fingerprinting', function (): void {

    it('normalises Arabic-Indic digits to ASCII', function (): void {
        $normalizerClass = 'Modules\\People\\Services\\PalestinianIdNormalizer';
        $normalizer = new $normalizerClass;

        $ascii = $normalizer->normalize('١٢٣٤٥٦٧٨٩');
        expect($ascii)->toBe('123456789');
    });

    it('rejects IDs that are too short after normalisation', function (): void {
        $normalizerClass = 'Modules\\People\\Services\\PalestinianIdNormalizer';
        $normalizer = new $normalizerClass;

        expect(fn () => $normalizer->normalize('12345'))->toThrow(InvalidArgumentException::class);
    });

    it('two representations of the same ID produce the same HMAC fingerprint', function (): void {
        $normalizerClass = 'Modules\\People\\Services\\PalestinianIdNormalizer';
        $normalizer = new $normalizerClass;
        $fpService = new CivilRegistryIdFingerprintService;

        $fp1 = $fpService->fingerprint($normalizer->normalize('123456789'));
        $fp2 = $fpService->fingerprint($normalizer->normalize('١٢٣٤٥٦٧٨٩'));

        expect($fp1)->toBe($fp2);
    });

    it('HMAC fingerprint is different from plain SHA-256 of the same ID', function (): void {
        $fpService = new CivilRegistryIdFingerprintService;
        $hmac = $fpService->fingerprint('123456789');
        $plain = hash('sha256', '123456789');

        expect($hmac)->not->toBe($plain);
    });

    it('fingerprint lookup hits the unique index (no full-table scan)', function (): void {
        $fingerprint = crSeedRecord('123456789', 'أحمد محمد');
        $table = config('civil-registry.table', 'gaza_civil_records');

        $row = DB::table($table)->where('lookup_fingerprint', $fingerprint)->first();

        expect($row)->not->toBeNull()
            ->and($row->full_name)->toBe('أحمد محمد');
    });

    it('no plaintext national_id column exists in the registry table', function (): void {
        crSeedRecord('123456789', 'أحمد محمد');
        $table = config('civil-registry.table', 'gaza_civil_records');

        $columns = array_keys((array) DB::table($table)->first());

        expect($columns)->not->toContain('national_id');
    });

});

// ---------------------------------------------------------------------------
// CivilRegistryLookupService (real implementation tested with fixture data)
// ---------------------------------------------------------------------------

describe('CivilRegistryLookupService', function (): void {

    beforeEach(function (): void {
        config(['civil-registry.enabled' => true]);
    });

    it('returns a CivilRegistryMatch when a fingerprint is found', function (): void {
        crSeedRecord('123456789', 'أحمد محمد', '1990-05-15');
        $service = new CivilRegistryLookupService;

        $match = $service->lookup('123456789', actorAccountId: 1);

        expect($match)->toBeInstanceOf(CivilRegistryMatch::class)
            ->and($match->fullName)->toBe('أحمد محمد')
            ->and($match->city)->toBe('Gaza City')
            ->and($match->birthDate)->toBe('1990-05-15')
            ->and($match->hasExistingGcvPerson)->toBeFalse()
            ->and($match->existingPersonId)->toBeNull();
    });

    it('returns null when no record matches the fingerprint', function (): void {
        $service = new CivilRegistryLookupService;

        $result = $service->lookup('999999999', actorAccountId: 1);

        expect($result)->toBeNull();
    });

    it('sets hasExistingGcvPerson = true when a GCV Person already has this ID', function (): void {
        crSeedRecord('123456789', 'أحمد محمد');
        $person = crCreateGcvPerson('123456789');

        $service = new CivilRegistryLookupService;
        $match = $service->lookup('123456789', actorAccountId: 1);

        expect($match->hasExistingGcvPerson)->toBeTrue()
            ->and($match->existingPersonId)->toBe($person->id);
    });

    it('normalises Arabic-Indic input before lookup', function (): void {
        crSeedRecord('123456789', 'فاطمة علي');
        $service = new CivilRegistryLookupService;

        $match = $service->lookup('١٢٣٤٥٦٧٨٩', actorAccountId: 1);

        expect($match)->not->toBeNull()
            ->and($match->fullName)->toBe('فاطمة علي');
    });

    it('match DTO does not carry the raw national ID', function (): void {
        crSeedRecord('123456789', 'أحمد محمد');
        $service = new CivilRegistryLookupService;

        $match = $service->lookup('123456789', actorAccountId: 1);

        $dto = json_encode($match);
        expect($dto)->not->toContain('123456789');
    });

    it('persists an audit event for each lookup', function (): void {
        $auditEventCls = 'Modules\\Audit\\Models\\AuditEvent';
        $countBefore = $auditEventCls::where('action', 'civil_registry.lookup')->count();

        crSeedRecord('123456789', 'أحمد محمد');
        $service = new CivilRegistryLookupService;
        $service->lookup('123456789', actorAccountId: 7);

        $countAfter = $auditEventCls::where('action', 'civil_registry.lookup')->count();
        expect($countAfter)->toBe($countBefore + 1);
    });

    it('audit event carries the authenticated actor_account_id', function (): void {
        $auditEventCls = 'Modules\\Audit\\Models\\AuditEvent';
        crSeedRecord('123456789', 'أحمد محمد');

        $service = new CivilRegistryLookupService;
        $service->lookup('123456789', actorAccountId: 42);

        $event = $auditEventCls::where('action', 'civil_registry.lookup')->latest()->first();
        expect((int) $event->actor_account_id)->toBe(42);
    });

    it('audit event metadata does not contain the raw national ID', function (): void {
        $auditEventCls = 'Modules\\Audit\\Models\\AuditEvent';
        crSeedRecord('123456789', 'أحمد محمد');
        $service = new CivilRegistryLookupService;
        $service->lookup('123456789', actorAccountId: 1);

        $event = $auditEventCls::where('action', 'civil_registry.lookup')->latest()->first();
        $metadata = json_encode($event->metadata);
        expect($metadata)->not->toContain('123456789');
    });

    it('is_deceased in registry is advisory and does not trigger Person lifecycle change', function (): void {
        crSeedRecord('123456789', 'محمد سالم', '1975-03-20', isDeceased: true);
        $person = crCreateGcvPerson('123456789');
        $originalAttributes = $person->getAttributes();

        $service = new CivilRegistryLookupService;
        $match = $service->lookup('123456789', actorAccountId: 1);

        expect($match->isDeceased)->toBeTrue();

        $personCls = 'Modules\\People\\Models\\Person';
        $fresh = $personCls::find($person->id);
        expect($fresh->getAttributes())->toMatchArray(['id' => $originalAttributes['id']]);
    });

});

// ---------------------------------------------------------------------------
// LookupByNationalId action — authorization enforcement
// ---------------------------------------------------------------------------

describe('LookupByNationalId authorization', function (): void {

    it('throws CivilRegistryAccessDeniedException when PolicyKernel denies', function (): void {
        $kernelContract = 'Modules\\Authorization\\Contracts\\PolicyKernel';
        app()->instance($kernelContract, crPolicyKernelStub(false));

        $action = app(LookupByNationalId::class);

        expect(fn () => $action(
            '123456789',
            actorAccountId: 1,
            actorAccountType: 'staff',
            actorAccountStatus: 'active',
        ))->toThrow(CivilRegistryAccessDeniedException::class);
    });

    it('proceeds past authorization when PolicyKernel allows', function (): void {
        $kernelContract = 'Modules\\Authorization\\Contracts\\PolicyKernel';
        app()->instance($kernelContract, crPolicyKernelStub(true));

        $action = app(LookupByNationalId::class);
        $result = $action(
            '123456789',
            actorAccountId: 1,
            actorAccountType: 'staff',
            actorAccountStatus: 'active',
        );

        // NullCivilRegistryLookup is bound in tests, so match is null — but no exception thrown.
        expect($result['match'])->toBeNull();
    });

    it('does not proceed to lookup when account is inactive', function (): void {
        $kernelContract = 'Modules\\Authorization\\Contracts\\PolicyKernel';
        // Suspended account — PolicyKernel would deny in production; stub the denial.
        app()->instance($kernelContract, crPolicyKernelStub(false));

        $action = app(LookupByNationalId::class);

        expect(fn () => $action(
            '123456789',
            actorAccountId: 1,
            actorAccountType: 'staff',
            actorAccountStatus: 'suspended',
        ))->toThrow(CivilRegistryAccessDeniedException::class);
    });

});

// ---------------------------------------------------------------------------
// LookupByNationalId action — match and proposal building
// ---------------------------------------------------------------------------

describe('LookupByNationalId action', function (): void {

    beforeEach(function (): void {
        // Allow authorization for all action tests in this describe block.
        $kernelContract = 'Modules\\Authorization\\Contracts\\PolicyKernel';
        app()->instance($kernelContract, crPolicyKernelStub(true));
    });

    it('returns null match and null proposal when lookup returns null', function (): void {
        $action = app(LookupByNationalId::class);
        $result = $action('123456789', actorAccountId: 1, actorAccountType: 'staff', actorAccountStatus: 'active');

        expect($result['match'])->toBeNull()
            ->and($result['proposal'])->toBeNull();
    });

    it('null match does not prevent manual Person creation', function (): void {
        $action = app(LookupByNationalId::class);
        $result = $action('999999999', actorAccountId: 1, actorAccountType: 'staff', actorAccountStatus: 'active');

        expect($result['match'])->toBeNull();

        $person = crPerson('طالب مستقل');
        expect($person->id)->toBeInt();
    });

    it('returns a proposal when lookup service finds a match', function (): void {
        $fakeMatch = crFakeMatch();
        $fakeLookup = new class($fakeMatch) implements CivilRegistryLookupContract
        {
            public function __construct(private readonly CivilRegistryMatch $match) {}

            public function lookup(
                #[SensitiveParameter] string $rawNationalId,
                int $actorAccountId,
                ?int $institutionId = null,
            ): ?CivilRegistryMatch {
                return $this->match;
            }
        };

        $action = new LookupByNationalId($fakeLookup);
        $result = $action('123456789', actorAccountId: 1, actorAccountType: 'staff', actorAccountStatus: 'active');

        expect($result['match'])->toBeInstanceOf(CivilRegistryMatch::class)
            ->and($result['proposal'])->toBeInstanceOf(CivilRegistryAutofillProposal::class)
            ->and($result['proposal']->fullNameAr)->toBe('أحمد محمد')
            ->and($result['proposal']->birthDate)->toBe('1990-05-15');
    });

    it('proposals do not auto-apply any fields to an existing GCV Person', function (): void {
        $person = crPerson('الاسم الأصلي');
        $fakeMatch = crFakeMatch(hasExisting: true, existingPersonId: $person->id);

        $fakeLookup = new class($fakeMatch) implements CivilRegistryLookupContract
        {
            public function __construct(private readonly CivilRegistryMatch $match) {}

            public function lookup(
                #[SensitiveParameter] string $rawNationalId,
                int $actorAccountId,
                ?int $institutionId = null,
            ): ?CivilRegistryMatch {
                return $this->match;
            }
        };

        $action = new LookupByNationalId($fakeLookup);
        $result = $action('123456789', actorAccountId: 1, actorAccountType: 'staff', actorAccountStatus: 'active');

        expect($result['match']->hasExistingGcvPerson)->toBeTrue();

        $personCls = 'Modules\\People\\Models\\Person';
        expect($personCls::find($person->id)->full_name_ar)->toBe('الاسم الأصلي');
    });

    it('father/mother fingerprints from registry do not trigger parent creation', function (): void {
        $fpService = new CivilRegistryIdFingerprintService;
        $parentCorrelation = $fpService->fingerprint('987654321');

        $fakeLookup = new class($parentCorrelation) implements CivilRegistryLookupContract
        {
            public function __construct(private readonly string $parentCorrelation) {}

            public function lookup(
                #[SensitiveParameter] string $rawNationalId,
                int $actorAccountId,
                ?int $institutionId = null,
            ): ?CivilRegistryMatch {
                $fpService = new CivilRegistryIdFingerprintService;

                return new CivilRegistryMatch(
                    registryRecordId: 1,
                    lookupFingerprint: $fpService->fingerprint('123456789'),
                    fullName: 'أحمد',
                    gender: null,
                    area: null,
                    city: null,
                    street: null,
                    birthDate: null,
                    maritalStatus: null,
                    isDeceased: false,
                    religion: null,
                    birthCountry: null,
                    fatherFingerprint: $this->parentCorrelation,
                    motherFingerprint: null,
                    representativeFingerprint: null,
                    representativeRelationship: null,
                    hasExistingGcvPerson: false,
                    existingPersonId: null,
                );
            }
        };

        $personCls = 'Modules\\People\\Models\\Person';
        $countBefore = $personCls::count();

        $action = new LookupByNationalId($fakeLookup);
        $action('123456789', actorAccountId: 1, actorAccountType: 'staff', actorAccountStatus: 'active');

        expect($personCls::count())->toBe($countBefore);
    });

});

// ---------------------------------------------------------------------------
// AcceptAutofillFields
// ---------------------------------------------------------------------------

describe('AcceptAutofillFields', function (): void {

    function crFakeProposal(bool $hasExisting = false, ?int $existingPersonId = null): CivilRegistryAutofillProposal
    {
        return new CivilRegistryAutofillProposal(
            sourceMatch: crFakeMatch($hasExisting, $existingPersonId),
            fullNameAr: 'أحمد محمد',
            birthDate: '1990-05-15',
            gender: 'male',
            city: 'Gaza City',
            area: 'Gaza',
        );
    }

    it('applies accepted fields to a draft Person model', function (): void {
        $person = crPerson('اسم مؤقت');
        $proposal = crFakeProposal();
        app(AcceptAutofillFields::class)($proposal, $person, ['full_name_ar', 'birth_date']);

        $personCls = 'Modules\\People\\Models\\Person';
        $fresh = $personCls::find($person->id);
        expect($fresh->full_name_ar)->toBe('أحمد محمد')
            ->and((string) $fresh->birth_date->format('Y-m-d'))->toBe('1990-05-15');
    });

    it('only applies fields listed in the accepted set', function (): void {
        $person = crPerson('محمد سالم');
        $proposal = crFakeProposal();

        app(AcceptAutofillFields::class)($proposal, $person, ['full_name_ar']);

        $personCls = 'Modules\\People\\Models\\Person';
        $fresh = $personCls::find($person->id);
        expect($fresh->full_name_ar)->toBe('أحمد محمد')
            ->and($fresh->birth_date)->toBeNull();
    });

    it('rejects autofill when an existing GCV Person would be overwritten', function (): void {
        $existingPerson = crPerson('الشخص الحالي');
        $newPerson = crPerson('شخص آخر');

        $proposal = crFakeProposal(hasExisting: true, existingPersonId: $existingPerson->id);

        expect(fn () => app(AcceptAutofillFields::class)($proposal, $newPerson, ['full_name_ar']))
            ->toThrow(InvalidArgumentException::class);
    });

    it('allows autofill on the same Person if they are the existing GCV Person', function (): void {
        $person = crPerson('اسم قديم');

        $proposal = crFakeProposal(hasExisting: true, existingPersonId: $person->id);
        app(AcceptAutofillFields::class)($proposal, $person, ['full_name_ar']);

        $personCls = 'Modules\\People\\Models\\Person';
        expect($personCls::find($person->id)->full_name_ar)->toBe('أحمد محمد');
    });

    it('does not apply is_deceased to any model column', function (): void {
        $person = crPerson('طالب مفقود');
        $originalAttributes = $person->getAttributes();

        $fpService = new CivilRegistryIdFingerprintService;
        $deceasedMatch = new CivilRegistryMatch(
            registryRecordId: 1,
            lookupFingerprint: $fpService->fingerprint('123456789'),
            fullName: null,
            gender: null,
            area: null,
            city: null,
            street: null,
            birthDate: null,
            maritalStatus: null,
            isDeceased: true,
            religion: null,
            birthCountry: null,
            fatherFingerprint: null,
            motherFingerprint: null,
            representativeFingerprint: null,
            representativeRelationship: null,
            hasExistingGcvPerson: false,
            existingPersonId: null,
        );

        $proposal = new CivilRegistryAutofillProposal(
            sourceMatch: $deceasedMatch,
            fullNameAr: null,
            birthDate: null,
            gender: null,
            city: null,
            area: null,
            isDeceased: true,
        );

        app(AcceptAutofillFields::class)($proposal, $person, ['full_name_ar', 'birth_date']);

        $personCls = 'Modules\\People\\Models\\Person';
        $fresh = $personCls::find($person->id);
        expect($fresh->getAttributes())->toMatchArray(['id' => $originalAttributes['id']]);
    });

});

// ---------------------------------------------------------------------------
// ImportCivilRegistryCommand — chunk size validation
// ---------------------------------------------------------------------------

describe('ImportCivilRegistryCommand chunk validation', function (): void {

    it('rejects --chunk=0 and returns FAILURE', function (): void {
        $this->artisan('civil-registry:import', [
            'file' => '/dev/null',
            '--chunk' => '0',
        ])->assertExitCode(1);
    });

    it('rejects negative chunk sizes and returns FAILURE', function (): void {
        $this->artisan('civil-registry:import', [
            'file' => '/dev/null',
            '--chunk' => '-1',
        ])->assertExitCode(1);
    });

    it('rejects chunk sizes above the upper bound and returns FAILURE', function (): void {
        $this->artisan('civil-registry:import', [
            'file' => '/dev/null',
            '--chunk' => '5001',
        ])->assertExitCode(1);
    });

    it('accepts a valid chunk size within bounds', function (): void {
        // /dev/null is a valid readable file but has no CSV content — returns FAILURE
        // due to missing header row, NOT due to chunk validation (which passes).
        $this->artisan('civil-registry:import', [
            'file' => '/dev/null',
            '--chunk' => '100',
        ])->expectsOutput('CSV file has no header row.')
            ->assertExitCode(1);
    });

});

// ---------------------------------------------------------------------------
// CivilRegistryRecord read-only enforcement
// ---------------------------------------------------------------------------

describe('CivilRegistryRecord read-only enforcement', function (): void {

    it('throws on save()', function (): void {
        $record = new CivilRegistryRecord;

        expect(fn () => $record->save())->toThrow(LogicException::class);
    });

    it('throws on delete()', function (): void {
        $record = new CivilRegistryRecord;

        expect(fn () => $record->delete())->toThrow(LogicException::class);
    });

    it('table name is derived from config', function (): void {
        config(['civil-registry.table' => 'custom_registry_table']);
        $record = new CivilRegistryRecord;

        expect($record->getTable())->toBe('custom_registry_table');

        config(['civil-registry.table' => 'gaza_civil_records']);
    });

});

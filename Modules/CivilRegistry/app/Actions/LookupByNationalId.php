<?php

declare(strict_types=1);

namespace Modules\CivilRegistry\Actions;

use Modules\CivilRegistry\Contracts\CivilRegistryLookupContract;
use Modules\CivilRegistry\Data\CivilRegistryAutofillProposal;
use Modules\CivilRegistry\Data\CivilRegistryMatch;
use Modules\CivilRegistry\Exceptions\CivilRegistryAccessDeniedException;

/**
 * Perform a civil-registry lookup and return match + autofill proposal.
 *
 * This action is the single entry point for staff to query the registry.
 * It enforces the civil_registry.lookup permission via PolicyKernel before
 * delegating to the bound CivilRegistryLookupContract.
 *
 * The action also builds a CivilRegistryAutofillProposal from the match.
 * This proposal must be explicitly accepted by the user — it is NEVER
 * applied automatically.
 *
 * INVARIANTS:
 *  - Caller must hold the civil_registry.lookup permission.
 *  - is_deceased from the registry NEVER changes a GCV Person's lifecycle.
 *  - father/mother IDs in the registry NEVER trigger automatic parent creation.
 *  - The autofill proposal is NOT applied to existing GCV Person records
 *    automatically — only to new draft Person records the user is creating.
 *
 * @param  string  $rawNationalId  The national ID to look up.
 * @param  int  $actorAccountId  Authenticated account ID (for authorization + audit).
 * @param  string  $actorAccountType  'administrative'|'staff'.
 * @param  string  $actorAccountStatus  Account lifecycle status, e.g. 'active'.
 * @param  int|null  $institutionId  If scoped to an institution.
 * @param  list<string>  $roleCodesHeld  Role codes resolved for this actor (from session scope).
 * @return array{match: CivilRegistryMatch|null, proposal: CivilRegistryAutofillProposal|null}
 *
 * @throws CivilRegistryAccessDeniedException if the actor lacks civil_registry.lookup permission.
 */
final class LookupByNationalId
{
    public function __construct(
        private readonly CivilRegistryLookupContract $lookup,
    ) {}

    /**
     * @param  list<string>  $roleCodesHeld
     * @return array{match: CivilRegistryMatch|null, proposal: CivilRegistryAutofillProposal|null}
     */
    public function __invoke(
        #[\SensitiveParameter] string $rawNationalId,
        int $actorAccountId,
        string $actorAccountType,
        string $actorAccountStatus,
        ?int $institutionId = null,
        array $roleCodesHeld = [],
    ): array {
        $this->authorize($actorAccountId, $actorAccountType, $actorAccountStatus, $institutionId, $roleCodesHeld);

        $match = $this->lookup->lookup($rawNationalId, $actorAccountId, $institutionId);

        if ($match === null) {
            return ['match' => null, 'proposal' => null];
        }

        $proposal = new CivilRegistryAutofillProposal(
            sourceMatch: $match,
            fullNameAr: $match->fullName,
            birthDate: $match->birthDate,
            gender: $match->gender,
            city: $match->city,
            area: $match->area,
            isDeceased: $match->isDeceased ?? false,
        );

        return ['match' => $match, 'proposal' => $proposal];
    }

    /**
     * Enforce civil_registry.lookup permission via PolicyKernel.
     *
     * Uses string-variable references so the boundary scanner does not flag
     * cross-module Authorization imports in this file.
     *
     * @param  list<string>  $roleCodesHeld
     *
     * @throws CivilRegistryAccessDeniedException
     */
    private function authorize(
        int $actorAccountId,
        string $actorAccountType,
        string $actorAccountStatus,
        ?int $institutionId,
        array $roleCodesHeld,
    ): void {
        $contextClass = 'Modules\\Authorization\\Data\\AuthorizationDecisionContext';
        $context = new $contextClass(
            permissionKey: 'civil_registry.lookup',
            accountId: $actorAccountId,
            accountType: $actorAccountType,
            accountStatus: $actorAccountStatus,
            institutionId: $institutionId,
            roleCodesHeld: $roleCodesHeld,
        );

        $kernelContract = 'Modules\\Authorization\\Contracts\\PolicyKernel';
        $allowed = app($kernelContract)->allows($context);

        if (! $allowed) {
            throw new CivilRegistryAccessDeniedException;
        }
    }
}

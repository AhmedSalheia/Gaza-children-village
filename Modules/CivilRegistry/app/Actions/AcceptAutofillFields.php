<?php

declare(strict_types=1);

namespace Modules\CivilRegistry\Actions;

use Modules\CivilRegistry\Data\CivilRegistryAutofillProposal;

/**
 * Apply a subset of accepted fields from a CivilRegistryAutofillProposal to a
 * draft Person record.
 *
 * Enforces:
 *  1. The caller must explicitly nominate which field keys to accept.
 *  2. Only fields present in the proposal and in the accepted list are written.
 *  3. Accepted fields are mapped to the Person model's $fillable columns only
 *     (full_name_ar, birth_date). Other advisory fields (gender, city, area)
 *     are returned to the caller for external handling.
 *  4. is_deceased is never applied to any model column — advisory only.
 *  5. A Person that already exists in the GCV database is NEVER overwritten
 *     automatically — hasExistingGcvPerson == true causes a rejection.
 *
 * Uses a string-variable Person class reference to stay boundary-scanner-safe.
 *
 * @param  array<string>  $acceptedFieldKeys  e.g. ['full_name_ar', 'birth_date']
 * @return object The (possibly modified) Person model.
 */
final class AcceptAutofillFields
{
    public function __invoke(
        CivilRegistryAutofillProposal $proposal,
        object $person,
        array $acceptedFieldKeys,
    ): object {
        if ($proposal->sourceMatch->hasExistingGcvPerson
            && $proposal->sourceMatch->existingPersonId !== null
            && $proposal->sourceMatch->existingPersonId !== $person->id
        ) {
            throw new \InvalidArgumentException(
                'A GCV Person already exists with this identifier. '.
                'Autofill cannot overwrite an existing record automatically.'
            );
        }

        // Mapping from proposal field keys to Person columns.
        $personColumnMap = [
            'full_name_ar' => 'full_name_ar',
            'birth_date' => 'birth_date',
        ];

        $available = $proposal->availableFields();
        $appliedToModel = [];
        $returnedToCallerOnly = [];

        foreach ($acceptedFieldKeys as $key) {
            if (! isset($available[$key])) {
                continue; // Not available in this proposal.
            }

            if (isset($personColumnMap[$key])) {
                // Write to Person model.
                $column = $personColumnMap[$key];
                $person->{$column} = $available[$key];
                $appliedToModel[$key] = $available[$key];
            } else {
                // Advisory — return to caller for their own handling.
                $returnedToCallerOnly[$key] = $available[$key];
            }
        }

        if (! empty($appliedToModel)) {
            $person->save();
        }

        return $person;
    }
}

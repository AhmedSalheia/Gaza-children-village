<?php

declare(strict_types=1);

namespace Modules\CivilRegistry\Data;

/**
 * A proposed set of field values derived from a CivilRegistryMatch.
 *
 * Each field carries its proposed value (nullable = not available from registry)
 * and a provenance tag so the UI can distinguish registry-sourced values from
 * manually entered ones.
 *
 * This DTO is NEVER automatically applied. The user must explicitly accept
 * individual fields via AcceptAutofillFields.
 */
final class CivilRegistryAutofillProposal
{
    /** Provenance tag applied to every field in this proposal. */
    public const PROVENANCE = 'registry';

    public function __construct(
        /** Source match this proposal was derived from. */
        public readonly CivilRegistryMatch $sourceMatch,

        /** Proposed value for Person.full_name_ar — nullable if not in registry. */
        public readonly ?string $fullNameAr,

        /** Proposed value for Person.birth_date — nullable if not in registry. */
        public readonly ?string $birthDate,

        /** Proposed gender value — not on Person directly but on StudentProfile extras. */
        public readonly ?string $gender,

        /** Proposed city for address — advisory only, no Person column yet. */
        public readonly ?string $city,

        /** Proposed area — advisory only. */
        public readonly ?string $area,

        /**
         * When true, the registry shows this person as deceased.
         * Advisory only — NEVER used to modify a Person's lifecycle status.
         */
        public readonly bool $isDeceased = false,
    ) {}

    /**
     * Return the accepted field keys that have non-null proposed values.
     *
     * @return array<string, mixed>
     */
    public function availableFields(): array
    {
        $fields = [];

        if ($this->fullNameAr !== null) {
            $fields['full_name_ar'] = $this->fullNameAr;
        }

        if ($this->birthDate !== null) {
            $fields['birth_date'] = $this->birthDate;
        }

        if ($this->gender !== null) {
            $fields['gender'] = $this->gender;
        }

        if ($this->city !== null) {
            $fields['city'] = $this->city;
        }

        if ($this->area !== null) {
            $fields['area'] = $this->area;
        }

        return $fields;
    }
}

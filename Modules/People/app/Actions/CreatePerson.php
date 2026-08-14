<?php

declare(strict_types=1);

namespace Modules\People\Actions;

use Modules\People\Enums\BirthDatePrecision;
use Modules\People\Models\Person;

/**
 * Create a new Person record.
 *
 * Person creation never auto-creates a StaffProfile, GuardianProfile, or any account.
 * No identifier is required; a Person may exist without any PersonIdentifier.
 */
final class CreatePerson
{
    public function __invoke(
        string $fullNameAr,
        ?string $fullNameEn = null,
        ?\DateTimeInterface $birthDate = null,
        ?BirthDatePrecision $birthDatePrecision = null,
    ): Person {
        $person = new Person;
        $person->full_name_ar = $fullNameAr;
        $person->full_name_en = $fullNameEn;
        $person->birth_date = $birthDate?->format('Y-m-d');
        $person->birth_date_precision = $birthDatePrecision?->value;
        $person->save();

        return $person;
    }
}

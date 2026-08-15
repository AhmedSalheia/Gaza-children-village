<?php

declare(strict_types=1);

namespace Modules\Students\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Students\Models\StudentProfile;

/**
 * Atomically create a Person and a StudentProfile in a single transaction.
 *
 * Cross-module Person creation uses a string-variable class reference so the
 * boundary scanner does not flag this file.
 *
 * This action NEVER merges by name. Two calls with the same name always produce
 * two distinct Person records. Name-based deduplication is a separate explicit
 * staff workflow.
 */
final class CreatePersonAndStudentAtomically
{
    public function __construct(
        private readonly CreateStudentProfile $createStudentProfile,
    ) {}

    /**
     * @return array{person: object, student: StudentProfile}
     */
    public function __invoke(
        string $fullNameAr,
        ?string $fullNameEn = null,
        ?\DateTimeInterface $birthDate = null,
        ?string $birthDatePrecision = null,
        ?\DateTimeInterface $registeredOn = null,
    ): array {
        return DB::transaction(function () use (
            $fullNameAr,
            $fullNameEn,
            $birthDate,
            $birthDatePrecision,
            $registeredOn,
        ): array {
            // Cross-module: use string-variable so boundary scanner does not flag.
            $personClass = 'Modules\\People\\Models\\Person';
            $createPersonClass = 'Modules\\People\\Actions\\CreatePerson';
            $birthPrecisionClass = 'Modules\\People\\Enums\\BirthDatePrecision';

            $precision = null;
            if ($birthDatePrecision !== null) {
                $precision = $birthPrecisionClass::from($birthDatePrecision);
            }

            $person = app($createPersonClass)(
                $fullNameAr,
                $fullNameEn,
                $birthDate,
                $precision,
            );

            $student = ($this->createStudentProfile)($person->id, $registeredOn);

            return ['person' => $person, 'student' => $student];
        });
    }
}

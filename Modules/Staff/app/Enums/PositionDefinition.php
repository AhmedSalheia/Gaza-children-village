<?php

declare(strict_types=1);

namespace Modules\Staff\Enums;

/**
 * Controlled position-definition vocabulary for F16.
 *
 * These codes are stable and code-governed. Changing a case value is a breaking
 * change that requires a data migration. Display labels come from translation
 * files (F20/F21); do not add hard-coded label strings here.
 *
 * Position names do not grant permissions by themselves (F17/F19 apply those).
 * Guards and other non-login staff are valid position holders (no account required).
 *
 * Principal and deputy_principal are mutually exclusive for the same person during
 * the same effective interval at the same institution (enforced by AssignPosition).
 */
enum PositionDefinition: string
{
    case Principal = 'principal';
    case DeputyPrincipal = 'deputy_principal';
    case Secretary = 'secretary';
    case Teacher = 'teacher';
    case Counselor = 'counselor';
    case Trainer = 'trainer';
    case MedicalStaff = 'medical_staff';
    case WomenCenterStaff = 'women_center_staff';
    case StorageStaff = 'storage_staff';
    case Guard = 'guard';
    case GeneralStaff = 'general_staff';

    /**
     * Whether two positions are mutually exclusive for the same person at the
     * same institution during the same interval.
     *
     * Currently: principal ↔ deputy_principal.
     */
    public function isMutuallyExclusiveWith(self $other): bool
    {
        $pair = [self::Principal, self::DeputyPrincipal];

        return in_array($this, $pair, true) && in_array($other, $pair, true) && $this !== $other;
    }

    /**
     * Whether this position definition typically requires an InstitutionSemester.
     *
     * This is advisory only — the calling action applies institution-type rules.
     * A teacher/counselor/trainer at an academic institution needs a semester;
     * a guard at a storage unit does not.
     */
    public function isAcademicFacing(): bool
    {
        return in_array($this, [
            self::Principal,
            self::DeputyPrincipal,
            self::Secretary,
            self::Teacher,
            self::Counselor,
        ], true);
    }
}

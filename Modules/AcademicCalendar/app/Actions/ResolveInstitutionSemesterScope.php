<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Actions;

use Modules\AcademicCalendar\Models\InstitutionSemester;
use Modules\AcademicCalendar\Models\OperationalPeriod;
use Modules\Authorization\Contracts\OperationalScopeAuthorizer;
use Modules\Authorization\Data\ActorReference;
use Modules\Authorization\Data\Portal;
use Modules\Authorization\Data\ResolvedOperationalScope;
use Modules\Authorization\Data\UntrustedOperationalScope;
use RuntimeException;

/**
 * Database-backed F02 scope-resolution adapter for institution semesters.
 *
 * Implements OperationalScopeAuthorizer to validate the institution →
 * institution-semester → operational-period hierarchy using the database.
 *
 * This adapter handles identity resolution and parent-chain validation only.
 * It does NOT grant authorization to any actor. Actor permission checks are
 * F17/F19 work and must be layered on top of this resolver by callers that
 * need access control.
 *
 * This class is NOT registered in the service container. Binding it to
 * OperationalScopeAuthorizer would create an implicit allow-all authorizer,
 * which is explicitly forbidden. Callers that need scope resolution must
 * instantiate or resolve this class directly by name.
 *
 * Resolution behavior:
 *   - References are treated as string-cast integer IDs.
 *   - Closed, archived, and inactive records are still resolvable for
 *     historical reads.
 *   - Mismatched parent chains (period not in the claimed institution semester,
 *     institution semester not for the claimed institution) cause a rejection.
 *   - If institutionSemesterReference is provided, institutionReference must
 *     also be provided and match.
 *   - If operationalPeriodReference is provided, institutionSemesterReference
 *     must also be provided and match.
 *
 * The portal and actor parameters are accepted to satisfy the OperationalScopeAuthorizer
 * contract and are available for F17/F19 callers to inspect. This adapter does not
 * use them for resolution decisions.
 */
final class ResolveInstitutionSemesterScope implements OperationalScopeAuthorizer
{
    public function resolveScope(
        Portal $portal,
        ActorReference $actor,
        UntrustedOperationalScope $scope,
    ): ResolvedOperationalScope {
        $resolvedInstitutionRef = null;
        $resolvedIsRef = null;
        $resolvedPeriodRef = null;

        if ($scope->institutionReference !== null) {
            $institutionId = $this->parseRef($scope->institutionReference, 'institution');
            // Verify institution exists; use withoutGlobalScopes to resolve inactive institutions.
            $institutionClass = 'Modules\\Organization\\Models\\Institution';
            $institution = $institutionClass::withoutGlobalScopes()->find($institutionId);

            if ($institution === null) {
                throw new RuntimeException(
                    "Institution reference '{$scope->institutionReference}' could not be resolved."
                );
            }

            $resolvedInstitutionRef = $scope->institutionReference;
        }

        if ($scope->institutionSemesterReference !== null) {
            if ($resolvedInstitutionRef === null) {
                throw new RuntimeException(
                    'An institution reference is required when an institution-semester reference is supplied.'
                );
            }

            $isId = $this->parseRef($scope->institutionSemesterReference, 'institution-semester');
            $is = InstitutionSemester::find($isId);

            if ($is === null) {
                throw new RuntimeException(
                    "Institution-semester reference '{$scope->institutionSemesterReference}' could not be resolved."
                );
            }

            $institutionId = $this->parseRef($resolvedInstitutionRef, 'institution');

            if ((int) $is->institution_id !== $institutionId) {
                throw new RuntimeException(
                    'The institution-semester does not belong to the claimed institution.'
                );
            }

            $resolvedIsRef = $scope->institutionSemesterReference;
        }

        if ($scope->operationalPeriodReference !== null) {
            if ($resolvedIsRef === null) {
                throw new RuntimeException(
                    'An institution-semester reference is required when an operational-period reference is supplied.'
                );
            }

            $periodId = $this->parseRef($scope->operationalPeriodReference, 'operational-period');
            $period = OperationalPeriod::find($periodId);

            if ($period === null) {
                throw new RuntimeException(
                    "Operational-period reference '{$scope->operationalPeriodReference}' could not be resolved."
                );
            }

            $isId = $this->parseRef($resolvedIsRef, 'institution-semester');

            if ((int) $period->institution_semester_id !== $isId) {
                throw new RuntimeException(
                    'The operational period does not belong to the claimed institution semester.'
                );
            }

            $resolvedPeriodRef = $scope->operationalPeriodReference;
        }

        return new ResolvedOperationalScope(
            institutionReference: $resolvedInstitutionRef,
            institutionSemesterReference: $resolvedIsRef,
            operationalPeriodReference: $resolvedPeriodRef,
        );
    }

    /**
     * Parse an opaque string reference to an integer ID.
     *
     * References are string-cast integer primary keys. Non-numeric values fail closed.
     */
    private function parseRef(string $ref, string $label): int
    {
        if (! ctype_digit(ltrim($ref, '-')) || (int) $ref <= 0) {
            throw new RuntimeException(
                "Invalid {$label} reference: expected a positive integer string, got '{$ref}'."
            );
        }

        return (int) $ref;
    }
}

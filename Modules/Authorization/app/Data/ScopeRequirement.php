<?php

declare(strict_types=1);

namespace Modules\Authorization\Data;

enum ScopeRequirement: string
{
    case None = 'none';
    case Institution = 'institution';
    case InstitutionSemester = 'institution_semester';
    case OperationalPeriod = 'operational_period';

    public function isSatisfiedBy(AuthorizedOperationalScope $scope): bool
    {
        return match ($this) {
            self::None => true,
            self::Institution => $scope->institutionReference !== null,
            self::InstitutionSemester => $scope->institutionSemesterReference !== null,
            self::OperationalPeriod => $scope->operationalPeriodReference !== null,
        };
    }
}

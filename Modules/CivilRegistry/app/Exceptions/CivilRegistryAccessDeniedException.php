<?php

declare(strict_types=1);

namespace Modules\CivilRegistry\Exceptions;

use RuntimeException;

/**
 * Thrown when a caller attempts a civil-registry lookup without holding
 * the civil_registry.lookup permission.
 */
final class CivilRegistryAccessDeniedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Permission denied: civil_registry.lookup is required for this operation.');
    }
}

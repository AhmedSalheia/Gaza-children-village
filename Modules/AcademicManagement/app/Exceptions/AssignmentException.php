<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Exceptions;

/**
 * Thrown when a teaching or homeroom assignment mutation is denied by a domain rule.
 */
final class AssignmentException extends \RuntimeException {}

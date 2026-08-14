<?php

declare(strict_types=1);

namespace Modules\Staff\Exceptions;

use RuntimeException;

/**
 * Thrown when a position mutation is attempted against a closed or archived
 * institution semester, or when a period link is attempted on a non-academic
 * (institution-semester-free) position.
 */
final class PositionMutationDeniedException extends RuntimeException {}

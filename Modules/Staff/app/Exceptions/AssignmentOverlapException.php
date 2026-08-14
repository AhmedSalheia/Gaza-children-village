<?php

declare(strict_types=1);

namespace Modules\Staff\Exceptions;

use RuntimeException;

/**
 * Thrown when a proposed assignment would overlap with an existing one.
 *
 * A staff member may work at only one institution on any calendar date.
 * Callers must surface a clear human-readable error rather than exposing
 * internal date values in user-facing messages.
 */
final class AssignmentOverlapException extends RuntimeException {}

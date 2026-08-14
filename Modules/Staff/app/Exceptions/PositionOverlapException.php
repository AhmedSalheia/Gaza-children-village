<?php

declare(strict_types=1);

namespace Modules\Staff\Exceptions;

use RuntimeException;

/**
 * Thrown when a position assignment would create an illegal overlap.
 *
 * Covers two rules:
 *  1. Duplicate overlapping same-definition positions for the same staff member
 *     at the same institution/semester.
 *  2. Principal ↔ deputy_principal mutual-exclusion overlap.
 */
final class PositionOverlapException extends RuntimeException {}

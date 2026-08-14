<?php

declare(strict_types=1);

namespace Modules\People\Exceptions;

use RuntimeException;

/**
 * Thrown when a normalized identifier fingerprint collides with an existing record.
 *
 * Callers must route to human review. Automatic merging is never permitted.
 * The exception message must not contain raw identifier values.
 */
final class IdentifierCollisionException extends RuntimeException {}

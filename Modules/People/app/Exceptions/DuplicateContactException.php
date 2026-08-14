<?php

declare(strict_types=1);

namespace Modules\People\Exceptions;

use RuntimeException;

/**
 * Thrown when a duplicate active contact point would be created for the same
 * Person/type/value combination.
 *
 * The exception message must not contain raw contact values.
 */
final class DuplicateContactException extends RuntimeException {}

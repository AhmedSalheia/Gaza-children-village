<?php

declare(strict_types=1);

namespace Modules\Workflow\Exceptions;

use RuntimeException;

/**
 * Thrown when an electronic approval cannot be recorded or is invalid.
 */
final class ElectronicApprovalException extends RuntimeException {}

<?php

declare(strict_types=1);

namespace Modules\Workflow\Exceptions;

use RuntimeException;

/**
 * Thrown when a workflow transition is invalid or cannot proceed.
 */
final class WorkflowException extends RuntimeException {}

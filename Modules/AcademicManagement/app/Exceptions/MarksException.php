<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Exceptions;

/**
 * Thrown when a marks domain rule is violated.
 *
 * Examples:
 *   - Score exceeds assessment max_score
 *   - Mark sheet is not in an editable state
 *   - Teacher has no teaching assignment for this class/subject
 *   - Mark window is closed
 *   - Incorrect role for verification/approval
 */
final class MarksException extends \RuntimeException {}

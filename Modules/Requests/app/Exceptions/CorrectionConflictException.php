<?php

declare(strict_types=1);

namespace Modules\Requests\Exceptions;

/**
 * Thrown when a correction's official value changed between submission and apply.
 *
 * The request is already flagged with conflict_flag = true when this exception is thrown.
 * The caller (Livewire component or console command) should surface this to the reviewer
 * so a human can decide whether to re-approve with the updated value or cancel.
 */
final class CorrectionConflictException extends \RuntimeException {}

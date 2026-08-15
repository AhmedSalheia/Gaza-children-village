<?php

declare(strict_types=1);

namespace Modules\Attendance\Exceptions;

use RuntimeException;

/**
 * Domain exception for the Attendance module.
 *
 * Thrown by domain actions when business rules are violated.
 * Callers (Livewire components, tests) catch this to surface user-friendly
 * messages rather than letting generic exceptions bubble.
 */
final class AttendanceException extends RuntimeException {}

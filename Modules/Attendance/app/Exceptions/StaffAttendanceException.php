<?php

declare(strict_types=1);

namespace Modules\Attendance\Exceptions;

/**
 * Domain exception for staff attendance operations.
 *
 * Thrown by domain actions when a business rule is violated.
 * Controllers and Livewire components catch this and surface the message to the UI.
 */
final class StaffAttendanceException extends \RuntimeException {}

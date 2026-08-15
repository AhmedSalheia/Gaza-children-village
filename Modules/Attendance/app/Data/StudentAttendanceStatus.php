<?php

declare(strict_types=1);

namespace Modules\Attendance\Data;

/**
 * Code-owned catalogue of student attendance status codes.
 *
 * No DB enum — statuses live here as PHP constants so they can be referenced
 * type-safely across the Attendance module without a migration when new
 * statuses are introduced.
 *
 * Behaviour flags per status:
 *   requires_reason          — free-text reason must be supplied when saving.
 *   allows_arrival_time      — arrived_at field is meaningful and may be stored.
 *   allows_departure_time    — departed_at field is meaningful and may be stored.
 *   counts_as_present        — included in present-count statistics.
 *   guardian_publishable     — visible to the linked guardian in the portal.
 */
final class StudentAttendanceStatus
{
    // ── Status codes ────────────────────────────────────────────────────────

    public const PRESENT         = 'present';
    public const ABSENT          = 'absent';
    public const EXCUSED_ABSENCE = 'excused_absence';
    public const LATE            = 'late';
    public const LEFT_EARLY      = 'left_early';

    // ── Metadata catalogue ──────────────────────────────────────────────────

    /**
     * Full metadata for every known status.
     *
     * @return array<string, array{
     *     label_ar: string,
     *     label_en: string,
     *     requires_reason: bool,
     *     allows_arrival_time: bool,
     *     allows_departure_time: bool,
     *     counts_as_present: bool,
     *     guardian_publishable: bool,
     * }>
     */
    public static function catalogue(): array
    {
        return [
            self::PRESENT => [
                'label_ar'              => 'حاضر',
                'label_en'              => 'Present',
                'requires_reason'       => false,
                'allows_arrival_time'   => false,
                'allows_departure_time' => false,
                'counts_as_present'     => true,
                'guardian_publishable'  => true,
            ],
            self::ABSENT => [
                'label_ar'              => 'غائب',
                'label_en'              => 'Absent',
                'requires_reason'       => false,
                'allows_arrival_time'   => false,
                'allows_departure_time' => false,
                'counts_as_present'     => false,
                'guardian_publishable'  => true,
            ],
            self::EXCUSED_ABSENCE => [
                'label_ar'              => 'غياب بعذر',
                'label_en'              => 'Excused Absence',
                'requires_reason'       => true,
                'allows_arrival_time'   => false,
                'allows_departure_time' => false,
                'counts_as_present'     => false,
                'guardian_publishable'  => true,
            ],
            self::LATE => [
                'label_ar'              => 'متأخر',
                'label_en'              => 'Late',
                'requires_reason'       => false,
                'allows_arrival_time'   => true,
                'allows_departure_time' => false,
                'counts_as_present'     => true,
                'guardian_publishable'  => true,
            ],
            self::LEFT_EARLY => [
                'label_ar'              => 'خرج مبكراً',
                'label_en'              => 'Left Early',
                'requires_reason'       => false,
                'allows_arrival_time'   => false,
                'allows_departure_time' => true,
                'counts_as_present'     => true,
                'guardian_publishable'  => true,
            ],
        ];
    }

    /** All valid status code strings. */
    public static function codes(): array
    {
        return array_keys(self::catalogue());
    }

    /** Whether the given code is a valid status code. */
    public static function isValid(string $code): bool
    {
        return array_key_exists($code, self::catalogue());
    }

    /** Metadata for a single status code. Throws on unknown code. */
    public static function meta(string $code): array
    {
        return self::catalogue()[$code]
            ?? throw new \InvalidArgumentException("Unknown attendance status code: '{$code}'.");
    }

    /** Whether the status requires a reason to be supplied. */
    public static function requiresReason(string $code): bool
    {
        return self::meta($code)['requires_reason'];
    }

    /** Whether arrived_at is meaningful for this status. */
    public static function allowsArrivalTime(string $code): bool
    {
        return self::meta($code)['allows_arrival_time'];
    }

    /** Whether departed_at is meaningful for this status. */
    public static function allowsDepartureTime(string $code): bool
    {
        return self::meta($code)['allows_departure_time'];
    }
}

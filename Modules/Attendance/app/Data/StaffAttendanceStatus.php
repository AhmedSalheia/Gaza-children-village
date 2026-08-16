<?php

declare(strict_types=1);

namespace Modules\Attendance\Data;

/**
 * Code-owned catalogue of staff attendance status codes.
 *
 * No DB enum — statuses live here as PHP constants so they can be referenced
 * type-safely across the Attendance module without a migration when new
 * statuses are introduced.
 *
 * Behaviour flags per status:
 *   requires_reason          — free-text reason must be supplied when saving.
 *   allows_arrival_time      — confirmed_arrived_at is meaningful and may be stored.
 *   allows_departure_time    — confirmed_departed_at is meaningful and may be stored.
 *   counts_as_present        — included in present-count statistics.
 */
final class StaffAttendanceStatus
{
    // ── Status codes ────────────────────────────────────────────────────────

    public const PRESENT = 'present';

    public const ABSENT = 'absent';

    public const EXCUSED = 'excused_absence';

    public const LATE = 'late';

    public const LEAVE = 'leave';

    public const OFFICIAL_DUTY = 'official_duty';

    // ── Metadata catalogue ──────────────────────────────────────────────────

    /**
     * @return array<string, array{
     *     label_ar: string,
     *     label_en: string,
     *     requires_reason: bool,
     *     allows_arrival_time: bool,
     *     allows_departure_time: bool,
     *     counts_as_present: bool,
     * }>
     */
    public static function catalogue(): array
    {
        return [
            self::PRESENT => [
                'label_ar' => 'حاضر',
                'label_en' => 'Present',
                'requires_reason' => false,
                'allows_arrival_time' => false,
                'allows_departure_time' => false,
                'counts_as_present' => true,
            ],
            self::ABSENT => [
                'label_ar' => 'غائب',
                'label_en' => 'Absent',
                'requires_reason' => false,
                'allows_arrival_time' => false,
                'allows_departure_time' => false,
                'counts_as_present' => false,
            ],
            self::EXCUSED => [
                'label_ar' => 'غياب بعذر',
                'label_en' => 'Excused Absence',
                'requires_reason' => true,
                'allows_arrival_time' => false,
                'allows_departure_time' => false,
                'counts_as_present' => false,
            ],
            self::LATE => [
                'label_ar' => 'متأخر',
                'label_en' => 'Late',
                'requires_reason' => false,
                'allows_arrival_time' => true,
                'allows_departure_time' => false,
                'counts_as_present' => true,
            ],
            self::LEAVE => [
                'label_ar' => 'إجازة',
                'label_en' => 'Leave',
                'requires_reason' => true,
                'allows_arrival_time' => false,
                'allows_departure_time' => false,
                'counts_as_present' => false,
            ],
            self::OFFICIAL_DUTY => [
                'label_ar' => 'مهمة رسمية',
                'label_en' => 'Official Duty',
                'requires_reason' => true,
                'allows_arrival_time' => false,
                'allows_departure_time' => false,
                'counts_as_present' => true,
            ],
        ];
    }

    public static function codes(): array
    {
        return array_keys(self::catalogue());
    }

    public static function isValid(string $code): bool
    {
        return array_key_exists($code, self::catalogue());
    }

    public static function meta(string $code): array
    {
        return self::catalogue()[$code]
            ?? throw new \InvalidArgumentException("Unknown staff attendance status code: '{$code}'.");
    }

    public static function requiresReason(string $code): bool
    {
        return self::meta($code)['requires_reason'];
    }

    public static function allowsArrivalTime(string $code): bool
    {
        return self::meta($code)['allows_arrival_time'];
    }

    public static function allowsDepartureTime(string $code): bool
    {
        return self::meta($code)['allows_departure_time'];
    }
}

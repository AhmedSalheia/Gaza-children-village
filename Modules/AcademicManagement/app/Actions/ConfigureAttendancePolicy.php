<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Modules\AcademicManagement\Models\AttendancePublicationPolicy;

/**
 * Create or update the attendance publication policy for a semester.
 *
 * Idempotent: creates the row on first call, updates on subsequent calls.
 * institution_semester_id is a plain cross-module integer (no DB FK).
 */
final class ConfigureAttendancePolicy
{
    public function __invoke(
        int $institutionSemesterId,
        bool $enabled,
        string $detailLevel = 'summary_only',
        int $publishDelayDays = 0,
        bool $showReason = false,
        bool $showArrivalDeparture = false,
    ): AttendancePublicationPolicy {
        /** @var AttendancePublicationPolicy $policy */
        $policy = AttendancePublicationPolicy::firstOrNew([
            'institution_semester_id' => $institutionSemesterId,
        ]);

        $policy->enabled = $enabled;
        $policy->detail_level = $detailLevel;
        $policy->publish_delay_days = max(0, $publishDelayDays);
        $policy->show_reason = $showReason;
        $policy->show_arrival_departure = $showArrivalDeparture;
        $policy->save();

        return $policy;
    }
}

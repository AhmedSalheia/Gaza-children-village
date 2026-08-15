<?php

declare(strict_types=1);

namespace Modules\Students\Actions;

use Modules\Students\Enums\DisplacementStatus;
use Modules\Students\Enums\OrphanStatus;
use Modules\Students\Exceptions\InvalidLifecycleTransitionException;
use Modules\Students\Models\StudentProfile;

/**
 * Correct mutable welfare and registration fields on a StudentProfile.
 *
 * Only correctable fields are accepted. Immutable fields (student_code,
 * person_id, lifecycle_status) are not accepted here.
 *
 * A reason is required: it is recorded in the audit trail and carried in
 * history_metadata on related records. Corrections are NOT allowed on
 * graduated or deceased profiles.
 *
 * This action intentionally does NOT call the Audit module directly;
 * the calling layer is responsible for audit emission if needed.
 */
final class CorrectStudentData
{
    public function __invoke(
        StudentProfile $profile,
        string $actorReference,
        string $reason,
        ?OrphanStatus $orphanStatus = null,
        ?DisplacementStatus $displacementStatus = null,
        ?string $displacementLocation = null,
        ?int $familyMemberCount = null,
        ?int $familyOrder = null,
        ?bool $accessibilityIndicator = null,
        ?\DateTimeInterface $registeredOn = null,
    ): StudentProfile {
        if ($profile->lifecycle_status->isTerminal()) {
            throw new InvalidLifecycleTransitionException(
                "Cannot correct data on a {$profile->lifecycle_status->value} StudentProfile."
            );
        }

        if ($reason === '') {
            throw new \InvalidArgumentException('A correction reason is required.');
        }

        if ($orphanStatus !== null) {
            $profile->orphan_status = $orphanStatus->value;
        }

        if ($displacementStatus !== null) {
            $profile->displacement_status = $displacementStatus->value;
        }

        if ($displacementLocation !== null) {
            $profile->displacement_location = $displacementLocation;
        }

        if ($familyMemberCount !== null) {
            $profile->family_member_count = $familyMemberCount;
        }

        if ($familyOrder !== null) {
            $profile->family_order = $familyOrder;
        }

        if ($accessibilityIndicator !== null) {
            $profile->accessibility_indicator = $accessibilityIndicator;
        }

        if ($registeredOn !== null) {
            $profile->registered_on = $registeredOn->format('Y-m-d');
        }

        $profile->save();

        return $profile;
    }
}

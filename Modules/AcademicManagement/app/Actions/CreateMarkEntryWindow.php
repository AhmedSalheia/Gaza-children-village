<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Illuminate\Support\Facades\DB;
use Modules\AcademicManagement\Enums\MarkWindowStatus;
use Modules\AcademicManagement\Exceptions\MarksException;
use Modules\AcademicManagement\Models\MarkEntryWindow;

/**
 * Create a new mark-entry window for an institution semester.
 *
 * Enforced rules:
 *  1. opens_at must be before closes_at.
 *  2. New windows always start in 'scheduled' status.
 */
final class CreateMarkEntryWindow
{
    public function __invoke(
        int $institutionSemesterId,
        \DateTimeInterface $opensAt,
        \DateTimeInterface $closesAt,
        ?int $classGroupId = null,
        ?int $subjectOfferingId = null,
        ?string $nameAr = null,
        ?string $nameEn = null,
        ?int $createdByStaffPositionId = null,
    ): MarkEntryWindow {
        if ($opensAt >= $closesAt) {
            throw new MarksException('opens_at must be before closes_at.');
        }

        // Validate class group belongs to the given semester (if provided)
        if ($classGroupId !== null) {
            $belongs = DB::table('class_groups')
                ->where('id', $classGroupId)
                ->where('institution_semester_id', $institutionSemesterId)
                ->exists();

            if (! $belongs) {
                throw new MarksException(
                    "Class group #{$classGroupId} does not belong to institution semester #{$institutionSemesterId}."
                );
            }
        }

        // Validate subject offering belongs to the given semester (if provided)
        if ($subjectOfferingId !== null) {
            $belongs = DB::table('institution_subject_offerings')
                ->where('id', $subjectOfferingId)
                ->where('institution_semester_id', $institutionSemesterId)
                ->exists();

            if (! $belongs) {
                throw new MarksException(
                    "Subject offering #{$subjectOfferingId} does not belong to institution semester #{$institutionSemesterId}."
                );
            }
        }

        $window = new MarkEntryWindow;
        $window->institution_semester_id = $institutionSemesterId;
        $window->class_group_id = $classGroupId;
        $window->subject_offering_id = $subjectOfferingId;
        $window->name_ar = $nameAr;
        $window->name_en = $nameEn;
        $window->opens_at = $opensAt->format('Y-m-d H:i:s');
        $window->closes_at = $closesAt->format('Y-m-d H:i:s');
        $window->status = MarkWindowStatus::Scheduled->value;
        $window->extension_history = null;
        $window->created_by_staff_position_id = $createdByStaffPositionId;
        $window->save();

        return $window;
    }
}

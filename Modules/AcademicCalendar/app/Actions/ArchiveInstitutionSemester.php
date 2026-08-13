<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Actions;

use Modules\AcademicCalendar\Enums\AcademicStatus;
use Modules\AcademicCalendar\Models\InstitutionSemester;
use RuntimeException;

/**
 * Archive an institution semester.
 *
 * Two valid source transitions:
 *
 *   draft → archived  Abandon an unused preparation; requires a non-empty reason.
 *   closed → archived Requires the parent global semester to be Closed or Archived.
 *
 * Archived is a terminal status. Archived records remain readable indefinitely.
 *
 * Authorization boundary: this action does not check actor permissions.
 * Future callers must pass through the F17/F19 policy kernel.
 */
final readonly class ArchiveInstitutionSemester
{
    public function execute(InstitutionSemester $is, string $reason = ''): InstitutionSemester
    {
        match ($is->status) {
            AcademicStatus::Draft => $this->archiveFromDraft($is, $reason),
            AcademicStatus::Closed => $this->archiveFromClosed($is),
            default => throw new RuntimeException(
                "Cannot archive an institution semester with status '{$is->status->value}'. Only Draft and Closed institution semesters may be archived."
            ),
        };

        $is->status = AcademicStatus::Archived;
        $is->save();

        return $is;
    }

    private function archiveFromDraft(InstitutionSemester $is, string $reason): void
    {
        if (trim($reason) === '') {
            throw new RuntimeException(
                'A non-empty reason is required to archive a draft institution semester.'
            );
        }
    }

    private function archiveFromClosed(InstitutionSemester $is): void
    {
        // Fresh query to avoid stale relationship cache.
        $semester = $is->semester()->first();

        $parentAllowsArchive = $semester->status === AcademicStatus::Closed
            || $semester->status === AcademicStatus::Archived;

        if (! $parentAllowsArchive) {
            throw new RuntimeException(
                "Cannot archive institution semester: the parent global semester '{$semester->code}' must be closed or archived first (current status: {$semester->status->value})."
            );
        }
    }
}

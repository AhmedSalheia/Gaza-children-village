<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Actions;

use Illuminate\Support\Facades\DB;
use Modules\AcademicCalendar\Enums\AcademicStatus;
use Modules\AcademicCalendar\Models\InstitutionSemester;
use RuntimeException;

/**
 * Transition an institution semester from Closed back to Open.
 *
 * Requires a non-empty reason for audit purposes. F18 will persist the
 * actor-aware audit history.
 *
 * Validates:
 *   - Institution semester is currently Closed.
 *   - A non-empty reason is provided.
 *   - The parent global academic year is still Open.
 *   - The parent global semester is still Open.
 *
 * Inside the transaction:
 *   - Re-verifies no other institution semester for the same institution is Open.
 *
 * Authorization boundary: this action does not check actor permissions.
 * Future callers must pass through the F17/F19 policy kernel.
 */
final readonly class ReopenInstitutionSemester
{
    public function execute(InstitutionSemester $is, string $reason): InstitutionSemester
    {
        if ($is->status !== AcademicStatus::Closed) {
            throw new RuntimeException(
                "Only a closed institution semester can be reopened. Current status: {$is->status->value}."
            );
        }

        if (trim($reason) === '') {
            throw new RuntimeException(
                'A non-empty reason is required to reopen an institution semester.'
            );
        }

        // Fresh queries to bypass stale relationship caches.
        $semester = $is->semester()->first();
        $year = $semester->academicYear()->first();

        if ($year->status !== AcademicStatus::Open) {
            throw new RuntimeException(
                "Cannot reopen institution semester: the parent academic year '{$year->code}' is not open (status: {$year->status->value})."
            );
        }

        if ($semester->status !== AcademicStatus::Open) {
            throw new RuntimeException(
                "Cannot reopen institution semester: the parent global semester '{$semester->code}' is not open (status: {$semester->status->value})."
            );
        }

        return DB::transaction(function () use ($is): InstitutionSemester {
            $alreadyOpen = InstitutionSemester::where('institution_id', $is->institution_id)
                ->where('status', AcademicStatus::Open->value)
                ->where('id', '!=', $is->id)
                ->exists();

            if ($alreadyOpen) {
                throw new RuntimeException(
                    'Another institution semester is already open for this institution. Close it before reopening this one.'
                );
            }

            $is->status = AcademicStatus::Open;
            $is->save();

            return $is;
        });
    }
}

<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Illuminate\Support\Facades\DB;
use Modules\AcademicManagement\Exceptions\MarksException;
use Modules\AcademicManagement\Models\ResultPublication;

/**
 * Publish a versioned result snapshot for a class group.
 *
 * Enforced rules:
 *  1. The class group must have at least one approved mark sheet.
 *  2. All mark sheets for the class group in the semester must be approved
 *     (no draft, submitted, verified, or returned sheets remain outstanding),
 *     UNLESS $requireAllApproved = false (manual override for partial publication).
 *  3. Results are calculated from approved sheets only (CalculateResults).
 *  4. Existing non-revoked publication for the same class group is superseded:
 *     its superseded_by_id is set to the new publication's ID.
 *  5. The publication itself is immutable once written.
 *
 * @throws MarksException
 */
final class PublishResults
{
    public function __construct(private readonly CalculateResults $calculateResults) {}

    public function __invoke(
        int $institutionSemesterId,
        int $classGroupId,
        int $publisherStaffProfileId,
        bool $requireAllApproved = true,
    ): ResultPublication {
        return DB::transaction(function () use (
            $institutionSemesterId, $classGroupId, $publisherStaffProfileId, $requireAllApproved
        ): ResultPublication {
            // 1. Check approved sheets exist
            $approvedCount = DB::table('mark_sheets')
                ->where('institution_semester_id', $institutionSemesterId)
                ->where('class_group_id', $classGroupId)
                ->where('status', 'approved')
                ->count();

            if ($approvedCount === 0) {
                throw new MarksException(
                    'No approved mark sheets found for class group #'.$classGroupId.
                    ' in semester #'.$institutionSemesterId.'.'
                );
            }

            // 2. Check for outstanding (non-approved, non-superseded) sheets
            if ($requireAllApproved) {
                $outstandingCount = DB::table('mark_sheets')
                    ->where('institution_semester_id', $institutionSemesterId)
                    ->where('class_group_id', $classGroupId)
                    ->whereNotIn('status', ['approved', 'superseded'])
                    ->count();

                if ($outstandingCount > 0) {
                    throw new MarksException(
                        "Cannot publish: {$outstandingCount} mark sheet(s) for class group #".
                        $classGroupId.' are not yet approved.'
                    );
                }
            }

            // 3. Calculate results
            $calculated = ($this->calculateResults)($institutionSemesterId, $classGroupId);

            if ($calculated->isEmpty()) {
                throw new MarksException(
                    'Result calculation returned no rows for class group #'.$classGroupId.
                    '. Ensure students are enrolled and assessments are defined.'
                );
            }

            // 4. Determine version number
            $lastVersion = DB::table('result_publications')
                ->where('institution_semester_id', $institutionSemesterId)
                ->where('class_group_id', $classGroupId)
                ->max('version') ?? 0;

            // 5. Create publication header
            $pub = new ResultPublication;
            $pub->institution_semester_id    = $institutionSemesterId;
            $pub->class_group_id             = $classGroupId;
            $pub->version                    = $lastVersion + 1;
            $pub->status                     = 'published';
            $pub->published_at               = now();
            $pub->publisher_staff_profile_id = $publisherStaffProfileId;
            $pub->save();

            // 6. Supersede the previous published version (if any)
            DB::table('result_publications')
                ->where('institution_semester_id', $institutionSemesterId)
                ->where('class_group_id', $classGroupId)
                ->where('id', '!=', $pub->id)
                ->where('status', 'published')
                ->whereNull('superseded_by_id')
                ->update(['superseded_by_id' => $pub->id]);

            // 7. Write immutable result rows in chunks
            $now  = now()->toDateTimeString();
            $rows = $calculated->map(fn ($item) => [
                'result_publication_id' => $pub->id,
                'student_profile_id'    => $item->student_profile_id,
                'enrollment_id'         => $item->enrollment_id,
                'subject_offering_id'   => $item->subject_offering_id,
                'raw_total_score'       => $item->raw_total_score,
                'raw_max_possible'      => $item->raw_max_possible,
                'normalized_score'      => $item->normalized_score,
                'grade_code'            => $item->grade_code,
                'grade_name_ar'         => $item->grade_name_ar,
                'is_passing'            => $item->is_passing,
                'completeness_status'   => $item->completeness_status,
                'created_at'            => $now,
                'updated_at'            => $now,
            ])->all();

            foreach (array_chunk($rows, 200) as $chunk) {
                DB::table('result_publication_rows')->insert($chunk);
            }

            return $pub->fresh();
        });
    }
}

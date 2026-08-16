<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Pure result-calculation service.
 *
 * For every (student_enrollment, subject_offering) pair in the given class group
 * and semester, computes a normalized score, grading-scale result, and completeness
 * status from the APPROVED mark sheets only.
 *
 * Side-effect free — returns a Collection of value objects; does not write
 * anything to the database.
 *
 * Calculation rules:
 *  1. Only APPROVED mark sheets are considered (status = 'approved').
 *  2. For each assessment_definition with weight > 0:
 *       - If the student_mark has a numeric score: contribution = (score / max_score) × weight
 *       - If the mark has an exception_status (absent/exempt/medical): excluded from
 *         the weighted average but counted in completeness.
 *       - If the mark is a null placeholder (no score, no exception): the assessment
 *         is considered MISSING → completeness = incomplete.
 *  3. normalized_score = sum(contributions) / sum(weights_used) × 100
 *       where weights_used excludes assessments with exceptions.
 *       If no weighted assessments have scores → score is null.
 *  4. Grading scale lookup uses mark_sheets.grading_scale_id (sheet-level scale).
 *       If no scale is attached, grade fields are null.
 *  5. completeness_status values:
 *       complete       — all assessment marks are present (score or exception)
 *       incomplete     — at least one assessment has neither score nor exception
 *       all_absent     — all assessments have exceptions (no numeric scores)
 *       no_assessments — no matching assessment definitions exist
 *
 * @return Collection<int, object> Each item has:
 *                                 enrollment_id, student_profile_id, subject_offering_id,
 *                                 raw_total_score, raw_max_possible, normalized_score,
 *                                 grade_code, grade_name_ar, is_passing, completeness_status
 */
final class CalculateResults
{
    public function __invoke(int $institutionSemesterId, int $classGroupId): Collection
    {
        // 1. Load all approved mark sheets for this class group
        $sheets = DB::table('mark_sheets')
            ->where('institution_semester_id', $institutionSemesterId)
            ->where('class_group_id', $classGroupId)
            ->where('status', 'approved')
            ->get(['id', 'subject_offering_id', 'grading_scale_id']);

        if ($sheets->isEmpty()) {
            return collect();
        }

        // 2. Load grading scales keyed by ID (with their grade tiers)
        $scaleIds = $sheets->pluck('grading_scale_id')->filter()->unique()->values();
        $scales = $scaleIds->isEmpty() ? collect() : $this->loadScales($scaleIds->all());

        // 3. Load active enrollments for this class group
        $enrollments = DB::table('student_enrollments')
            ->where('class_group_id', $classGroupId)
            ->where('institution_semester_id', $institutionSemesterId)
            ->where('enrollment_status', 'active')
            ->get(['id as enrollment_id', 'student_profile_id']);

        if ($enrollments->isEmpty()) {
            return collect();
        }

        $results = collect();

        foreach ($sheets as $sheet) {
            // 4. Load assessment definitions for this sheet's context
            $definitions = DB::table('assessment_definitions')
                ->where('institution_semester_id', $institutionSemesterId)
                ->where('status', 'active')
                ->where(function ($q) use ($sheet, $classGroupId): void {
                    $q->where(function ($inner) use ($sheet, $classGroupId): void {
                        $inner->where('class_group_id', $classGroupId)
                            ->where('subject_offering_id', $sheet->subject_offering_id);
                    })->orWhere(function ($inner) use ($sheet): void {
                        $inner->whereNull('class_group_id')
                            ->where('subject_offering_id', $sheet->subject_offering_id);
                    })->orWhere(function ($inner) use ($classGroupId): void {
                        $inner->where('class_group_id', $classGroupId)
                            ->whereNull('subject_offering_id');
                    })->orWhere(function ($inner): void {
                        $inner->whereNull('class_group_id')
                            ->whereNull('subject_offering_id');
                    });
                })
                ->orderBy('id')
                ->get(['id', 'max_score', 'weight']);

            if ($definitions->isEmpty()) {
                foreach ($enrollments as $enrollment) {
                    $results->push((object) [
                        'enrollment_id' => (int) $enrollment->enrollment_id,
                        'student_profile_id' => (int) $enrollment->student_profile_id,
                        'subject_offering_id' => (int) $sheet->subject_offering_id,
                        'raw_total_score' => null,
                        'raw_max_possible' => null,
                        'normalized_score' => null,
                        'grade_code' => null,
                        'grade_name_ar' => null,
                        'is_passing' => null,
                        'completeness_status' => 'no_assessments',
                    ]);
                }

                continue;
            }

            // 5. Load all effective student marks for this sheet.
            //
            //    CorrectMark preserves the original row and creates a replacement
            //    with correction_of_id = original_id. The "effective" mark for
            //    each (enrollment, assessment) position is the leaf node in the
            //    correction chain — the row whose ID does NOT appear as any
            //    other row's correction_of_id. Using whereNull('correction_of_id')
            //    would return original (potentially superseded) rows instead.
            $marks = DB::table('student_marks as sm')
                ->where('sm.mark_sheet_id', $sheet->id)
                ->whereNotExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('student_marks as sm2')
                        ->whereColumn('sm2.correction_of_id', 'sm.id');
                })
                ->get(['sm.enrollment_id', 'sm.assessment_definition_id', 'sm.score', 'sm.exception_status']);

            // Index marks: enrollment_id → assessment_definition_id → mark
            $markIndex = [];
            foreach ($marks as $mark) {
                $markIndex[(int) $mark->enrollment_id][(int) $mark->assessment_definition_id] = $mark;
            }

            // 6. Grading scale for this sheet
            $scale = $sheet->grading_scale_id ? ($scales[$sheet->grading_scale_id] ?? null) : null;

            foreach ($enrollments as $enrollment) {
                $eid = (int) $enrollment->enrollment_id;

                $totalWeightedScore = 0.0;
                $totalWeightUsed = 0.0;
                $maxPossible = 0.0;
                $missingCount = 0;
                $exceptionCount = 0;
                $scoredCount = 0;

                foreach ($definitions as $def) {
                    $did = (int) $def->id;
                    $mark = $markIndex[$eid][$did] ?? null;
                    $w = (float) $def->weight;
                    $ms = (float) $def->max_score;

                    $maxPossible += $w;

                    if ($mark === null || ($mark->score === null && $mark->exception_status === null)) {
                        // Missing placeholder
                        $missingCount++;
                    } elseif ($mark->exception_status !== null) {
                        // Absence/exempt/medical — excluded from score calc
                        $exceptionCount++;
                    } else {
                        // Numeric score
                        $score = (float) $mark->score;
                        $contribution = $ms > 0 ? ($score / $ms) * $w : 0.0;
                        $totalWeightedScore += $contribution;
                        $totalWeightUsed += $w;
                        $scoredCount++;
                    }
                }

                // Determine completeness
                $totalCount = count($definitions);
                if ($missingCount > 0) {
                    $completeness = 'incomplete';
                } elseif ($scoredCount === 0) {
                    $completeness = 'all_absent';
                } else {
                    $completeness = 'complete';
                }

                // Normalized score
                $normalizedScore = null;
                if ($totalWeightUsed > 0) {
                    $normalizedScore = round(($totalWeightedScore / $totalWeightUsed) * 100, 2);
                }

                // Grade lookup
                $gradeCode = null;
                $gradeNameAr = null;
                $isPassing = null;

                if ($scale !== null && $normalizedScore !== null) {
                    $grade = $this->lookupGrade($scale, $normalizedScore);
                    if ($grade !== null) {
                        $gradeCode = $grade->code;
                        $gradeNameAr = $grade->name_ar;
                        $isPassing = (bool) $grade->is_passing;
                    }
                }

                $results->push((object) [
                    'enrollment_id' => $eid,
                    'student_profile_id' => (int) $enrollment->student_profile_id,
                    'subject_offering_id' => (int) $sheet->subject_offering_id,
                    'raw_total_score' => $totalWeightUsed > 0 ? round($totalWeightedScore, 4) : null,
                    'raw_max_possible' => $maxPossible > 0 ? round($maxPossible, 4) : null,
                    'normalized_score' => $normalizedScore,
                    'grade_code' => $gradeCode,
                    'grade_name_ar' => $gradeNameAr,
                    'is_passing' => $isPassing,
                    'completeness_status' => $completeness,
                ]);
            }
        }

        return $results;
    }

    /**
     * Load grading scales with their grade tiers, keyed by scale ID.
     *
     * @param  list<int>  $scaleIds
     * @return array<int, object>
     */
    private function loadScales(array $scaleIds): array
    {
        $scales = DB::table('grading_scales')
            ->whereIn('id', $scaleIds)
            ->get(['id', 'name_ar'])
            ->keyBy('id')
            ->all();

        $grades = DB::table('grading_scale_grades')
            ->whereIn('grading_scale_id', $scaleIds)
            ->orderBy('grading_scale_id')
            ->orderBy('sequence')
            ->get(['grading_scale_id', 'code', 'name_ar', 'min_score', 'max_score', 'is_passing']);

        $gradesByScale = [];
        foreach ($grades as $grade) {
            $gradesByScale[(int) $grade->grading_scale_id][] = $grade;
        }

        foreach ($scales as $id => $scale) {
            $scale->grades = $gradesByScale[(int) $id] ?? [];
        }

        return $scales;
    }

    /**
     * Find the first grade tier whose range covers the normalized score.
     */
    private function lookupGrade(object $scale, float $normalizedScore): ?object
    {
        foreach ($scale->grades as $grade) {
            if ($normalizedScore >= (float) $grade->min_score &&
                $normalizedScore <= (float) $grade->max_score) {
                return $grade;
            }
        }

        return null;
    }
}

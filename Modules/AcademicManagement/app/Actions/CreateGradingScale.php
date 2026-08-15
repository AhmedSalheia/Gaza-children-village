<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Illuminate\Support\Facades\DB;
use Modules\AcademicManagement\Exceptions\MarksException;
use Modules\AcademicManagement\Models\GradingScale;
use Modules\AcademicManagement\Models\GradingScaleGrade;

/**
 * Create a new GradingScale with its grade tiers in a single transaction.
 *
 * Enforced rules:
 *  1. Code must be unique within the institution.
 *  2. Each grade tier must have min_score <= max_score.
 *  3. No overlapping ranges within the same scale.
 *  4. At least one grade tier must be provided.
 */
final class CreateGradingScale
{
    /**
     * @param  list<array{code: string, name_ar: string, name_en?: string|null, min_score: float, max_score: float, is_passing: bool, sequence: int}>  $grades
     */
    public function __invoke(
        int $institutionId,
        string $code,
        string $nameAr,
        ?string $nameEn,
        array $grades,
    ): GradingScale {
        if (empty($grades)) {
            throw new MarksException('A grading scale must have at least one grade tier.');
        }

        $this->validateGrades($grades);

        return DB::transaction(function () use ($institutionId, $code, $nameAr, $nameEn, $grades): GradingScale {
            $existing = GradingScale::where('institution_id', $institutionId)
                ->where('code', $code)
                ->lockForUpdate()
                ->exists();

            if ($existing) {
                throw new MarksException("A grading scale with code '{$code}' already exists for this institution.");
            }

            $scale = new GradingScale;
            $scale->institution_id = $institutionId;
            $scale->code           = $code;
            $scale->name_ar        = $nameAr;
            $scale->name_en        = $nameEn;
            $scale->is_active      = true;
            $scale->save();

            foreach ($grades as $gradeData) {
                $grade = new GradingScaleGrade;
                $grade->grading_scale_id = $scale->id;
                $grade->code             = $gradeData['code'];
                $grade->name_ar          = $gradeData['name_ar'];
                $grade->name_en          = $gradeData['name_en'] ?? null;
                $grade->min_score        = $gradeData['min_score'];
                $grade->max_score        = $gradeData['max_score'];
                $grade->is_passing       = $gradeData['is_passing'];
                $grade->sequence         = $gradeData['sequence'];
                $grade->save();
            }

            return $scale->load('grades');
        });
    }

    /** @param  list<array{min_score: float, max_score: float, code: string}>  $grades */
    private function validateGrades(array $grades): void
    {
        foreach ($grades as $g) {
            if ($g['min_score'] > $g['max_score']) {
                throw new MarksException(
                    "Grade '{$g['code']}': min_score ({$g['min_score']}) must not exceed max_score ({$g['max_score']})."
                );
            }
        }

        // Check for overlaps
        $sorted = $grades;
        usort($sorted, fn ($a, $b) => $a['min_score'] <=> $b['min_score']);

        for ($i = 0; $i < count($sorted) - 1; $i++) {
            if ($sorted[$i]['max_score'] >= $sorted[$i + 1]['min_score']) {
                throw new MarksException(
                    "Grade ranges overlap: '{$sorted[$i]['code']}' (max {$sorted[$i]['max_score']}) ".
                    "overlaps with '{$sorted[$i + 1]['code']}' (min {$sorted[$i + 1]['min_score']})."
                );
            }
        }
    }
}

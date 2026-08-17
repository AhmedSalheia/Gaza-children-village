<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds student enrollments and promotion proposals for the demo dataset.
 *
 * Enrollment statuses covered: draft, active, withdrawn, transferred, completed.
 * Promotion proposal review statuses: pending, approved, rejected.
 *
 * Idempotent: checks student_profile_id + institution_semester_id before inserting.
 */
final class DemoEnrollmentSeeder extends Seeder
{
    public function run(): void
    {
        $inst1Id = DB::table('institutions')->where('code', 'academy_1')->value('id');

        if ($inst1Id === null) {
            $this->command?->warn('DemoEnrollmentSeeder: academy_1 not found. Skipping.');

            return;
        }

        // Open semester for Academy 1
        $openSemId = DB::table('institution_semesters')
            ->where('institution_id', $inst1Id)
            ->where('status', 'open')
            ->value('id');

        // Historical semester for Academy 1
        $histSemId = DB::table('institution_semesters')
            ->where('institution_id', $inst1Id)
            ->where('status', 'archived')
            ->value('id');

        if ($openSemId === null) {
            $this->command?->warn('DemoEnrollmentSeeder: No open semester for academy_1. Skipping.');

            return;
        }

        // Get class group IDs
        $classGroupIds = DB::table('class_groups')
            ->where('institution_semester_id', $openSemId)
            ->pluck('id', 'code');

        // Active enrollments (10 students in open semester)
        $activeEnrollments = [
            'STU-001' => 'CG-KG1-A',
            'STU-002' => 'CG-KG1-A',
            'STU-003' => 'CG-KG2-A',
            'STU-004' => 'CG-G1-A',
            'STU-005' => 'CG-G1-A',
            'STU-006' => 'CG-G2-A',
            'STU-007' => 'CG-G2-A',
            'STU-008' => 'CG-G3-A',
            'STU-009' => 'CG-G3-A',
            'STU-010' => 'CG-G4-A',
        ];

        foreach ($activeEnrollments as $studentCode => $classGroupCode) {
            $studentId = DB::table('student_profiles')->where('student_code', $studentCode)->value('id');
            $classGroupId = $classGroupIds[$classGroupCode] ?? null;

            if ($studentId === null || $classGroupId === null) {
                continue;
            }

            $this->upsertEnrollment($studentId, $openSemId, $classGroupId, 'active', now()->subWeeks(8)->toDateString(), now()->subWeeks(7)->toDateString());
        }

        // Draft enrollments (2 students)
        foreach (['STU-011', 'STU-012'] as $code) {
            $studentId = DB::table('student_profiles')->where('student_code', $code)->value('id');
            $classGroupId = $classGroupIds['CG-G3-A'] ?? null;

            if ($studentId && $classGroupId) {
                $this->upsertEnrollment($studentId, $openSemId, $classGroupId, 'draft', now()->subWeeks(2)->toDateString(), null);
            }
        }

        // Withdrawn enrollment
        $wdStudentId = DB::table('student_profiles')->where('student_code', 'STU-023')->value('id');
        $classGroupId = $classGroupIds['CG-G2-A'] ?? null;

        if ($wdStudentId && $classGroupId) {
            $this->upsertEnrollment($wdStudentId, $openSemId, $classGroupId, 'withdrawn', now()->subMonths(3)->toDateString(), now()->subMonths(3)->toDateString());
        }

        // Transferred enrollment
        $trStudentId = DB::table('student_profiles')->where('student_code', 'STU-024')->value('id');

        if ($trStudentId && $classGroupId) {
            $this->upsertEnrollment($trStudentId, $openSemId, $classGroupId, 'transferred', now()->subMonths(2)->toDateString(), now()->subMonths(2)->toDateString());
        }

        // Historical enrollments in archived semester (if available)
        if ($histSemId !== null) {
            $histClassGroups = DB::table('class_groups')
                ->where('institution_semester_id', $histSemId)
                ->pluck('id', 'code');

            // STU-031 and STU-032 completed/graduated in historical semester
            foreach (['STU-031' => 'completed', 'STU-032' => 'promoted'] as $code => $status) {
                $studentId = DB::table('student_profiles')->where('student_code', $code)->value('id');
                $cgId = $histClassGroups->first(); // use first available class group

                if ($studentId && $cgId) {
                    $enrollId = $this->upsertEnrollment(
                        $studentId, $histSemId, $cgId, $status,
                        '2024-09-01', '2025-01-31',
                    );

                    // Promotion proposal for STU-031 (approved)
                    if ($code === 'STU-031' && $enrollId && ! empty($classGroupIds)) {
                        $this->upsertProposal($enrollId, 'promoted', $classGroupIds->first(), 'approved');
                    }

                    // Promotion proposal for STU-032 (pending)
                    if ($code === 'STU-032' && $enrollId && ! empty($classGroupIds)) {
                        $this->upsertProposal($enrollId, 'repeating', $classGroupIds->first(), 'pending');
                    }
                }
            }
        }

        // Promotion proposals in open semester — all three review statuses represented here
        // so the test suite can assert them without depending on the historical semester path.
        $openProposals = [
            'STU-007' => ['proposed_status' => 'promoted', 'review_status' => 'approved'],
            'STU-008' => ['proposed_status' => 'promoted', 'review_status' => 'rejected'],
            'STU-009' => ['proposed_status' => 'repeating', 'review_status' => 'pending'],
        ];

        $firstClassGroupId = $classGroupIds->first();

        foreach ($openProposals as $studentCode => $proposal) {
            $studId = DB::table('student_profiles')->where('student_code', $studentCode)->value('id');

            if ($studId === null || $firstClassGroupId === null) {
                continue;
            }

            $enrollId = DB::table('student_enrollments')
                ->where('student_profile_id', $studId)
                ->where('institution_semester_id', $openSemId)
                ->value('id');

            if ($enrollId) {
                $this->upsertProposal($enrollId, $proposal['proposed_status'], $firstClassGroupId, $proposal['review_status']);
            }
        }
    }

    private function upsertEnrollment(
        int $studentId,
        int $semId,
        int $classGroupId,
        string $status,
        string $enrolledOn,
        ?string $activatedOn,
    ): ?int {
        $existing = DB::table('student_enrollments')
            ->where('student_profile_id', $studentId)
            ->where('institution_semester_id', $semId)
            ->first();

        if ($existing) {
            return $existing->id;
        }

        return DB::table('student_enrollments')->insertGetId([
            'student_profile_id' => $studentId,
            'institution_semester_id' => $semId,
            'class_group_id' => $classGroupId,
            'enrollment_status' => $status,
            'enrolled_on' => $enrolledOn,
            'activated_on' => $activatedOn,
            'completed_on' => null,
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function upsertProposal(int $enrollId, string $proposedStatus, int $targetClassGroupId, string $reviewStatus): void
    {
        $exists = DB::table('promotion_proposals')
            ->where('source_enrollment_id', $enrollId)
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('promotion_proposals')->insert([
            'source_enrollment_id' => $enrollId,
            'proposed_status' => $proposedStatus,
            'proposed_class_group_id' => $targetClassGroupId,
            'review_status' => $reviewStatus,
            'reviewed_by' => $reviewStatus !== 'pending' ? 'demo-principal' : null,
            'reviewed_at' => $reviewStatus !== 'pending' ? now()->subDays(5) : null,
            'reason' => match ($reviewStatus) {
                'approved' => 'Student meets all promotion criteria.',
                'rejected' => 'Insufficient attendance record for promotion.',
                default => null,
            },
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

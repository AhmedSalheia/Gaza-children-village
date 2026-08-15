<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\AcademicManagement\Actions\ConfigureAttendancePolicy;
use Modules\AcademicManagement\Actions\PublishAttendanceSnapshot;
use Modules\AcademicManagement\Actions\PublishResults;

/**
 * Seeds published results and attendance snapshots for the demo dataset.
 *
 * Publishes:
 *  - Results for CG-G1-A (all 3 approved mark sheets → full result rows)
 *  - Results for CG-KG1-A (English approved → guardian@gcv.demo can view)
 *  - Attendance snapshot for CG-G1-A
 *
 * Idempotent: the Publish* actions use supersession; re-running just creates
 * a new version but existing data remains visible. We skip if a non-revoked
 * non-superseded publication already exists for the class group.
 *
 * Runs AFTER: DemoMarkSeeder, DemoAttendanceSeeder.
 */
final class DemoPublicationSeeder extends Seeder
{
    public function run(): void
    {
        $inst1Id = (int) DB::table('institutions')->where('code', 'academy_1')->value('id');

        if ($inst1Id === 0) {
            $this->command->warn('DemoPublicationSeeder: academy_1 not found. Skipping.');

            return;
        }

        $instSemId = (int) DB::table('institution_semesters')
            ->where('institution_id', $inst1Id)
            ->where('status', 'open')
            ->value('id');

        if ($instSemId === 0) {
            $this->command->warn('DemoPublicationSeeder: No open semester. Skipping.');

            return;
        }

        $principalId = (int) DB::table('staff_profiles')->where('staff_code', 'STAFF-001')->value('id');

        $cgG1aId  = (int) DB::table('class_groups')->where('code', 'CG-G1-A')->where('institution_semester_id', $instSemId)->value('id');
        $cgKg1aId = (int) DB::table('class_groups')->where('code', 'CG-KG1-A')->where('institution_semester_id', $instSemId)->value('id');

        // ── 1. Publish results for CG-G1-A ───────────────────────────────
        if ($cgG1aId && ! $this->hasPublishedResults($instSemId, $cgG1aId)) {
            try {
                app(PublishResults::class)(
                    institutionSemesterId: $instSemId,
                    classGroupId: $cgG1aId,
                    publisherStaffProfileId: $principalId,
                    requireAllApproved: false, // demo: publish with whatever is approved
                );
                $this->command->info('DemoPublicationSeeder: Published results for CG-G1-A.');
            } catch (\Throwable $e) {
                $this->command->warn('DemoPublicationSeeder: Could not publish G1-A results: '.$e->getMessage());
            }
        }

        // ── 2. Publish results for CG-KG1-A (guardian demo) ──────────────
        if ($cgKg1aId && ! $this->hasPublishedResults($instSemId, $cgKg1aId)) {
            try {
                app(PublishResults::class)(
                    institutionSemesterId: $instSemId,
                    classGroupId: $cgKg1aId,
                    publisherStaffProfileId: $principalId,
                    requireAllApproved: false,
                );
                $this->command->info('DemoPublicationSeeder: Published results for CG-KG1-A.');
            } catch (\Throwable $e) {
                $this->command->warn('DemoPublicationSeeder: Could not publish KG1-A results: '.$e->getMessage());
            }
        }

        // ── 3. Configure attendance publication policy ────────────────────
        if ($cgG1aId) {
            try {
                app(ConfigureAttendancePolicy::class)(
                    institutionSemesterId: $instSemId,
                    enabled: true,
                    detailLevel: 'summary',
                    publishDelayDays: 0,
                    showReason: true,
                    showArrivalDeparture: false,
                );

                // ── 4. Publish attendance snapshot for CG-G1-A ───────────
                if (! $this->hasPublishedAttendance($instSemId, $cgG1aId)) {
                    app(PublishAttendanceSnapshot::class)(
                        institutionSemesterId: $instSemId,
                        classGroupId: $cgG1aId,
                        publisherStaffProfileId: $principalId,
                    );
                    $this->command->info('DemoPublicationSeeder: Published attendance snapshot for CG-G1-A.');
                }
            } catch (\Throwable $e) {
                $this->command->warn('DemoPublicationSeeder: Could not publish attendance snapshot: '.$e->getMessage());
            }
        }
    }

    private function hasPublishedResults(int $instSemId, int $classGroupId): bool
    {
        return DB::table('result_publications')
            ->where('institution_semester_id', $instSemId)
            ->where('class_group_id', $classGroupId)
            ->where('status', 'published')
            ->whereNull('superseded_by_id')
            ->exists();
    }

    private function hasPublishedAttendance(int $instSemId, int $classGroupId): bool
    {
        return DB::table('attendance_publication_snapshots')
            ->where('institution_semester_id', $instSemId)
            ->where('class_group_id', $classGroupId)
            ->where('status', 'published')
            ->whereNull('superseded_by_id')
            ->exists();
    }
}

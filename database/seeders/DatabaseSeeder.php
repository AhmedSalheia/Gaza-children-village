<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\AcademicManagement\Database\Seeders\AcademicLevelReferenceSeeder;
use Modules\Authorization\Database\Seeders\PermissionCatalogueSeeder;
use Modules\Documents\Database\Seeders\DocumentTemplateFamilySeeder;
use Modules\Documents\Database\Seeders\DocumentTypeSeeder;
use Modules\Organization\Database\Seeders\FeatureModuleReferenceSeeder;
use Modules\Organization\Database\Seeders\InstitutionReferenceSeeder;
use Modules\Organization\Database\Seeders\InstitutionTypeFeatureRuleReferenceSeeder;
use Modules\Organization\Database\Seeders\InstitutionTypeReferenceSeeder;
use Modules\Organization\Database\Seeders\OrganizationReferenceSeeder;
use Modules\Reporting\Database\Seeders\ReportDefinitionSeeder;
use Modules\Workflow\Database\Seeders\WorkflowDefinitionSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Dependency order (reference data before demo data):
     *
     *   1. organization           — GCV root organization
     *   2. institution types      — academy, university_space, etc.
     *   3. feature modules        — SR, AM, etc.
     *   4. type feature rules     — required/default/allowed rules per type
     *   5. institutions           — all GCV academies and facilities
     *   6. permission catalogue   — all permission keys + role templates
     *   7. academic levels        — KG1, KG2, Grade1–Grade12
     *   8. subjects               — Arabic, Math, English, etc.
     *   9. calendar               — academic years, semesters, inst-semesters, op-periods
     *  10. academic structure     — classrooms, class groups, subject offerings
     *  11. students               — 32 synthetic student profiles
     *  12. guardians              — guardian profiles + student relationships
     *  13. enrollments            — enrollments + promotion proposals
     *  14. civil registry         — synthetic Gaza civil records
     *  15. import batches         — demo import batches + rows + results
     *  16. staff                  — staff profiles, assignments, positions, role grants
     *  17. accounts               — portal accounts (admin, staff, guardian)
     *                               ⚠️  Refuses to run in production.
     */
    public function run(): void
    {
        // ── Reference data ──────────────────────────────────────────
        $this->call([
            OrganizationReferenceSeeder::class,
            InstitutionTypeReferenceSeeder::class,
            FeatureModuleReferenceSeeder::class,
            InstitutionTypeFeatureRuleReferenceSeeder::class,
            InstitutionReferenceSeeder::class,
            PermissionCatalogueSeeder::class,
            AcademicLevelReferenceSeeder::class,
            DemoSubjectReferenceSeeder::class,
            WorkflowDefinitionSeeder::class,
            DocumentTypeSeeder::class,          // document type catalogue (7 types)
            DocumentTemplateFamilySeeder::class, // org-wide template family records
            ReportDefinitionSeeder::class,      // 21 report family definitions
        ]);

        // ── Demo structural data ─────────────────────────────────────
        $this->call([
            DemoCalendarSeeder::class,
            DemoAcademicStructureSeeder::class,
        ]);

        // ── Demo people / registry ───────────────────────────────────
        $this->call([
            DemoStudentSeeder::class,
            DemoGuardianSeeder::class,
            DemoEnrollmentSeeder::class,
            DemoCivilRegistrySeeder::class,
            DemoImportBatchSeeder::class,
        ]);

        // ── Demo staff and accounts (production-guarded) ─────────────
        $this->call([
            DemoStaffSeeder::class,
        ]);

        // ── Demo operational data (marks, attendance, publications) ───
        $this->call([
            DemoMarkSeeder::class,
            DemoAttendanceSeeder::class,
            DemoPublicationSeeder::class,
        ]);

        // ── Demo accounts must come last (requires staff + guardians) ─
        $this->call([
            DemoAccountSeeder::class,
        ]);
    }
}

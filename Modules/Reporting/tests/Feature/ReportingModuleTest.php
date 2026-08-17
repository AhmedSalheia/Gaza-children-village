<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Reporting\Data\ReportScope;
use Modules\Reporting\Services\FormulaInjectionSanitizer;
use Modules\Reporting\Services\ReportQueryService;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers — raw DB inserts throughout (fillable exclusions + FK chains)
// ---------------------------------------------------------------------------

function reportingSeedDefinitions(): void
{
    $seederClass = 'Modules\\Reporting\\Database\\Seeders\\ReportDefinitionSeeder';
    (new $seederClass)->run();
}

/**
 * Build the minimal FK chain: organization → institution → semester chain →
 * institution_semester → two operational periods → two class groups.
 *
 * @return array{semesterId:int, periodA:int, periodB:int, cgA:int, cgB:int}
 */
function reportingMakeSemesterChain(): array
{
    $orgId = DB::table('organizations')->insertGetId([
        'code' => 'ORG-R', 'name_en' => 'Org', 'name_ar' => 'منظمة', 'is_active' => true,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $typeId = DB::table('institution_types')->insertGetId([
        'code' => 'TYPE-R', 'name_en' => 'School', 'name_ar' => 'مدرسة', 'is_active' => true,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $instId = DB::table('institutions')->insertGetId([
        'organization_id' => $orgId, 'institution_type_id' => $typeId,
        'code' => 'INST-R', 'name_en' => 'Inst', 'name_ar' => 'مؤسسة', 'is_active' => true,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $yearId = DB::table('academic_years')->insertGetId([
        'organization_id' => $orgId, 'code' => 'AY-R', 'name_en' => 'Y', 'name_ar' => 'سنة',
        'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'status' => 'active',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $semId = DB::table('semesters')->insertGetId([
        'academic_year_id' => $yearId, 'code' => 'S1-R', 'name_en' => 'S1', 'name_ar' => 'فصل',
        'sequence' => 1, 'starts_on' => '2026-01-01', 'ends_on' => '2026-06-30', 'status' => 'active',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $isId = DB::table('institution_semesters')->insertGetId([
        'institution_id' => $instId, 'semester_id' => $semId, 'status' => 'open',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $periodA = DB::table('operational_periods')->insertGetId([
        'institution_semester_id' => $isId, 'code' => 'P-A', 'name_en' => 'A', 'name_ar' => 'أ',
        'sequence' => 1, 'starts_at' => '08:00', 'ends_at' => '12:00', 'is_active' => true,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $periodB = DB::table('operational_periods')->insertGetId([
        'institution_semester_id' => $isId, 'code' => 'P-B', 'name_en' => 'B', 'name_ar' => 'ب',
        'sequence' => 2, 'starts_at' => '12:00', 'ends_at' => '16:00', 'is_active' => true,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $levelId = DB::table('academic_levels')->insertGetId([
        'code' => 'L1-R', 'name_en' => 'L1', 'name_ar' => 'مستوى', 'sequence' => 1, 'is_active' => true,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $cgA = DB::table('class_groups')->insertGetId([
        'institution_semester_id' => $isId, 'operational_period_id' => $periodA,
        'academic_level_id' => $levelId, 'code' => 'CG-A', 'name_en' => 'CG A', 'name_ar' => 'شعبة أ',
        'lifecycle_status' => 'active', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $cgB = DB::table('class_groups')->insertGetId([
        'institution_semester_id' => $isId, 'operational_period_id' => $periodB,
        'academic_level_id' => $levelId, 'code' => 'CG-B', 'name_en' => 'CG B', 'name_ar' => 'شعبة ب',
        'lifecycle_status' => 'active', 'created_at' => now(), 'updated_at' => now(),
    ]);

    return ['semesterId' => $isId, 'periodA' => $periodA, 'periodB' => $periodB, 'cgA' => $cgA, 'cgB' => $cgB];
}

/** @return array{profileId:int, enrollmentId:int} */
function reportingMakeStudent(string $name, int $semesterId, int $classGroupId): array
{
    $personId = DB::table('people')->insertGetId([
        'full_name_ar' => $name, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $profileId = DB::table('student_profiles')->insertGetId([
        'person_id' => $personId, 'student_code' => 'STU-'.$personId,
        'lifecycle_status' => 'active', 'registered_on' => '2026-01-05',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $enrollmentId = DB::table('student_enrollments')->insertGetId([
        'student_profile_id' => $profileId, 'institution_semester_id' => $semesterId,
        'class_group_id' => $classGroupId, 'enrollment_status' => 'active',
        'enrolled_on' => '2026-01-05', 'created_at' => now(), 'updated_at' => now(),
    ]);

    return ['profileId' => $profileId, 'enrollmentId' => $enrollmentId];
}

function reportingAdminScope(int $limit = 200): ReportScope
{
    return new ReportScope(
        actorType: 'admin', actorAccountId: 1, portal: 'admin',
        locale: 'ar', isFullScope: true, limit: $limit,
    );
}

// ---------------------------------------------------------------------------
// Formula-injection sanitization
// ---------------------------------------------------------------------------

it('prefixes formula-injection cells with a quote', function (): void {
    $s = new FormulaInjectionSanitizer;

    expect($s->sanitizeCell('=SUM(A1:A9)'))->toBe("'=SUM(A1:A9)")
        ->and($s->sanitizeCell('+1+2'))->toBe("'+1+2")
        ->and($s->sanitizeCell('-cmd'))->toBe("'-cmd")
        ->and($s->sanitizeCell('@import'))->toBe("'@import")
        ->and($s->sanitizeCell('normal value'))->toBe('normal value')
        ->and($s->sanitizeCell(null))->toBeNull()
        ->and($s->sanitizeCell(42))->toBe(42);
});

it('sanitizes every cell of a row', function (): void {
    $s = new FormulaInjectionSanitizer;
    $row = $s->sanitizeRow(['name' => '=EVIL()', 'score' => 10]);

    expect($row['name'])->toBe("'=EVIL()")
        ->and($row['score'])->toBe(10);
});

// ---------------------------------------------------------------------------
// Definitions
// ---------------------------------------------------------------------------

it('seeds all 21 report definitions with valid permission keys', function (): void {
    reportingSeedDefinitions();

    expect(DB::table('report_definitions')->count())->toBe(21);

    // Every permission key referenced by a definition must exist in the catalogue
    $catalogueSeeder = 'Modules\\Authorization\\Database\\Seeders\\PermissionCatalogueSeeder';
    (new $catalogueSeeder)->run();

    $missing = DB::table('report_definitions')
        ->pluck('permission_key')->unique()
        ->reject(fn (string $key): bool => DB::table('permissions')->where('key', $key)->exists());

    expect($missing->all())->toBe([]);
});

it('marks organization-wide definitions as admin_only', function (): void {
    reportingSeedDefinitions();

    $adminOnly = DB::table('report_definitions')->where('admin_only', true)->pluck('code');

    expect($adminOnly)->toContain('institution_summary')
        ->and($adminOnly)->toContain('audit_activity');
});

it('rejects unknown report definition codes', function (): void {
    app(ReportQueryService::class)->run('nonexistent_report', reportingAdminScope());
})->throws(InvalidArgumentException::class);

// ---------------------------------------------------------------------------
// Period restriction (staff scope enforcement)
// ---------------------------------------------------------------------------

it('restricts class-group-bound reports to allowed operational periods', function (): void {
    $chain = reportingMakeSemesterChain();
    reportingMakeStudent('طالب أ', $chain['semesterId'], $chain['cgA']);
    reportingMakeStudent('طالب ب', $chain['semesterId'], $chain['cgB']);

    $svc = app(ReportQueryService::class);

    $fullScope = new ReportScope(
        actorType: 'staff', actorAccountId: 5, portal: 'staff', locale: 'ar',
        institutionSemesterId: $chain['semesterId'], isFullScope: true, limit: 200,
    );
    expect($svc->run('enrollment_placement', $fullScope))->toHaveCount(2);

    $restricted = new ReportScope(
        actorType: 'staff', actorAccountId: 5, portal: 'staff', locale: 'ar',
        institutionSemesterId: $chain['semesterId'], isFullScope: false,
        allowedPeriodIds: [$chain['periodA']], limit: 200,
    );
    $rows = $svc->run('enrollment_placement', $restricted);

    expect($rows)->toHaveCount(1)
        ->and($rows->first()->class_group)->toBe('شعبة أ');
});

it('returns zero rows when a restricted staff member has no period grants', function (): void {
    $chain = reportingMakeSemesterChain();
    reportingMakeStudent('طالب أ', $chain['semesterId'], $chain['cgA']);

    $zeroGrants = new ReportScope(
        actorType: 'staff', actorAccountId: 5, portal: 'staff', locale: 'ar',
        institutionSemesterId: $chain['semesterId'], isFullScope: false,
        allowedPeriodIds: [], limit: 200,
    );

    expect(app(ReportQueryService::class)->run('enrollment_placement', $zeroGrants))->toHaveCount(0)
        ->and(app(ReportQueryService::class)->run('student_registry', $zeroGrants))->toHaveCount(0);
});

it('restricts student attendance rows to allowed periods', function (): void {
    $chain = reportingMakeSemesterChain();
    $stuA = reportingMakeStudent('طالب أ', $chain['semesterId'], $chain['cgA']);
    $stuB = reportingMakeStudent('طالب ب', $chain['semesterId'], $chain['cgB']);

    foreach ([[$chain['cgA'], $chain['periodA'], $stuA], [$chain['cgB'], $chain['periodB'], $stuB]] as [$cg, $period, $stu]) {
        $sheetId = DB::table('student_attendance_sheets')->insertGetId([
            'institution_semester_id' => $chain['semesterId'], 'operational_period_id' => $period,
            'class_group_id' => $cg, 'attendance_date' => '2026-03-01', 'status' => 'verified',
            'creator_staff_profile_id' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('student_attendance_records')->insert([
            'sheet_id' => $sheetId, 'enrollment_id' => $stu['enrollmentId'],
            'student_profile_id' => $stu['profileId'], 'status_code' => 'present',
            'source' => 'manual', 'correction_cycle' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    $restricted = new ReportScope(
        actorType: 'staff', actorAccountId: 5, portal: 'staff', locale: 'ar',
        institutionSemesterId: $chain['semesterId'], isFullScope: false,
        allowedPeriodIds: [$chain['periodB']], limit: 200,
    );
    $rows = app(ReportQueryService::class)->run('student_attendance', $restricted);

    expect($rows)->toHaveCount(1)
        ->and($rows->first()->class_group)->toBe('شعبة ب');
});

it('restricts staff attendance rows to allowed operational periods', function (): void {
    $chain = reportingMakeSemesterChain();

    $personId = DB::table('people')->insertGetId([
        'full_name_ar' => 'موظف', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $staffId = DB::table('staff_profiles')->insertGetId([
        'person_id' => $personId, 'staff_code' => 'STF-1', 'employment_status' => 'active',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    foreach ([$chain['periodA'], $chain['periodB']] as $period) {
        DB::table('staff_attendance_records')->insert([
            'staff_profile_id' => $staffId, 'institution_semester_id' => $chain['semesterId'],
            'operational_period_id' => $period, 'record_date' => '2026-03-01',
            'status_code' => 'present', 'creator_staff_profile_id' => $staffId,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    $restricted = new ReportScope(
        actorType: 'staff', actorAccountId: 5, portal: 'staff', locale: 'ar',
        institutionSemesterId: $chain['semesterId'], isFullScope: false,
        allowedPeriodIds: [$chain['periodA']], limit: 200,
    );

    expect(app(ReportQueryService::class)->run('staff_attendance', $restricted))->toHaveCount(1);

    $zeroGrants = new ReportScope(
        actorType: 'staff', actorAccountId: 5, portal: 'staff', locale: 'ar',
        institutionSemesterId: $chain['semesterId'], isFullScope: false,
        allowedPeriodIds: [], limit: 200,
    );

    expect(app(ReportQueryService::class)->run('staff_attendance', $zeroGrants))->toHaveCount(0);
});

// ---------------------------------------------------------------------------
// Institution scoping (cross-institution isolation)
// ---------------------------------------------------------------------------

it('never returns another institution\'s correction requests or import batches when the scope carries an institution', function (): void {
    $chain = reportingMakeSemesterChain();
    $instA = (int) DB::table('institution_semesters')->where('id', $chain['semesterId'])->value('institution_id');
    $instB = DB::table('institutions')->insertGetId([
        'organization_id' => DB::table('organizations')->value('id'),
        'institution_type_id' => DB::table('institution_types')->value('id'),
        'code' => 'INST-B', 'name_en' => 'Other', 'name_ar' => 'أخرى', 'is_active' => true,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $stu = reportingMakeStudent('طالب أ', $chain['semesterId'], $chain['cgA']);

    $wfDefId = DB::table('workflow_definitions')->insertGetId([
        'type' => 'correction_request', 'version' => 1, 'is_active' => true,
        'transitions' => json_encode([]), 'terminal_states' => json_encode(['applied']),
        'initial_state' => 'submitted', 'created_at' => now(), 'updated_at' => now(),
    ]);

    foreach ([$instA, $instB] as $instId) {
        $wfId = DB::table('workflow_instances')->insertGetId([
            'workflow_definition_id' => $wfDefId, 'subject_type' => 'correction_request',
            'subject_id' => 0, 'current_state' => 'submitted',
            'initiating_actor_type' => 'guardian', 'initiating_actor_portal' => 'guardian',
            'correlation_id' => 'corr-'.$instId, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('student_correction_requests')->insert([
            'workflow_instance_id' => $wfId, 'student_profile_id' => $stu['profileId'],
            'guardian_account_id' => 1, 'guardian_profile_id' => 1, 'institution_id' => $instId,
            'field_catalogue_code' => 'full_name_ar', 'classification' => 'simple',
            'conflict_flag' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('import_batches')->insert([
            'status' => 'applied', 'actor_account_id' => 1, 'institution_id' => $instId,
            'original_filename' => 'x.xlsx', 'mime_type' => 'application/xlsx',
            'file_size_bytes' => 10, 'total_rows' => 1, 'valid_rows' => 1,
            'error_rows' => 0, 'applied_rows' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    $scoped = new ReportScope(
        actorType: 'admin', actorAccountId: 1, portal: 'admin', locale: 'ar',
        institutionSemesterId: $chain['semesterId'], institutionId: $instA,
        isFullScope: true, limit: 200,
    );
    $svc = app(ReportQueryService::class);

    expect($svc->run('correction_requests', $scoped))->toHaveCount(1)
        ->and($svc->run('import_results', $scoped))->toHaveCount(1);
});

it('restricts correction, document and issued-document reports to granted periods', function (): void {
    $chain = reportingMakeSemesterChain();
    $stuA = reportingMakeStudent('طالب أ', $chain['semesterId'], $chain['cgA']);
    $stuB = reportingMakeStudent('طالب ب', $chain['semesterId'], $chain['cgB']);

    $wfDefId = DB::table('workflow_definitions')->insertGetId([
        'type' => 'correction_request', 'version' => 1, 'is_active' => true,
        'transitions' => json_encode([]), 'terminal_states' => json_encode(['applied']),
        'initial_state' => 'submitted', 'created_at' => now(), 'updated_at' => now(),
    ]);

    $templateId = DB::table('document_templates')->insertGetId([
        'document_type_code' => 'enrollment_certificate',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $templateVersionId = DB::table('document_template_versions')->insertGetId([
        'template_id' => $templateId, 'version_number' => 1, 'locale' => 'ar',
        'body' => '<p>x</p>', 'status' => 'active',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    foreach ([$stuA, $stuB] as $i => $stu) {
        $wfId = DB::table('workflow_instances')->insertGetId([
            'workflow_definition_id' => $wfDefId, 'subject_type' => 'correction_request',
            'subject_id' => 0, 'current_state' => 'submitted',
            'initiating_actor_type' => 'guardian', 'initiating_actor_portal' => 'guardian',
            'correlation_id' => 'corr-period-'.$i, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('student_correction_requests')->insert([
            'workflow_instance_id' => $wfId, 'student_profile_id' => $stu['profileId'],
            'guardian_account_id' => 1, 'guardian_profile_id' => 1, 'institution_id' => 1,
            'field_catalogue_code' => 'full_name_ar', 'classification' => 'simple',
            'conflict_flag' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('student_document_requests')->insert([
            'enrollment_id' => $stu['enrollmentId'], 'student_profile_id' => $stu['profileId'],
            'institution_id' => 1, 'institution_semester_id' => $chain['semesterId'],
            'requested_by_actor_type' => 'guardian', 'requested_by_account_id' => 1,
            'portal' => 'guardian', 'document_type_code' => 'enrollment_certificate',
            'locale' => 'ar', 'status' => 'submitted', 'submitted_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('issued_documents')->insert([
            'document_number' => 'DOC-'.$i, 'document_type_code' => 'enrollment_certificate',
            'enrollment_id' => $stu['enrollmentId'], 'student_profile_id' => $stu['profileId'],
            'institution_id' => 1, 'institution_semester_id' => $chain['semesterId'],
            'template_version_id' => $templateVersionId, 'locale' => 'ar', 'approved_by_account_id' => 1,
            'issued_at' => now(), 'verification_code' => 'VC-'.$i,
            'verification_code_hash' => hash('sha256', 'VC-'.$i), 'storage_path' => 'docs/'.$i,
            'file_sha256' => str_repeat('a', 64), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    // Restricted staff granted only period A must see only student A's records
    $restricted = new ReportScope(
        actorType: 'staff', actorAccountId: 5, portal: 'staff', locale: 'ar',
        institutionSemesterId: $chain['semesterId'], institutionId: 1,
        isFullScope: false, allowedPeriodIds: [$chain['periodA']], limit: 200,
    );
    $svc = app(ReportQueryService::class);

    foreach (['correction_requests', 'document_requests', 'issued_documents'] as $code) {
        $rows = $svc->run($code, $restricted);
        expect($rows)->toHaveCount(1, "$code leaked cross-period rows")
            ->and($rows->first()->student_name)->toBe('طالب أ');
    }

    // Zero grants → zero rows for all three families
    $zero = new ReportScope(
        actorType: 'staff', actorAccountId: 5, portal: 'staff', locale: 'ar',
        institutionSemesterId: $chain['semesterId'], institutionId: 1,
        isFullScope: false, allowedPeriodIds: [], limit: 200,
    );

    foreach (['correction_requests', 'document_requests', 'issued_documents'] as $code) {
        expect($svc->run($code, $zero))->toHaveCount(0);
    }
});

it('restricts staff positions report to positions granted in allowed periods', function (): void {
    $chain = reportingMakeSemesterChain();

    $makePosition = function (string $name, ?int $grantedPeriodId) use ($chain): void {
        $personId = DB::table('people')->insertGetId([
            'full_name_ar' => $name, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $profileId = DB::table('staff_profiles')->insertGetId([
            'person_id' => $personId, 'staff_code' => 'STF-'.$personId,
            'employment_status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $assignmentId = DB::table('staff_institution_assignments')->insertGetId([
            'staff_profile_id' => $profileId, 'institution_id' => 1,
            'started_on' => '2024-01-01', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $positionId = DB::table('staff_positions')->insertGetId([
            'staff_profile_id' => $profileId,
            'staff_institution_assignment_id' => $assignmentId,
            'institution_id' => 1, 'institution_semester_id' => $chain['semesterId'],
            'position_definition' => 'secretary', 'started_on' => '2024-01-01',
            'created_by' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);

        if ($grantedPeriodId !== null) {
            DB::table('staff_position_periods')->insert([
                'staff_position_id' => $positionId,
                'operational_period_id' => $grantedPeriodId,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    };

    $makePosition('موظف فترة أ', $chain['periodA']);
    $makePosition('موظف فترة ب', $chain['periodB']);

    $svc = app(ReportQueryService::class);

    $restricted = new ReportScope(
        actorType: 'staff', actorAccountId: 5, portal: 'staff', locale: 'ar',
        institutionSemesterId: $chain['semesterId'], institutionId: 1,
        isFullScope: false, allowedPeriodIds: [$chain['periodA']], limit: 200,
    );

    $rows = $svc->run('staff_positions', $restricted);
    expect($rows)->toHaveCount(1)
        ->and($rows->first()->staff_name)->toBe('موظف فترة أ');

    // Zero grants → zero rows
    $zero = new ReportScope(
        actorType: 'staff', actorAccountId: 5, portal: 'staff', locale: 'ar',
        institutionSemesterId: $chain['semesterId'], institutionId: 1,
        isFullScope: false, allowedPeriodIds: [], limit: 200,
    );
    expect($svc->run('staff_positions', $zero))->toHaveCount(0);
});

// ---------------------------------------------------------------------------
// Export history isolation
// ---------------------------------------------------------------------------

it('lets staff see only their own export history while admins see all', function (): void {
    DB::table('report_exports')->insert([
        ['export_type' => 'student_registry', 'actor_type' => 'staff', 'actor_account_id' => 7,
            'scope' => '{}', 'locale' => 'ar', 'row_count' => 3, 'file_path' => 'reports/a.xlsx',
            'created_at' => now(), 'updated_at' => now()],
        ['export_type' => 'student_registry', 'actor_type' => 'staff', 'actor_account_id' => 8,
            'scope' => '{}', 'locale' => 'ar', 'row_count' => 4, 'file_path' => 'reports/b.xlsx',
            'created_at' => now(), 'updated_at' => now()],
        ['export_type' => 'audit_activity', 'actor_type' => 'admin', 'actor_account_id' => 1,
            'scope' => '{}', 'locale' => 'ar', 'row_count' => 9, 'file_path' => 'reports/c.xlsx',
            'created_at' => now(), 'updated_at' => now()],
    ]);

    $svc = app(ReportQueryService::class);

    $staffScope = new ReportScope(
        actorType: 'staff', actorAccountId: 7, portal: 'staff', locale: 'ar',
        isFullScope: false, allowedPeriodIds: [], limit: 200,
    );
    $staffRows = $svc->run('export_job_history', $staffScope);

    expect($staffRows)->toHaveCount(1)
        ->and((int) $staffRows->first()->actor_account_id)->toBe(7);

    expect($svc->run('export_job_history', reportingAdminScope()))->toHaveCount(3);
});

// ---------------------------------------------------------------------------
// All definitions execute without SQL errors
// ---------------------------------------------------------------------------

it('runs every seeded definition without a query error', function (): void {
    reportingSeedDefinitions();
    $svc = app(ReportQueryService::class);

    foreach (DB::table('report_definitions')->pluck('code') as $code) {
        $rows = $svc->run($code, reportingAdminScope(5));
        expect($rows)->toBeInstanceOf(Collection::class);
    }
});

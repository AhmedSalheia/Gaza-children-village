<?php

declare(strict_types=1);

namespace Modules\Documents\Services;

use Illuminate\Support\Facades\DB;
use Modules\Documents\Data\DocumentDataContext;

/**
 * Builds a DocumentDataContext from a student enrollment record.
 *
 * All DB access is plain query-builder so this service does not import
 * cross-module model classes (boundary-scanner safe).
 *
 * Institution, semester, academic-level, class-group, student profile,
 * and guardian relationship data are resolved from the enrollment ID.
 */
final class EnrollmentSnapshotService
{
    /**
     * Load all required context from the enrollment and build a DocumentDataContext.
     *
     * The guardian context fields are populated from the requesting guardian's
     * profile if a guardian account ID is provided; otherwise synthetic values
     * are used (for staff-initiated documents).
     *
     * @throws \RuntimeException When enrollment, student, or institution data is missing
     */
    public function buildFromEnrollment(
        int $enrollmentId,
        string $documentNumber,
        string $documentTypeLabelAr,
        string $documentTypeLabelEn,
        ?int $requestingGuardianAccountId = null,
    ): DocumentDataContext {
        // Load enrollment + class group + semester + institution + academic level
        $enrollment = DB::table('student_enrollments as se')
            ->join('class_groups as cg', 'cg.id', '=', 'se.class_group_id')
            ->join('institution_semesters as is', 'is.id', '=', 'se.institution_semester_id')
            ->join('institutions as inst', 'inst.id', '=', 'is.institution_id')
            ->join('semesters as sem', 'sem.id', '=', 'is.semester_id')
            ->join('academic_levels as al', 'al.id', '=', 'cg.academic_level_id')
            ->leftJoin('academic_years as ay', 'ay.id', '=', 'sem.academic_year_id')
            ->where('se.id', $enrollmentId)
            ->select(
                'se.student_profile_id',
                'se.institution_semester_id',
                'cg.name as class_group_name',
                'is.institution_id',
                'sem.name_ar as semester_name',
                'inst.name_ar as institution_name_ar',
                'inst.name_en as institution_name_en',
                'inst.code as institution_code',
                'al.name_ar as academic_level_ar',
                'al.name_en as academic_level_en',
                'ay.name_ar as academic_year_name',
            )
            ->first();

        if (! $enrollment) {
            throw new \RuntimeException("Enrollment #{$enrollmentId} not found or has missing relational data.");
        }

        // Load student profile → person
        $student = DB::table('student_profiles as sp')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->where('sp.id', (int) $enrollment->student_profile_id)
            ->select(
                'sp.id',
                'sp.student_code',
                'p.full_name_ar',
                'p.full_name_en',
                'p.birth_date',
            )
            ->first();

        if (! $student) {
            throw new \RuntimeException("Student profile #{$enrollment->student_profile_id} not found.");
        }

        // Load organization
        $organization = DB::table('organizations')
            ->join('institutions as i', 'i.organization_id', '=', 'organizations.id')
            ->where('i.id', (int) $enrollment->institution_id)
            ->select('organizations.name_ar as org_name_ar', 'organizations.name_en as org_name_en')
            ->first();

        // Load guardian context (if requested by a guardian)
        $guardianNameAr      = 'ولي الأمر';
        $guardianNameEn      = 'Guardian';
        $guardianRelType     = '';

        if ($requestingGuardianAccountId !== null) {
            $guardianProfile = DB::table('guardian_profiles as gp')
                ->join('people as p', 'p.id', '=', 'gp.person_id')
                ->where('gp.guardian_account_id', $requestingGuardianAccountId)
                ->select('gp.id', 'p.full_name_ar', 'p.full_name_en')
                ->first();

            if ($guardianProfile) {
                $guardianNameAr = (string) $guardianProfile->full_name_ar;
                $guardianNameEn = (string) ($guardianProfile->full_name_en ?? $guardianProfile->full_name_ar);

                // Relationship type label
                $rel = DB::table('guardian_student_relationships')
                    ->where('guardian_profile_id', (int) $guardianProfile->id)
                    ->where('student_profile_id', (int) $enrollment->student_profile_id)
                    ->where('verification_status', 'verified')
                    ->where('portal_eligible', true)
                    ->select('relationship_type')
                    ->first();

                $guardianRelType = $rel ? (string) $rel->relationship_type : '';
            }
        }

        // Load principal / issuing official name for the institution
        $principalNameAr = DB::table('staff_positions as sp')
            ->join('staff_profiles as sprof', 'sprof.id', '=', 'sp.staff_profile_id')
            ->join('people as p', 'p.id', '=', 'sprof.person_id')
            ->join('institution_semesters as is', 'is.id', '=', 'sp.institution_semester_id')
            ->where('is.institution_id', (int) $enrollment->institution_id)
            ->where('sp.position_definition', 'principal')
            ->whereNull('sp.ended_on')
            ->select('p.full_name_ar')
            ->value('full_name_ar') ?? 'مدير المدرسة';

        return new DocumentDataContext(
            studentFullNameAr:     (string) $student->full_name_ar,
            studentFullNameEn:     (string) ($student->full_name_en ?? $student->full_name_ar),
            studentBirthDate:      $student->birth_date ? (string) $student->birth_date : '',
            studentStudentCode:    (string) ($student->student_code ?? ''),
            studentAcademicLevel:  (string) ($enrollment->academic_level_ar ?? ''),
            studentClassGroupName: (string) ($enrollment->class_group_name ?? ''),
            guardianFullNameAr:    $guardianNameAr,
            guardianFullNameEn:    $guardianNameEn,
            guardianRelationshipType: $guardianRelType,
            institutionNameAr:     (string) ($enrollment->institution_name_ar ?? ''),
            institutionNameEn:     (string) ($enrollment->institution_name_en ?? ''),
            institutionCode:       (string) ($enrollment->institution_code ?? ''),
            organizationNameAr:    (string) ($organization->org_name_ar ?? ''),
            organizationNameEn:    (string) ($organization->org_name_en ?? ''),
            academicYearName:      (string) ($enrollment->academic_year_name ?? ''),
            semesterName:          (string) ($enrollment->semester_name ?? ''),
            documentNumber:        $documentNumber,
            documentIssuedAt:      now()->toDateString(),
            documentTypeLabelAr:   $documentTypeLabelAr,
            documentTypeLabelEn:   $documentTypeLabelEn,
            documentIssuedByNameAr: (string) $principalNameAr,
        );
    }
}

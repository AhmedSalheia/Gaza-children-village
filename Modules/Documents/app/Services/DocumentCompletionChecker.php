<?php

declare(strict_types=1);

namespace Modules\Documents\Services;

use Illuminate\Support\Facades\DB;

/**
 * Checks whether the enrollment and student data required for a document type
 * are complete enough to proceed to the approval and generation steps.
 *
 * The completeness_checks JSON column on document_type_catalogue defines which
 * checks to run. Each check is a named rule that this service evaluates.
 *
 * Rule names are deliberately kept identical to the seeded catalogue values so
 * that adding a new type in DocumentTypeSeeder automatically activates the
 * matching check here without any further mapping.
 *
 * Fail-closed policy: a rule name that is not recognized returns a failure
 * message rather than silently passing. This prevents catalogue drift from
 * inadvertently bypassing security/eligibility preconditions.
 *
 * Returns a list of human-readable failure messages (in Arabic). An empty list
 * means all checks passed.
 */
final class DocumentCompletionChecker
{
    /**
     * Run all completeness checks for the given document type and enrollment.
     *
     * @return list<string> List of failure messages; empty = passed
     */
    public function check(string $documentTypeCode, int $enrollmentId): array
    {
        $type = DB::table('document_type_catalogue')
            ->where('code', $documentTypeCode)
            ->select('completeness_checks')
            ->first();

        if (! $type || ! $type->completeness_checks) {
            // No checks defined — passes by default
            return [];
        }

        /** @var list<string> $checks */
        $checks = json_decode((string) $type->completeness_checks, true) ?? [];

        if (empty($checks)) {
            return [];
        }

        $enrollment = DB::table('student_enrollments as se')
            ->join('class_groups as cg', 'cg.id', '=', 'se.class_group_id')
            ->join('institution_semesters as is', 'is.id', '=', 'se.institution_semester_id')
            ->join('semesters as sem', 'sem.id', '=', 'is.semester_id')
            ->where('se.id', $enrollmentId)
            ->select(
                'se.id',
                'se.student_profile_id',
                'se.institution_semester_id',
                'se.enrollment_status',
                'is.institution_id',
                'sem.academic_year_id',
                'cg.id as class_group_id',
            )
            ->first();

        if (! $enrollment) {
            return ['لم يتم العثور على سجل التسجيل المطلوب.'];
        }

        $failures = [];

        foreach ($checks as $check) {
            $failure = $this->runCheck($check, $enrollment);

            if ($failure !== null) {
                $failures[] = $failure;
            }
        }

        return $failures;
    }

    /**
     * Run a single named check. Returns a failure message or null on pass.
     *
     * Fail-closed: an unrecognized rule name always returns a failure message.
     */
    private function runCheck(string $checkName, object $enrollment): ?string
    {
        return match ($checkName) {
            // ── Catalogue rule names (must match DocumentTypeSeeder exactly) ──

            'active_enrollment' => $this->checkEnrollmentActive((string) $enrollment->enrollment_status),

            'marks_published' => $this->checkMarksPublished(
                (int) $enrollment->institution_semester_id,
                (int) $enrollment->class_group_id,
            ),

            'attendance_published' => $this->checkAttendancePublished(
                (int) $enrollment->institution_semester_id,
                (int) $enrollment->class_group_id,
            ),

            'year_closed' => $this->checkYearClosed((int) $enrollment->academic_year_id),

            // ── Legacy / extra checks (not in the seeder, fail-safe to keep) ──

            'student_has_person_record' => $this->checkStudentPersonRecord((int) $enrollment->student_profile_id),
            'student_has_birth_date' => $this->checkStudentBirthDate((int) $enrollment->student_profile_id),
            'student_has_name_ar' => $this->checkStudentNameAr((int) $enrollment->student_profile_id),
            'guardian_relationship_exists' => $this->checkGuardianRelationship((int) $enrollment->student_profile_id),

            // ── Fail-closed: unknown rule names block issuance ────────────────
            default => sprintf(
                'فحص الاكتمال "%s" غير معرَّف. يرجى مراجعة المسؤول.',
                $checkName,
            ),
        };
    }

    // ── Catalogue-level check implementations ────────────────────────────────

    private function checkEnrollmentActive(string $status): ?string
    {
        return $status === 'active' ? null : 'التسجيل ليس نشطاً حالياً.';
    }

    /**
     * Check that a published (not revoked) result publication exists for the
     * class group in the current institution semester.
     */
    private function checkMarksPublished(int $institutionSemesterId, int $classGroupId): ?string
    {
        $exists = DB::table('result_publications')
            ->where('institution_semester_id', $institutionSemesterId)
            ->where('class_group_id', $classGroupId)
            ->where('status', 'published')
            ->exists();

        return $exists ? null : 'لم يتم نشر نتائج درجات هذه المجموعة بعد.';
    }

    /**
     * Check that a published (not revoked) attendance publication snapshot
     * exists for the class group in the current institution semester.
     */
    private function checkAttendancePublished(int $institutionSemesterId, int $classGroupId): ?string
    {
        $exists = DB::table('attendance_publication_snapshots')
            ->where('institution_semester_id', $institutionSemesterId)
            ->where('class_group_id', $classGroupId)
            ->where('status', 'published')
            ->exists();

        return $exists ? null : 'لم يتم نشر سجل الحضور لهذه المجموعة بعد.';
    }

    /**
     * Check that the academic year linked to the enrollment's semester is
     * closed or archived (required for end-of-year certificate types).
     */
    private function checkYearClosed(int $academicYearId): ?string
    {
        $year = DB::table('academic_years')
            ->where('id', $academicYearId)
            ->select('status')
            ->first();

        if (! $year) {
            return 'لم يتم العثور على السنة الدراسية المرتبطة بالتسجيل.';
        }

        return in_array((string) $year->status, ['closed', 'archived'], true)
            ? null
            : 'لا يمكن إصدار هذه الوثيقة قبل إغلاق السنة الدراسية.';
    }

    // ── Extra checks (not in production catalogue; kept for completeness) ─────

    private function checkStudentPersonRecord(int $studentProfileId): ?string
    {
        $exists = DB::table('student_profiles as sp')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->where('sp.id', $studentProfileId)
            ->exists();

        return $exists ? null : 'لا يوجد سجل شخصي مرتبط بملف الطالب.';
    }

    private function checkStudentBirthDate(int $studentProfileId): ?string
    {
        $person = DB::table('student_profiles as sp')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->where('sp.id', $studentProfileId)
            ->select('p.birth_date')
            ->first();

        return ($person && $person->birth_date) ? null : 'تاريخ ميلاد الطالب مفقود.';
    }

    private function checkStudentNameAr(int $studentProfileId): ?string
    {
        $person = DB::table('student_profiles as sp')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->where('sp.id', $studentProfileId)
            ->select('p.full_name_ar')
            ->first();

        return ($person && $person->full_name_ar) ? null : 'الاسم العربي الكامل للطالب مفقود.';
    }

    private function checkGuardianRelationship(int $studentProfileId): ?string
    {
        $exists = DB::table('guardian_student_relationships')
            ->where('student_profile_id', $studentProfileId)
            ->where('verification_status', 'verified')
            ->where('portal_eligible', true)
            ->where(fn ($q) => $q->whereNull('ends_on')->orWhere('ends_on', '>=', now()->toDateString()))
            ->exists();

        return $exists ? null : 'لا توجد علاقة ولاية موثقة ومؤهلة لهذا الطالب.';
    }
}

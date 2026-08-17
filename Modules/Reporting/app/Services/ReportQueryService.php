<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Reporting\Data\ReportScope;

/**
 * Runs the data query for each of the 21 report families.
 *
 * All cross-module data access uses DB::table() to satisfy the
 * ModuleBoundaries scanner (no `use` imports from other modules).
 *
 * Each method accepts a ReportScope and returns a Collection<object>,
 * applying scope->limit. Period restriction is enforced for non-full-scope
 * staff via scope->allowedPeriodIds wherever the data is class-group bound.
 *
 * Sensitive fields (national identifiers, guardian contact details) are
 * excluded from all report results by design.
 */
final class ReportQueryService
{
    // ── Dispatch ────────────────────────────────────────────────────────────

    /**
     * Dispatch to the correct query method by definition code.
     *
     * @return Collection<int, object>
     *
     * @throws \InvalidArgumentException When the code is unknown.
     */
    public function run(string $definitionCode, ReportScope $scope): Collection
    {
        return match ($definitionCode) {
            'student_registry' => $this->runStudentRegistry($scope),
            'enrollment_placement' => $this->runEnrollmentPlacement($scope),
            'student_transfers' => $this->runStudentTransfers($scope),
            'student_attendance' => $this->runStudentAttendance($scope),
            'staff_attendance' => $this->runStaffAttendance($scope),
            'assessments_marks' => $this->runAssessmentsMarks($scope),
            'published_results' => $this->runPublishedResults($scope),
            'missing_attendance' => $this->runMissingAttendance($scope),
            'missing_marks' => $this->runMissingMarks($scope),
            'teaching_assignments' => $this->runTeachingAssignments($scope),
            'staff_positions' => $this->runStaffPositions($scope),
            'guardian_relationships' => $this->runGuardianRelationships($scope),
            'correction_requests' => $this->runCorrectionRequests($scope),
            'document_requests' => $this->runDocumentRequests($scope),
            'issued_documents' => $this->runIssuedDocuments($scope),
            'formal_requests' => $this->runFormalRequests($scope),
            'audit_activity' => $this->runAuditActivity($scope),
            'import_results' => $this->runImportResults($scope),
            'institution_summary' => $this->runInstitutionSummary($scope),
            'notification_events' => $this->runNotificationEvents($scope),
            'export_job_history' => $this->runExportJobHistory($scope),
            default => throw new \InvalidArgumentException("Unknown report definition code: {$definitionCode}"),
        };
    }

    // ── 1. Student registry ──────────────────────────────────────────────────

    /** @return Collection<int, object> */
    public function runStudentRegistry(ReportScope $scope): Collection
    {
        $q = DB::table('student_profiles as sp')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->orderBy('p.full_name_ar');

        if ($scope->institutionSemesterId) {
            $q->join('student_enrollments as e', 'e.student_profile_id', '=', 'sp.id')
                ->where('e.institution_semester_id', $scope->institutionSemesterId)
                ->where('e.enrollment_status', '!=', 'withdrawn');

            if ($scope->classGroupId) {
                $q->where('e.class_group_id', $scope->classGroupId);
            }

            $this->restrictToAllowedPeriodsViaClassGroup($q, $scope, 'e.class_group_id');
        }

        return $q->limit($scope->limit)->offset($scope->offset)
            ->select(
                'sp.student_code',
                'p.full_name_ar as student_name',
                'p.birth_date',
                'sp.lifecycle_status',
                'sp.orphan_status',
                'sp.displacement_status',
                'sp.registered_on',
            )->get();
    }

    // ── 2. Enrollment placement ───────────────────────────────────────────────

    /** @return Collection<int, object> */
    public function runEnrollmentPlacement(ReportScope $scope): Collection
    {
        $q = DB::table('student_enrollments as e')
            ->join('student_profiles as sp', 'sp.id', '=', 'e.student_profile_id')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->join('class_groups as cg', 'cg.id', '=', 'e.class_group_id')
            ->leftJoin('academic_levels as al', 'al.id', '=', 'cg.academic_level_id')
            ->where('e.enrollment_status', '!=', 'withdrawn');

        if ($scope->institutionSemesterId) {
            $q->where('e.institution_semester_id', $scope->institutionSemesterId);
        }

        if ($scope->classGroupId) {
            $q->where('e.class_group_id', $scope->classGroupId);
        }

        $this->restrictToAllowedPeriods($q, $scope);

        return $q->limit($scope->limit)->offset($scope->offset)
            ->orderBy('al.sequence')
            ->orderBy('cg.name_ar')
            ->orderBy('p.full_name_ar')
            ->select(
                'sp.student_code',
                'p.full_name_ar as student_name',
                'cg.name_ar as class_group',
                'al.name_ar as level',
                'e.enrollment_status',
                'e.enrolled_on',
            )->get();
    }

    // ── 3. Student transfers / exits ─────────────────────────────────────────

    /** @return Collection<int, object> */
    public function runStudentTransfers(ReportScope $scope): Collection
    {
        $q = DB::table('student_enrollments as e')
            ->join('student_profiles as sp', 'sp.id', '=', 'e.student_profile_id')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->leftJoin('class_groups as cg', 'cg.id', '=', 'e.class_group_id')
            ->whereIn('e.enrollment_status', ['withdrawn', 'transferred'])
            ->orderByDesc('e.updated_at');

        if ($scope->institutionSemesterId) {
            $q->where('e.institution_semester_id', $scope->institutionSemesterId);
        }

        $this->restrictToAllowedPeriods($q, $scope);

        if ($scope->dateFrom) {
            $q->where('e.updated_at', '>=', $scope->dateFrom);
        }

        if ($scope->dateTo) {
            $q->where('e.updated_at', '<=', $scope->dateTo.' 23:59:59');
        }

        return $q->limit($scope->limit)->offset($scope->offset)
            ->select(
                'sp.student_code',
                'p.full_name_ar as student_name',
                'cg.name_ar as class_group',
                'e.enrollment_status',
                'e.enrolled_on',
                'e.completed_on',
                'e.notes',
            )->get();
    }

    // ── 4. Student attendance ────────────────────────────────────────────────

    /** @return Collection<int, object> */
    public function runStudentAttendance(ReportScope $scope): Collection
    {
        $q = DB::table('student_attendance_records as sar')
            ->join('student_attendance_sheets as sas', 'sas.id', '=', 'sar.sheet_id')
            ->join('class_groups as cg', 'cg.id', '=', 'sas.class_group_id')
            ->join('student_profiles as sp', 'sp.id', '=', 'sar.student_profile_id')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->orderBy('sas.attendance_date')
            ->orderBy('cg.name_ar');

        if ($scope->institutionSemesterId) {
            $q->where('sas.institution_semester_id', $scope->institutionSemesterId);
        }

        if ($scope->classGroupId) {
            $q->where('sas.class_group_id', $scope->classGroupId);
        }

        if ($scope->operationalPeriodId) {
            $q->where('cg.operational_period_id', $scope->operationalPeriodId);
        } elseif (! $scope->isFullScope && $scope->allowedPeriodIds !== null) {
            $q->whereIn('cg.operational_period_id', $scope->allowedPeriodIds);
        }

        if ($scope->dateFrom) {
            $q->where('sas.attendance_date', '>=', $scope->dateFrom);
        }

        if ($scope->dateTo) {
            $q->where('sas.attendance_date', '<=', $scope->dateTo);
        }

        return $q->limit($scope->limit)->offset($scope->offset)
            ->select(
                'sas.attendance_date',
                'cg.name_ar as class_group',
                'p.full_name_ar as student_name',
                'sp.student_code',
                'sar.status_code',
                'sar.reason',
                'sas.status as sheet_status',
            )->get();
    }

    // ── 5. Staff attendance ──────────────────────────────────────────────────

    /** @return Collection<int, object> */
    public function runStaffAttendance(ReportScope $scope): Collection
    {
        $q = DB::table('staff_attendance_records as sar')
            ->join('staff_profiles as sp', 'sp.id', '=', 'sar.staff_profile_id')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->leftJoin('operational_periods as op', 'op.id', '=', 'sar.operational_period_id')
            ->orderBy('sar.record_date')
            ->orderBy('p.full_name_ar');

        if ($scope->institutionSemesterId) {
            $q->where('sar.institution_semester_id', $scope->institutionSemesterId);
        }

        if ($scope->operationalPeriodId) {
            $q->where('sar.operational_period_id', $scope->operationalPeriodId);
        }

        // Period-restricted staff only see records within their granted periods.
        if (! $scope->isFullScope && $scope->allowedPeriodIds !== null) {
            $q->whereIn('sar.operational_period_id', $scope->allowedPeriodIds);
        }

        if ($scope->dateFrom) {
            $q->where('sar.record_date', '>=', $scope->dateFrom);
        }

        if ($scope->dateTo) {
            $q->where('sar.record_date', '<=', $scope->dateTo);
        }

        return $q->limit($scope->limit)->offset($scope->offset)
            ->select(
                'sar.record_date',
                'p.full_name_ar as staff_name',
                'sp.staff_code',
                'op.name_ar as period_name',
                'sar.status_code',
                'sar.is_verified',
                'sar.confirmed_arrived_at',
            )->get();
    }

    // ── 6. Assessments / marks ───────────────────────────────────────────────

    /** @return Collection<int, object> */
    public function runAssessmentsMarks(ReportScope $scope): Collection
    {
        $q = DB::table('student_marks as sm')
            ->join('mark_sheets as ms', 'ms.id', '=', 'sm.mark_sheet_id')
            ->join('class_groups as cg', 'cg.id', '=', 'ms.class_group_id')
            ->join('student_enrollments as e', 'e.id', '=', 'sm.enrollment_id')
            ->join('student_profiles as sp', 'sp.id', '=', 'e.student_profile_id')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->join('assessment_definitions as ad', 'ad.id', '=', 'sm.assessment_definition_id')
            ->leftJoin('institution_subject_offerings as iso', 'iso.id', '=', 'ms.subject_offering_id')
            ->leftJoin('subjects as subj', 'subj.id', '=', 'iso.subject_id')
            ->orderBy('cg.name_ar')
            ->orderBy('p.full_name_ar');

        if ($scope->institutionSemesterId) {
            $q->where('ms.institution_semester_id', $scope->institutionSemesterId);
        }

        if ($scope->classGroupId) {
            $q->where('ms.class_group_id', $scope->classGroupId);
        }

        $this->restrictToAllowedPeriods($q, $scope);

        return $q->limit($scope->limit)->offset($scope->offset)
            ->select(
                'sp.student_code',
                'p.full_name_ar as student_name',
                'cg.name_ar as class_group',
                'subj.name_ar as subject',
                'ad.name_ar as assessment',
                'sm.score',
                'ad.max_score',
                'ms.status as sheet_status',
            )->get();
    }

    // ── 7. Published results ─────────────────────────────────────────────────

    /** @return Collection<int, object> */
    public function runPublishedResults(ReportScope $scope): Collection
    {
        $q = DB::table('result_publication_rows as rpr')
            ->join('result_publications as rp', 'rp.id', '=', 'rpr.result_publication_id')
            ->join('student_profiles as sp', 'sp.id', '=', 'rpr.student_profile_id')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->join('class_groups as cg', 'cg.id', '=', 'rp.class_group_id')
            ->where('rp.status', 'published')
            ->orderBy('cg.name_ar')
            ->orderBy('p.full_name_ar');

        if ($scope->institutionSemesterId) {
            $q->where('rp.institution_semester_id', $scope->institutionSemesterId);
        }

        if ($scope->classGroupId) {
            $q->where('rp.class_group_id', $scope->classGroupId);
        }

        $this->restrictToAllowedPeriods($q, $scope);

        return $q->limit($scope->limit)->offset($scope->offset)
            ->select(
                'sp.student_code',
                'p.full_name_ar as student_name',
                'cg.name_ar as class_group',
                'rpr.raw_total_score',
                'rpr.raw_max_possible',
                'rpr.normalized_score',
                'rpr.grade_name_ar as grade',
                'rpr.is_passing',
                'rp.published_at',
            )->get();
    }

    // ── 8. Missing / incomplete attendance ───────────────────────────────────

    /** @return Collection<int, object> */
    public function runMissingAttendance(ReportScope $scope): Collection
    {
        $q = DB::table('student_attendance_sheets as sas')
            ->join('class_groups as cg', 'cg.id', '=', 'sas.class_group_id')
            ->whereIn('sas.status', ['draft', 'returned'])
            ->orderBy('sas.attendance_date')
            ->orderBy('cg.name_ar');

        if ($scope->institutionSemesterId) {
            $q->where('sas.institution_semester_id', $scope->institutionSemesterId);
        }

        if ($scope->classGroupId) {
            $q->where('sas.class_group_id', $scope->classGroupId);
        }

        $this->restrictToAllowedPeriods($q, $scope);

        if ($scope->dateFrom) {
            $q->where('sas.attendance_date', '>=', $scope->dateFrom);
        }

        if ($scope->dateTo) {
            $q->where('sas.attendance_date', '<=', $scope->dateTo);
        }

        return $q->limit($scope->limit)->offset($scope->offset)
            ->select(
                'sas.attendance_date',
                'cg.name_ar as class_group',
                'sas.status',
                'sas.created_at',
                'sas.updated_at',
            )->get();
    }

    // ── 9. Missing / incomplete marks ────────────────────────────────────────

    /** @return Collection<int, object> */
    public function runMissingMarks(ReportScope $scope): Collection
    {
        $q = DB::table('mark_sheets as ms')
            ->join('class_groups as cg', 'cg.id', '=', 'ms.class_group_id')
            ->leftJoin('institution_subject_offerings as iso', 'iso.id', '=', 'ms.subject_offering_id')
            ->leftJoin('subjects as subj', 'subj.id', '=', 'iso.subject_id')
            ->whereIn('ms.status', ['draft', 'returned'])
            ->orderBy('cg.name_ar');

        if ($scope->institutionSemesterId) {
            $q->where('ms.institution_semester_id', $scope->institutionSemesterId);
        }

        if ($scope->classGroupId) {
            $q->where('ms.class_group_id', $scope->classGroupId);
        }

        $this->restrictToAllowedPeriods($q, $scope);

        return $q->limit($scope->limit)->offset($scope->offset)
            ->select(
                'cg.name_ar as class_group',
                'subj.name_ar as subject',
                'ms.status',
                'ms.return_reason',
                'ms.updated_at',
            )->get();
    }

    // ── 10. Teaching assignments ──────────────────────────────────────────────

    /** @return Collection<int, object> */
    public function runTeachingAssignments(ReportScope $scope): Collection
    {
        $q = DB::table('teaching_assignments as ta')
            ->join('staff_profiles as sp', 'sp.id', '=', 'ta.staff_profile_id')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->join('class_groups as cg', 'cg.id', '=', 'ta.class_group_id')
            ->leftJoin('institution_subject_offerings as iso', 'iso.id', '=', 'ta.subject_offering_id')
            ->leftJoin('subjects as subj', 'subj.id', '=', 'iso.subject_id')
            ->orderBy('cg.name_ar')
            ->orderBy('p.full_name_ar');

        if ($scope->institutionSemesterId) {
            $q->where('ta.institution_semester_id', $scope->institutionSemesterId);
        }

        if ($scope->classGroupId) {
            $q->where('ta.class_group_id', $scope->classGroupId);
        }

        $this->restrictToAllowedPeriods($q, $scope);

        return $q->limit($scope->limit)->offset($scope->offset)
            ->select(
                'p.full_name_ar as teacher_name',
                'sp.staff_code',
                'cg.name_ar as class_group',
                'subj.name_ar as subject',
                'ta.status',
                'ta.starts_on',
                'ta.ends_on',
            )->get();
    }

    // ── 11. Staff positions ───────────────────────────────────────────────────

    /** @return Collection<int, object> */
    public function runStaffPositions(ReportScope $scope): Collection
    {
        $q = DB::table('staff_positions as spos')
            ->join('staff_profiles as sp', 'sp.id', '=', 'spos.staff_profile_id')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->join('institution_semesters as is_', 'is_.id', '=', 'spos.institution_semester_id')
            ->join('institutions as inst', 'inst.id', '=', 'is_.institution_id')
            ->orderBy('p.full_name_ar');

        if ($scope->institutionSemesterId) {
            $q->where('spos.institution_semester_id', $scope->institutionSemesterId);
        } elseif ($scope->institutionId) {
            $q->where('inst.id', $scope->institutionId);
        }

        // Period-restricted staff only see positions holding a grant in one of
        // their own allowed periods; zero grants → zero rows.
        if (! $scope->isFullScope && $scope->allowedPeriodIds !== null) {
            $allowed = $scope->allowedPeriodIds;
            $q->whereExists(function ($sub) use ($allowed): void {
                $sub->select(DB::raw(1))
                    ->from('staff_position_periods as spp')
                    ->whereColumn('spp.staff_position_id', 'spos.id')
                    ->whereIn('spp.operational_period_id', $allowed);
            });
        }

        if ($scope->dateFrom) {
            $q->where('spos.started_on', '>=', $scope->dateFrom);
        }

        if ($scope->dateTo) {
            $q->where(function ($inner) use ($scope): void {
                $inner->whereNull('spos.ended_on')
                    ->orWhere('spos.ended_on', '<=', $scope->dateTo);
            });
        }

        return $q->limit($scope->limit)->offset($scope->offset)
            ->select(
                'p.full_name_ar as staff_name',
                'sp.staff_code',
                'inst.name_ar as institution',
                'spos.position_definition',
                'spos.started_on',
                'spos.ended_on',
            )->get();
    }

    // ── 12. Guardian relationships ────────────────────────────────────────────

    /** @return Collection<int, object> */
    public function runGuardianRelationships(ReportScope $scope): Collection
    {
        $q = DB::table('guardian_student_relationships as gsr')
            ->join('student_profiles as sp', 'sp.id', '=', 'gsr.student_profile_id')
            ->join('people as sp_p', 'sp_p.id', '=', 'sp.person_id')
            ->join('guardian_profiles as gp', 'gp.id', '=', 'gsr.guardian_profile_id')
            ->join('people as gp_p', 'gp_p.id', '=', 'gp.person_id')
            ->orderBy('sp_p.full_name_ar');

        // Scope to students enrolled in this semester if provided
        if ($scope->institutionSemesterId) {
            $q->join('student_enrollments as e', function ($join) use ($scope): void {
                $join->on('e.student_profile_id', '=', 'gsr.student_profile_id')
                    ->where('e.institution_semester_id', $scope->institutionSemesterId)
                    ->where('e.enrollment_status', '!=', 'withdrawn');
            });

            $this->restrictToAllowedPeriodsViaClassGroup($q, $scope, 'e.class_group_id');
        }

        return $q->limit($scope->limit)->offset($scope->offset)
            ->select(
                'sp.student_code',
                'sp_p.full_name_ar as student_name',
                'gp_p.full_name_ar as guardian_name',
                'gsr.relationship_type',
                'gsr.legal_authority',
                'gsr.verification_status',
                'gsr.contact_priority',
                'gsr.portal_eligible',
            )->get();
    }

    // ── 13. Correction requests ───────────────────────────────────────────────

    /** @return Collection<int, object> */
    public function runCorrectionRequests(ReportScope $scope): Collection
    {
        $q = DB::table('student_correction_requests as scr')
            ->join('student_profiles as sp', 'sp.id', '=', 'scr.student_profile_id')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->leftJoin('workflow_instances as wi', 'wi.id', '=', 'scr.workflow_instance_id')
            ->orderByDesc('scr.created_at');

        if ($scope->institutionId) {
            $q->where('scr.institution_id', $scope->institutionId);
        }

        // Period-restricted staff: the student must have an enrollment in the
        // scope semester whose class group falls in a granted period.
        if (! $scope->isFullScope && $scope->allowedPeriodIds !== null) {
            $allowed = $scope->allowedPeriodIds;
            $q->whereExists(function ($sub) use ($scope, $allowed): void {
                $sub->select(DB::raw(1))
                    ->from('student_enrollments as se')
                    ->join('class_groups as scg', 'scg.id', '=', 'se.class_group_id')
                    ->whereColumn('se.student_profile_id', 'scr.student_profile_id')
                    ->whereIn('scg.operational_period_id', $allowed);

                if ($scope->institutionSemesterId) {
                    $sub->where('se.institution_semester_id', $scope->institutionSemesterId);
                }
            });
        }

        if ($scope->dateFrom) {
            $q->where('scr.created_at', '>=', $scope->dateFrom);
        }

        if ($scope->dateTo) {
            $q->where('scr.created_at', '<=', $scope->dateTo.' 23:59:59');
        }

        return $q->limit($scope->limit)->offset($scope->offset)
            ->select(
                'sp.student_code',
                'p.full_name_ar as student_name',
                'scr.field_catalogue_code',
                'scr.classification',
                'wi.current_state as status',
                'scr.conflict_flag',
                'scr.created_at',
                'scr.applied_at',
            )->get();
    }

    // ── 14. Document requests ────────────────────────────────────────────────

    /** @return Collection<int, object> */
    public function runDocumentRequests(ReportScope $scope): Collection
    {
        $q = DB::table('student_document_requests as sdr')
            ->join('student_profiles as sp', 'sp.id', '=', 'sdr.student_profile_id')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->leftJoin('document_type_catalogue as dtc', 'dtc.code', '=', 'sdr.document_type_code')
            ->orderByDesc('sdr.created_at');

        if ($scope->institutionSemesterId) {
            $q->where('sdr.institution_semester_id', $scope->institutionSemesterId);
        }

        // Period-restricted staff: request's enrollment must fall in a granted period.
        if (! $scope->isFullScope && $scope->allowedPeriodIds !== null) {
            $q->join('student_enrollments as e', 'e.id', '=', 'sdr.enrollment_id');
            $this->restrictToAllowedPeriodsViaClassGroup($q, $scope, 'e.class_group_id');
        }

        if ($scope->dateFrom) {
            $q->where('sdr.created_at', '>=', $scope->dateFrom);
        }

        if ($scope->dateTo) {
            $q->where('sdr.created_at', '<=', $scope->dateTo.' 23:59:59');
        }

        return $q->limit($scope->limit)->offset($scope->offset)
            ->select(
                'sp.student_code',
                'p.full_name_ar as student_name',
                'dtc.label_ar as document_type',
                'sdr.status',
                'sdr.requested_by_actor_type',
                'sdr.submitted_at',
                'sdr.completed_at',
            )->get();
    }

    // ── 15. Issued documents ─────────────────────────────────────────────────

    /** @return Collection<int, object> */
    public function runIssuedDocuments(ReportScope $scope): Collection
    {
        $q = DB::table('issued_documents as id_')
            ->join('student_profiles as sp', 'sp.id', '=', 'id_.student_profile_id')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->leftJoin('document_type_catalogue as dtc', 'dtc.code', '=', 'id_.document_type_code')
            ->orderByDesc('id_.issued_at');

        if ($scope->institutionSemesterId) {
            $q->where('id_.institution_semester_id', $scope->institutionSemesterId);
        }

        // Period-restricted staff: document's enrollment must fall in a granted period.
        if (! $scope->isFullScope && $scope->allowedPeriodIds !== null) {
            $q->join('student_enrollments as e', 'e.id', '=', 'id_.enrollment_id');
            $this->restrictToAllowedPeriodsViaClassGroup($q, $scope, 'e.class_group_id');
        }

        if ($scope->dateFrom) {
            $q->where('id_.issued_at', '>=', $scope->dateFrom);
        }

        if ($scope->dateTo) {
            $q->where('id_.issued_at', '<=', $scope->dateTo.' 23:59:59');
        }

        return $q->limit($scope->limit)->offset($scope->offset)
            ->select(
                'sp.student_code',
                'p.full_name_ar as student_name',
                'dtc.label_ar as document_type',
                'id_.document_number',
                'id_.locale',
                'id_.issued_at',
                'id_.cancelled_at',
            )->get();
    }

    // ── 16. Formal requests ──────────────────────────────────────────────────

    /** @return Collection<int, object> */
    public function runFormalRequests(ReportScope $scope): Collection
    {
        $q = DB::table('institution_formal_requests as fir')
            ->leftJoin('institutions as inst', 'inst.id', '=', 'fir.institution_id')
            ->orderByDesc('fir.created_at');

        if ($scope->institutionSemesterId) {
            $q->where('fir.institution_semester_id', $scope->institutionSemesterId);
        } elseif ($scope->institutionId) {
            $q->where('fir.institution_id', $scope->institutionId);
        }

        if ($scope->dateFrom) {
            $q->where('fir.created_at', '>=', $scope->dateFrom);
        }

        if ($scope->dateTo) {
            $q->where('fir.created_at', '<=', $scope->dateTo.' 23:59:59');
        }

        return $q->limit($scope->limit)->offset($scope->offset)
            ->select(
                'fir.request_number',
                'fir.request_type',
                'fir.title_ar',
                'fir.current_status',
                'fir.priority',
                'inst.name_ar as institution',
                'fir.due_date',
                'fir.created_at as requested_at',
                'fir.response_at',
            )->get();
    }

    // ── 17. Audit activity ───────────────────────────────────────────────────

    /** @return Collection<int, object> */
    public function runAuditActivity(ReportScope $scope): Collection
    {
        $q = DB::table('audit_events as ae')
            ->orderByDesc('ae.recorded_at');

        if ($scope->institutionSemesterId) {
            $q->where('ae.institution_semester_id', $scope->institutionSemesterId);
        }

        if ($scope->dateFrom) {
            $q->where('ae.recorded_at', '>=', $scope->dateFrom);
        }

        if ($scope->dateTo) {
            $q->where('ae.recorded_at', '<=', $scope->dateTo.' 23:59:59');
        }

        return $q->limit($scope->limit)->offset($scope->offset)
            ->select(
                'ae.action',
                'ae.source_module',
                'ae.actor_type',
                'ae.actor_account_id',
                'ae.subject_type',
                'ae.subject_id',
                'ae.portal',
                'ae.recorded_at',
            )->get();
    }

    // ── 18. Import results ────────────────────────────────────────────────────

    /** @return Collection<int, object> */
    public function runImportResults(ReportScope $scope): Collection
    {
        $q = DB::table('import_batches as ib')
            ->leftJoin('institutions as inst', 'inst.id', '=', 'ib.institution_id')
            ->orderByDesc('ib.created_at');

        if ($scope->institutionId) {
            $q->where('ib.institution_id', $scope->institutionId);
        }

        if ($scope->dateFrom) {
            $q->where('ib.created_at', '>=', $scope->dateFrom);
        }

        if ($scope->dateTo) {
            $q->where('ib.created_at', '<=', $scope->dateTo.' 23:59:59');
        }

        return $q->limit($scope->limit)->offset($scope->offset)
            ->select(
                'ib.id',
                'ib.original_filename',
                'inst.name_ar as institution',
                'ib.status',
                'ib.total_rows',
                'ib.valid_rows',
                'ib.error_rows',
                'ib.applied_rows',
                'ib.created_at',
                'ib.applied_at',
            )->get();
    }

    // ── 19. Institution summary ───────────────────────────────────────────────

    /** @return Collection<int, object> */
    public function runInstitutionSummary(ReportScope $scope): Collection
    {
        $q = DB::table('institutions as inst')
            ->leftJoin('institution_types as it', 'it.id', '=', 'inst.institution_type_id')
            ->orderBy('inst.name_ar');

        if ($scope->institutionId) {
            $q->where('inst.id', $scope->institutionId);
        }

        return $q->limit($scope->limit)->offset($scope->offset)
            ->select(
                'inst.code',
                'inst.name_ar',
                'it.name_ar as institution_type',
                'inst.is_active',
                'inst.created_at',
            )->get();
    }

    // ── 20. Notification events ───────────────────────────────────────────────

    /** @return Collection<int, object> */
    public function runNotificationEvents(ReportScope $scope): Collection
    {
        $q = DB::table('portal_notifications as pn')
            ->orderByDesc('pn.created_at');

        if ($scope->dateFrom) {
            $q->where('pn.created_at', '>=', $scope->dateFrom);
        }

        if ($scope->dateTo) {
            $q->where('pn.created_at', '<=', $scope->dateTo.' 23:59:59');
        }

        return $q->limit($scope->limit)->offset($scope->offset)
            ->select(
                'pn.notification_type',
                'pn.recipient_account_type',
                'pn.recipient_account_id',
                'pn.portal',
                'pn.priority',
                'pn.read_at',
                'pn.created_at',
            )->get();
    }

    // ── 21. Export / job history ──────────────────────────────────────────────

    /** @return Collection<int, object> */
    public function runExportJobHistory(ReportScope $scope): Collection
    {
        $q = DB::table('report_exports as re')
            ->orderByDesc('re.created_at');

        if ($scope->actorType !== 'admin') {
            // Staff can only see their own exports
            $q->where('re.actor_type', $scope->actorType)
                ->where('re.actor_account_id', $scope->actorAccountId);
        }

        if ($scope->dateFrom) {
            $q->where('re.created_at', '>=', $scope->dateFrom);
        }

        if ($scope->dateTo) {
            $q->where('re.created_at', '<=', $scope->dateTo.' 23:59:59');
        }

        return $q->limit($scope->limit)->offset($scope->offset)
            ->select(
                're.export_type',
                're.actor_type',
                're.actor_account_id',
                're.row_count',
                're.locale',
                're.created_at',
            )->get();
    }

    // ── Shared scope helpers ──────────────────────────────────────────────────

    /**
     * Restrict a query already joined to `class_groups as cg` to the staff
     * member's granted operational periods (no-op for full-scope actors).
     */
    private function restrictToAllowedPeriods(Builder $q, ReportScope $scope): void
    {
        if ($scope->isFullScope || $scope->allowedPeriodIds === null) {
            return;
        }

        $q->whereIn('cg.operational_period_id', $scope->allowedPeriodIds);
    }

    /**
     * Same restriction for queries not joined to class_groups: joins through
     * the given class-group FK column.
     */
    private function restrictToAllowedPeriodsViaClassGroup(Builder $q, ReportScope $scope, string $classGroupColumn): void
    {
        if ($scope->isFullScope || $scope->allowedPeriodIds === null) {
            return;
        }

        $q->join('class_groups as cg_scope', 'cg_scope.id', '=', $classGroupColumn)
            ->whereIn('cg_scope.operational_period_id', $scope->allowedPeriodIds);
    }
}

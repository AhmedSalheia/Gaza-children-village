<?php

declare(strict_types=1);

namespace Modules\Authorization\Data;

/**
 * Stable permission key catalogue (~40 keys).
 *
 * All production code MUST reference a constant here rather than raw strings.
 * Architecture test F17 scans for raw permission-key string usage.
 *
 * Format: resource.action
 * Groups: institution | semester | staff | person | account | audit | system
 */
final class PermissionKey
{
    // -----------------------------------------------------------------
    // Institution management
    // -----------------------------------------------------------------
    public const INSTITUTION_VIEW = 'institution.view';

    public const INSTITUTION_CREATE = 'institution.create';

    public const INSTITUTION_UPDATE = 'institution.update';

    public const INSTITUTION_TOGGLE = 'institution.toggle_active';

    // -----------------------------------------------------------------
    // Academic calendar
    // -----------------------------------------------------------------
    public const ACADEMIC_YEAR_VIEW = 'academic_year.view';

    public const ACADEMIC_YEAR_MANAGE = 'academic_year.manage';

    public const SEMESTER_VIEW = 'semester.view';

    public const SEMESTER_MANAGE = 'semester.manage';

    public const INST_SEMESTER_VIEW = 'institution_semester.view';

    public const INST_SEMESTER_OPEN = 'institution_semester.open';

    public const INST_SEMESTER_CLOSE = 'institution_semester.close';

    public const INST_SEMESTER_ARCHIVE = 'institution_semester.archive';

    public const OP_PERIOD_VIEW = 'operational_period.view';

    public const OP_PERIOD_MANAGE = 'operational_period.manage';

    // -----------------------------------------------------------------
    // Staff management
    // -----------------------------------------------------------------
    public const STAFF_PROFILE_VIEW = 'staff_profile.view';

    public const STAFF_PROFILE_CREATE = 'staff_profile.create';

    public const STAFF_PROFILE_UPDATE = 'staff_profile.update';

    public const STAFF_ASSIGN = 'staff.assign';

    public const STAFF_TRANSFER = 'staff.transfer';

    public const STAFF_POSITION_ASSIGN = 'staff_position.assign';

    public const STAFF_POSITION_END = 'staff_position.end';

    public const STAFF_POSITION_VIEW = 'staff_position.view';

    // -----------------------------------------------------------------
    // People / persons
    // -----------------------------------------------------------------
    public const PERSON_VIEW = 'person.view';

    public const PERSON_CREATE = 'person.create';

    public const PERSON_UPDATE = 'person.update';

    public const PERSON_VIEW_SENSITIVE = 'person.view_sensitive';

    // -----------------------------------------------------------------
    // Accounts
    // -----------------------------------------------------------------
    public const ACCOUNT_VIEW = 'account.view';

    public const ACCOUNT_CREATE = 'account.create';

    public const ACCOUNT_SUSPEND = 'account.suspend';

    public const ACCOUNT_LOCK = 'account.lock';

    public const ACCOUNT_REVOKE = 'account.revoke';

    public const ACCOUNT_ROLE_ASSIGN = 'account.role_assign';

    public const ACCOUNT_ROLE_REVOKE = 'account.role_revoke';

    // -----------------------------------------------------------------
    // Audit
    // -----------------------------------------------------------------
    public const AUDIT_VIEW = 'audit.view';

    public const AUDIT_EXPORT = 'audit.export';

    // -----------------------------------------------------------------
    // System / admin
    // -----------------------------------------------------------------
    public const SYSTEM_SETTINGS_VIEW = 'system.settings_view';

    public const SYSTEM_SETTINGS_UPDATE = 'system.settings_update';

    public const ROLE_VIEW = 'role.view';

    public const ROLE_ASSIGN = 'role.assign';

    // -----------------------------------------------------------------
    // Civil Registry
    // -----------------------------------------------------------------
    public const CIVIL_REGISTRY_LOOKUP = 'civil_registry.lookup';

    // -----------------------------------------------------------------
    // Student Registry
    // -----------------------------------------------------------------
    public const STUDENT_VIEW = 'student.view';

    public const STUDENT_VIEW_RESTRICTED = 'student.view_restricted';

    public const STUDENT_CREATE = 'student.create';

    public const STUDENT_UPDATE = 'student.update';

    public const STUDENT_MANAGE = 'student.manage';

    // -----------------------------------------------------------------
    // Guardian relationships
    // -----------------------------------------------------------------
    public const GUARDIAN_RELATIONSHIP_VIEW = 'guardian_relationship.view';

    public const GUARDIAN_RELATIONSHIP_MANAGE = 'guardian_relationship.manage';

    public const GUARDIAN_RELATIONSHIP_VERIFY = 'guardian_relationship.verify';

    // -----------------------------------------------------------------
    // Academic structure management
    // -----------------------------------------------------------------
    public const ACADEMIC_LEVEL_MANAGE = 'academic_level.manage';

    public const CLASSROOM_MANAGE = 'classroom.manage';

    public const CLASS_GROUP_MANAGE = 'class_group.manage';

    public const SUBJECT_MANAGE = 'subject.manage';

    public const SUBJECT_OFFERING_MANAGE = 'subject_offering.manage';

    // -----------------------------------------------------------------
    // Enrolment
    // -----------------------------------------------------------------
    public const ENROLLMENT_VIEW = 'enrollment.view';

    public const ENROLLMENT_MANAGE = 'enrollment.manage';

    public const ENROLLMENT_TRANSFER = 'enrollment.transfer';

    public const ENROLLMENT_PROMOTE = 'enrollment.promote';

    // -----------------------------------------------------------------
    // Import pipeline
    // -----------------------------------------------------------------
    public const IMPORT_UPLOAD = 'import.upload';

    public const IMPORT_REVIEW = 'import.review';

    public const IMPORT_APPLY = 'import.apply';

    // -----------------------------------------------------------------
    // Sensitive data export
    // -----------------------------------------------------------------
    public const SENSITIVE_EXPORT = 'data.sensitive_export';

    // -----------------------------------------------------------------
    // Teaching and homeroom assignments
    // -----------------------------------------------------------------
    public const TEACHING_ASSIGNMENT_READ   = 'teaching_assignment.read';

    public const TEACHING_ASSIGNMENT_MANAGE = 'teaching_assignment.manage';

    public const HOMEROOM_ASSIGNMENT_READ   = 'homeroom_assignment.read';

    public const HOMEROOM_ASSIGNMENT_MANAGE = 'homeroom_assignment.manage';

    // -----------------------------------------------------------------
    // Student attendance
    // -----------------------------------------------------------------
    public const STUDENT_ATTENDANCE_READ    = 'student_attendance.read';

    public const STUDENT_ATTENDANCE_ENTER   = 'student_attendance.enter';

    public const STUDENT_ATTENDANCE_SUBMIT  = 'student_attendance.submit';

    public const STUDENT_ATTENDANCE_RETURN  = 'student_attendance.return';

    public const STUDENT_ATTENDANCE_VERIFY  = 'student_attendance.verify';

    public const STUDENT_ATTENDANCE_CORRECT = 'student_attendance.correct';

    public const STUDENT_ATTENDANCE_PUBLISH = 'student_attendance.publish';

    // -----------------------------------------------------------------
    // Staff attendance
    // -----------------------------------------------------------------
    public const STAFF_ATTENDANCE_READ    = 'staff_attendance.read';

    public const STAFF_ATTENDANCE_ENTER   = 'staff_attendance.enter';

    public const STAFF_ATTENDANCE_VERIFY  = 'staff_attendance.verify';

    public const STAFF_ATTENDANCE_CORRECT = 'staff_attendance.correct';

    // -----------------------------------------------------------------
    // QR attendance scan review
    // -----------------------------------------------------------------
    public const ATTENDANCE_SCAN_REVIEW = 'attendance_scan.review';

    // -----------------------------------------------------------------
    // Attendance reports / exports
    // -----------------------------------------------------------------
    public const ATTENDANCE_REPORT_READ   = 'attendance_report.read';

    public const ATTENDANCE_REPORT_EXPORT = 'attendance_report.export';

    // -----------------------------------------------------------------
    // Assessment definitions
    // -----------------------------------------------------------------
    public const ASSESSMENT_READ   = 'assessment.read';

    public const ASSESSMENT_MANAGE = 'assessment.manage';

    // -----------------------------------------------------------------
    // Grading scales
    // -----------------------------------------------------------------
    public const GRADING_SCALE_READ   = 'grading_scale.read';

    public const GRADING_SCALE_MANAGE = 'grading_scale.manage';

    // -----------------------------------------------------------------
    // Mark-entry windows
    // -----------------------------------------------------------------
    public const MARK_WINDOW_READ   = 'mark_window.read';

    public const MARK_WINDOW_MANAGE = 'mark_window.manage';

    public const MARK_WINDOW_EXTEND = 'mark_window.extend';

    // -----------------------------------------------------------------
    // Marks (mark sheets / student marks)
    // -----------------------------------------------------------------
    public const MARKS_READ    = 'marks.read';

    public const MARKS_ENTER   = 'marks.enter';

    public const MARKS_SUBMIT  = 'marks.submit';

    public const MARKS_RETURN  = 'marks.return';

    public const MARKS_VERIFY  = 'marks.verify';

    public const MARKS_APPROVE = 'marks.approve';

    public const MARKS_CORRECT = 'marks.correct';

    // -----------------------------------------------------------------
    // Results / publication
    // -----------------------------------------------------------------
    public const RESULTS_READ   = 'results.read';

    public const RESULTS_PUBLISH = 'results.publish';

    public const RESULTS_REVOKE  = 'results.revoke';

    // -----------------------------------------------------------------
    // Result reports / exports
    // -----------------------------------------------------------------
    public const RESULT_REPORT_READ   = 'result_report.read';

    public const RESULT_REPORT_EXPORT = 'result_report.export';

    // -----------------------------------------------------------------
    // Workflow engine
    // -----------------------------------------------------------------
    public const WORKFLOW_READ   = 'workflow.read';

    public const WORKFLOW_MANAGE = 'workflow.manage';

    // -----------------------------------------------------------------
    // Secure attachments
    // -----------------------------------------------------------------
    public const ATTACHMENT_UPLOAD = 'attachment.upload';

    public const ATTACHMENT_READ   = 'attachment.read';

    // -----------------------------------------------------------------
    // Guardian correction requests
    // -----------------------------------------------------------------
    public const CORRECTION_REQUEST = 'correction.request';

    public const CORRECTION_REVIEW  = 'correction.review';

    public const CORRECTION_APPROVE = 'correction.approve';

    public const CORRECTION_APPLY   = 'correction.apply';

    // -----------------------------------------------------------------
    // Document requests and issuance
    // -----------------------------------------------------------------
    public const DOCUMENT_REQUEST = 'document.request';

    public const DOCUMENT_REVIEW  = 'document.review';

    public const DOCUMENT_APPROVE = 'document.approve';

    public const DOCUMENT_ISSUE   = 'document.issue';

    public const DOCUMENT_DOWNLOAD = 'document.download';

    public const DOCUMENT_CANCEL   = 'document.cancel';

    public const DOCUMENT_REISSUE  = 'document.reissue';

    // -----------------------------------------------------------------
    // Document templates
    // -----------------------------------------------------------------
    public const TEMPLATE_READ     = 'template.read';

    public const TEMPLATE_MANAGE   = 'template.manage';

    public const TEMPLATE_ACTIVATE = 'template.activate';

    // -----------------------------------------------------------------
    // Formal institution requests
    // -----------------------------------------------------------------
    public const FORMAL_REQUEST_PREPARE = 'formal_request.prepare';

    public const FORMAL_REQUEST_REVIEW  = 'formal_request.review';

    public const FORMAL_REQUEST_SIGN    = 'formal_request.sign';

    public const FORMAL_REQUEST_SUBMIT  = 'formal_request.submit';

    public const FORMAL_REQUEST_RESPOND = 'formal_request.respond';

    // -----------------------------------------------------------------
    // Electronic approvals
    // -----------------------------------------------------------------
    public const ELECTRONIC_APPROVAL_CREATE = 'electronic_approval.create';

    public const ELECTRONIC_APPROVAL_REVOKE = 'electronic_approval.revoke';

    // -----------------------------------------------------------------
    // In-app notifications
    // -----------------------------------------------------------------
    public const NOTIFICATION_READ   = 'notification.read';

    public const NOTIFICATION_MANAGE = 'notification.manage';

    // -----------------------------------------------------------------
    // Reports and exports
    // -----------------------------------------------------------------
    public const REPORT_READ              = 'report.read';

    public const REPORT_ORGANIZATION_READ = 'report.organization_read';

    public const EXPORT_CREATE   = 'export.create';

    public const EXPORT_DOWNLOAD = 'export.download';

    // -----------------------------------------------------------------
    // Document public verification
    // -----------------------------------------------------------------
    public const DOCUMENT_VERIFICATION_AUTHENTICATED_READ = 'document_verification.authenticated_read';

    // -----------------------------------------------------------------
    // All keys — used by seeder and architecture test.
    // -----------------------------------------------------------------

    /** @return list<string> */
    public static function all(): array
    {
        $r = new \ReflectionClass(self::class);

        return array_values(
            array_filter(
                array_map(fn ($c) => $c->getValue(), $r->getReflectionConstants()),
                fn ($v) => is_string($v) && str_contains((string) $v, '.')
            )
        );
    }
}

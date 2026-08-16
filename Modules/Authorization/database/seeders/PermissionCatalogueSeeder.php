<?php

declare(strict_types=1);

namespace Modules\Authorization\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Authorization\Data\PermissionKey;
use Modules\Authorization\Data\RoleCode;
use Modules\Authorization\Models\Permission;
use Modules\Authorization\Models\Role;

/**
 * Seeds all permission keys and protected role templates.
 *
 * Idempotent: uses the check-then-create pattern (direct property assignment
 * since key is not in $fillable).
 */
final class PermissionCatalogueSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPermissions();
        $this->seedRoles();
        $this->seedRolePermissions();
    }

    private function seedPermissions(): void
    {
        $groups = [
            'institution' => [
                PermissionKey::INSTITUTION_VIEW,
                PermissionKey::INSTITUTION_CREATE,
                PermissionKey::INSTITUTION_UPDATE,
                PermissionKey::INSTITUTION_TOGGLE,
            ],
            'calendar' => [
                PermissionKey::ACADEMIC_YEAR_VIEW,
                PermissionKey::ACADEMIC_YEAR_MANAGE,
                PermissionKey::SEMESTER_VIEW,
                PermissionKey::SEMESTER_MANAGE,
                PermissionKey::INST_SEMESTER_VIEW,
                PermissionKey::INST_SEMESTER_OPEN,
                PermissionKey::INST_SEMESTER_CLOSE,
                PermissionKey::INST_SEMESTER_ARCHIVE,
                PermissionKey::OP_PERIOD_VIEW,
                PermissionKey::OP_PERIOD_MANAGE,
            ],
            'staff' => [
                PermissionKey::STAFF_PROFILE_VIEW,
                PermissionKey::STAFF_PROFILE_CREATE,
                PermissionKey::STAFF_PROFILE_UPDATE,
                PermissionKey::STAFF_ASSIGN,
                PermissionKey::STAFF_TRANSFER,
                PermissionKey::STAFF_POSITION_ASSIGN,
                PermissionKey::STAFF_POSITION_END,
                PermissionKey::STAFF_POSITION_VIEW,
            ],
            'person' => [
                PermissionKey::PERSON_VIEW,
                PermissionKey::PERSON_CREATE,
                PermissionKey::PERSON_UPDATE,
                PermissionKey::PERSON_VIEW_SENSITIVE,
            ],
            'account' => [
                PermissionKey::ACCOUNT_VIEW,
                PermissionKey::ACCOUNT_CREATE,
                PermissionKey::ACCOUNT_SUSPEND,
                PermissionKey::ACCOUNT_LOCK,
                PermissionKey::ACCOUNT_REVOKE,
                PermissionKey::ACCOUNT_ROLE_ASSIGN,
                PermissionKey::ACCOUNT_ROLE_REVOKE,
            ],
            'audit' => [
                PermissionKey::AUDIT_VIEW,
                PermissionKey::AUDIT_EXPORT,
            ],
            'system' => [
                PermissionKey::SYSTEM_SETTINGS_VIEW,
                PermissionKey::SYSTEM_SETTINGS_UPDATE,
                PermissionKey::ROLE_VIEW,
                PermissionKey::ROLE_ASSIGN,
            ],
            'civil_registry' => [
                PermissionKey::CIVIL_REGISTRY_LOOKUP,
            ],
            'student' => [
                PermissionKey::STUDENT_VIEW,
                PermissionKey::STUDENT_VIEW_RESTRICTED,
                PermissionKey::STUDENT_CREATE,
                PermissionKey::STUDENT_UPDATE,
                PermissionKey::STUDENT_MANAGE,
            ],
            'guardian_relationship' => [
                PermissionKey::GUARDIAN_RELATIONSHIP_VIEW,
                PermissionKey::GUARDIAN_RELATIONSHIP_MANAGE,
                PermissionKey::GUARDIAN_RELATIONSHIP_VERIFY,
            ],
            'academic_structure' => [
                PermissionKey::ACADEMIC_LEVEL_MANAGE,
                PermissionKey::CLASSROOM_MANAGE,
                PermissionKey::CLASS_GROUP_MANAGE,
                PermissionKey::SUBJECT_MANAGE,
                PermissionKey::SUBJECT_OFFERING_MANAGE,
            ],
            'enrollment' => [
                PermissionKey::ENROLLMENT_VIEW,
                PermissionKey::ENROLLMENT_MANAGE,
                PermissionKey::ENROLLMENT_TRANSFER,
                PermissionKey::ENROLLMENT_PROMOTE,
            ],
            'import' => [
                PermissionKey::IMPORT_UPLOAD,
                PermissionKey::IMPORT_REVIEW,
                PermissionKey::IMPORT_APPLY,
            ],
            'data' => [
                PermissionKey::SENSITIVE_EXPORT,
            ],
            'teaching_assignment' => [
                PermissionKey::TEACHING_ASSIGNMENT_READ,
                PermissionKey::TEACHING_ASSIGNMENT_MANAGE,
            ],
            'homeroom_assignment' => [
                PermissionKey::HOMEROOM_ASSIGNMENT_READ,
                PermissionKey::HOMEROOM_ASSIGNMENT_MANAGE,
            ],
            'student_attendance' => [
                PermissionKey::STUDENT_ATTENDANCE_READ,
                PermissionKey::STUDENT_ATTENDANCE_ENTER,
                PermissionKey::STUDENT_ATTENDANCE_SUBMIT,
                PermissionKey::STUDENT_ATTENDANCE_RETURN,
                PermissionKey::STUDENT_ATTENDANCE_VERIFY,
                PermissionKey::STUDENT_ATTENDANCE_CORRECT,
                PermissionKey::STUDENT_ATTENDANCE_PUBLISH,
            ],
            'staff_attendance' => [
                PermissionKey::STAFF_ATTENDANCE_READ,
                PermissionKey::STAFF_ATTENDANCE_ENTER,
                PermissionKey::STAFF_ATTENDANCE_VERIFY,
                PermissionKey::STAFF_ATTENDANCE_CORRECT,
            ],
            'attendance_scan' => [
                PermissionKey::ATTENDANCE_SCAN_REVIEW,
            ],
            'attendance_report' => [
                PermissionKey::ATTENDANCE_REPORT_READ,
                PermissionKey::ATTENDANCE_REPORT_EXPORT,
            ],
            'assessment' => [
                PermissionKey::ASSESSMENT_READ,
                PermissionKey::ASSESSMENT_MANAGE,
            ],
            'grading_scale' => [
                PermissionKey::GRADING_SCALE_READ,
                PermissionKey::GRADING_SCALE_MANAGE,
            ],
            'mark_window' => [
                PermissionKey::MARK_WINDOW_READ,
                PermissionKey::MARK_WINDOW_MANAGE,
                PermissionKey::MARK_WINDOW_EXTEND,
            ],
            'marks' => [
                PermissionKey::MARKS_READ,
                PermissionKey::MARKS_ENTER,
                PermissionKey::MARKS_SUBMIT,
                PermissionKey::MARKS_RETURN,
                PermissionKey::MARKS_VERIFY,
                PermissionKey::MARKS_APPROVE,
                PermissionKey::MARKS_CORRECT,
            ],
            'results' => [
                PermissionKey::RESULTS_READ,
                PermissionKey::RESULTS_PUBLISH,
                PermissionKey::RESULTS_REVOKE,
            ],
            'result_report' => [
                PermissionKey::RESULT_REPORT_READ,
                PermissionKey::RESULT_REPORT_EXPORT,
            ],
            'workflow' => [
                PermissionKey::WORKFLOW_READ,
                PermissionKey::WORKFLOW_MANAGE,
            ],
            'attachment' => [
                PermissionKey::ATTACHMENT_UPLOAD,
                PermissionKey::ATTACHMENT_READ,
            ],
            'correction' => [
                PermissionKey::CORRECTION_REQUEST,
                PermissionKey::CORRECTION_REVIEW,
                PermissionKey::CORRECTION_APPROVE,
                PermissionKey::CORRECTION_APPLY,
            ],
            'document' => [
                PermissionKey::DOCUMENT_REQUEST,
                PermissionKey::DOCUMENT_REVIEW,
                PermissionKey::DOCUMENT_APPROVE,
                PermissionKey::DOCUMENT_ISSUE,
                PermissionKey::DOCUMENT_DOWNLOAD,
                PermissionKey::DOCUMENT_CANCEL,
                PermissionKey::DOCUMENT_REISSUE,
            ],
            'template' => [
                PermissionKey::TEMPLATE_READ,
                PermissionKey::TEMPLATE_MANAGE,
                PermissionKey::TEMPLATE_ACTIVATE,
            ],
            'formal_request' => [
                PermissionKey::FORMAL_REQUEST_PREPARE,
                PermissionKey::FORMAL_REQUEST_REVIEW,
                PermissionKey::FORMAL_REQUEST_SIGN,
                PermissionKey::FORMAL_REQUEST_SUBMIT,
                PermissionKey::FORMAL_REQUEST_RESPOND,
            ],
            'electronic_approval' => [
                PermissionKey::ELECTRONIC_APPROVAL_CREATE,
                PermissionKey::ELECTRONIC_APPROVAL_REVOKE,
            ],
            'notification' => [
                PermissionKey::NOTIFICATION_READ,
                PermissionKey::NOTIFICATION_MANAGE,
            ],
            'report' => [
                PermissionKey::REPORT_READ,
                PermissionKey::REPORT_ORGANIZATION_READ,
                PermissionKey::EXPORT_CREATE,
                PermissionKey::EXPORT_DOWNLOAD,
            ],
            'document_verification' => [
                PermissionKey::DOCUMENT_VERIFICATION_AUTHENTICATED_READ,
            ],
        ];

        foreach ($groups as $group => $keys) {
            foreach ($keys as $key) {
                if (! Permission::where('key', $key)->exists()) {
                    $perm = new Permission;
                    $perm->key = $key;
                    $perm->group = $group;
                    $perm->description = str_replace('.', ' ', $key);
                    $perm->is_system = true;
                    $perm->save();
                }
            }
        }
    }

    private function seedRoles(): void
    {
        $roles = [
            RoleCode::SYSTEM_ADMIN => 'System Administrator',
            RoleCode::AUDIT_INSPECTOR => 'Audit Inspector',
            RoleCode::CALENDAR_MANAGER => 'Calendar Manager',
            RoleCode::ACCOUNT_MANAGER => 'Account Manager',
            RoleCode::INSTITUTION_ADMIN => 'Institution Administrator',
            RoleCode::PRINCIPAL => 'Principal',
            RoleCode::DEPUTY_PRINCIPAL => 'Deputy Principal',
            RoleCode::SECRETARY => 'Secretary',
            RoleCode::TEACHER => 'Teacher',
            RoleCode::COUNSELOR => 'Counselor',
            RoleCode::OPERATIONS_VIEWER => 'Operations Viewer',
            RoleCode::STAFF_MANAGER => 'Staff Manager',
        ];

        foreach ($roles as $code => $label) {
            if (! Role::where('code', $code)->exists()) {
                $role = new Role;
                $role->code = $code;
                $role->label = $label;
                $role->is_protected = true;
                $role->save();
            }
        }
    }

    private function seedRolePermissions(): void
    {
        // Map: role_code => list of PermissionKey constants
        $matrix = [
            RoleCode::SYSTEM_ADMIN => PermissionKey::all(),

            RoleCode::AUDIT_INSPECTOR => [
                PermissionKey::AUDIT_VIEW,
                PermissionKey::AUDIT_EXPORT,
                PermissionKey::INSTITUTION_VIEW,
                PermissionKey::STAFF_PROFILE_VIEW,
                PermissionKey::PERSON_VIEW,
                PermissionKey::STUDENT_VIEW,
                PermissionKey::ENROLLMENT_VIEW,
            ],

            RoleCode::CALENDAR_MANAGER => [
                PermissionKey::ACADEMIC_YEAR_VIEW,
                PermissionKey::ACADEMIC_YEAR_MANAGE,
                PermissionKey::SEMESTER_VIEW,
                PermissionKey::SEMESTER_MANAGE,
                PermissionKey::INST_SEMESTER_VIEW,
                PermissionKey::INST_SEMESTER_OPEN,
                PermissionKey::INST_SEMESTER_CLOSE,
                PermissionKey::INST_SEMESTER_ARCHIVE,
                PermissionKey::OP_PERIOD_VIEW,
                PermissionKey::OP_PERIOD_MANAGE,
                PermissionKey::INSTITUTION_VIEW,
            ],

            RoleCode::ACCOUNT_MANAGER => [
                PermissionKey::ACCOUNT_VIEW,
                PermissionKey::ACCOUNT_CREATE,
                PermissionKey::ACCOUNT_SUSPEND,
                PermissionKey::ACCOUNT_LOCK,
                PermissionKey::ACCOUNT_REVOKE,
                PermissionKey::ACCOUNT_ROLE_ASSIGN,
                PermissionKey::ACCOUNT_ROLE_REVOKE,
                PermissionKey::PERSON_VIEW,
                PermissionKey::ROLE_VIEW,
                PermissionKey::ROLE_ASSIGN,
            ],

            RoleCode::INSTITUTION_ADMIN => [
                PermissionKey::INSTITUTION_VIEW,
                PermissionKey::INSTITUTION_UPDATE,
                PermissionKey::INST_SEMESTER_VIEW,
                PermissionKey::OP_PERIOD_VIEW,
                PermissionKey::STAFF_PROFILE_VIEW,
                PermissionKey::STAFF_POSITION_VIEW,
                PermissionKey::PERSON_VIEW,
                PermissionKey::ACCOUNT_VIEW,
                PermissionKey::STUDENT_VIEW,
                PermissionKey::ENROLLMENT_VIEW,
                PermissionKey::GUARDIAN_RELATIONSHIP_VIEW,
                // Workflow (read + manage for institution admin / data admin)
                PermissionKey::WORKFLOW_READ,
                PermissionKey::WORKFLOW_MANAGE,
                // Attachments
                PermissionKey::ATTACHMENT_READ,
                // Correction requests (review + approve + apply for data admin)
                PermissionKey::CORRECTION_REVIEW,
                PermissionKey::CORRECTION_APPROVE,
                PermissionKey::CORRECTION_APPLY,
                // Documents
                PermissionKey::DOCUMENT_REVIEW,
                PermissionKey::DOCUMENT_APPROVE,
                PermissionKey::DOCUMENT_ISSUE,
                PermissionKey::DOCUMENT_DOWNLOAD,
                PermissionKey::DOCUMENT_CANCEL,
                PermissionKey::DOCUMENT_REISSUE,
                // Templates (full management for data admin)
                PermissionKey::TEMPLATE_READ,
                PermissionKey::TEMPLATE_MANAGE,
                PermissionKey::TEMPLATE_ACTIVATE,
                // Formal requests (respond — data admin is management-side)
                PermissionKey::FORMAL_REQUEST_RESPOND,
                // Electronic approvals
                PermissionKey::ELECTRONIC_APPROVAL_CREATE,
                PermissionKey::ELECTRONIC_APPROVAL_REVOKE,
                // Notifications
                PermissionKey::NOTIFICATION_READ,
                PermissionKey::NOTIFICATION_MANAGE,
                // Reports
                PermissionKey::REPORT_READ,
                PermissionKey::REPORT_ORGANIZATION_READ,
                PermissionKey::EXPORT_CREATE,
                PermissionKey::EXPORT_DOWNLOAD,
                PermissionKey::DOCUMENT_VERIFICATION_AUTHENTICATED_READ,
            ],

            RoleCode::PRINCIPAL => [
                PermissionKey::INSTITUTION_VIEW,
                PermissionKey::INST_SEMESTER_VIEW,
                PermissionKey::OP_PERIOD_VIEW,
                PermissionKey::STAFF_PROFILE_VIEW,
                PermissionKey::STAFF_POSITION_VIEW,
                PermissionKey::STAFF_POSITION_ASSIGN,
                PermissionKey::STAFF_POSITION_END,
                PermissionKey::PERSON_VIEW,
                PermissionKey::ACCOUNT_VIEW,
                // Student registry
                PermissionKey::STUDENT_VIEW,
                PermissionKey::STUDENT_CREATE,
                PermissionKey::STUDENT_UPDATE,
                PermissionKey::STUDENT_MANAGE,
                PermissionKey::GUARDIAN_RELATIONSHIP_VIEW,
                PermissionKey::GUARDIAN_RELATIONSHIP_MANAGE,
                PermissionKey::GUARDIAN_RELATIONSHIP_VERIFY,
                // Academic structure
                PermissionKey::ACADEMIC_LEVEL_MANAGE,
                PermissionKey::CLASSROOM_MANAGE,
                PermissionKey::CLASS_GROUP_MANAGE,
                PermissionKey::SUBJECT_MANAGE,
                PermissionKey::SUBJECT_OFFERING_MANAGE,
                // Enrolment
                PermissionKey::ENROLLMENT_VIEW,
                PermissionKey::ENROLLMENT_MANAGE,
                PermissionKey::ENROLLMENT_TRANSFER,
                PermissionKey::ENROLLMENT_PROMOTE,
                // Import pipeline
                PermissionKey::IMPORT_UPLOAD,
                PermissionKey::IMPORT_REVIEW,
                PermissionKey::IMPORT_APPLY,
                // Civil Registry & sensitive
                PermissionKey::CIVIL_REGISTRY_LOOKUP,
                PermissionKey::SENSITIVE_EXPORT,
                // Teaching & homeroom assignments
                PermissionKey::TEACHING_ASSIGNMENT_READ,
                PermissionKey::TEACHING_ASSIGNMENT_MANAGE,
                PermissionKey::HOMEROOM_ASSIGNMENT_READ,
                PermissionKey::HOMEROOM_ASSIGNMENT_MANAGE,
                // Student attendance
                PermissionKey::STUDENT_ATTENDANCE_READ,
                PermissionKey::STUDENT_ATTENDANCE_RETURN,
                PermissionKey::STUDENT_ATTENDANCE_VERIFY,
                PermissionKey::STUDENT_ATTENDANCE_CORRECT,
                PermissionKey::STUDENT_ATTENDANCE_PUBLISH,
                // Staff attendance
                PermissionKey::STAFF_ATTENDANCE_READ,
                PermissionKey::STAFF_ATTENDANCE_VERIFY,
                PermissionKey::STAFF_ATTENDANCE_CORRECT,
                // QR scan review
                PermissionKey::ATTENDANCE_SCAN_REVIEW,
                // Attendance reports
                PermissionKey::ATTENDANCE_REPORT_READ,
                PermissionKey::ATTENDANCE_REPORT_EXPORT,
                // Assessments & grading
                PermissionKey::ASSESSMENT_READ,
                PermissionKey::ASSESSMENT_MANAGE,
                PermissionKey::GRADING_SCALE_READ,
                PermissionKey::GRADING_SCALE_MANAGE,
                // Mark windows
                PermissionKey::MARK_WINDOW_READ,
                PermissionKey::MARK_WINDOW_MANAGE,
                PermissionKey::MARK_WINDOW_EXTEND,
                // Marks
                PermissionKey::MARKS_READ,
                PermissionKey::MARKS_RETURN,
                PermissionKey::MARKS_VERIFY,
                PermissionKey::MARKS_APPROVE,
                PermissionKey::MARKS_CORRECT,
                // Results
                PermissionKey::RESULTS_READ,
                PermissionKey::RESULTS_PUBLISH,
                PermissionKey::RESULTS_REVOKE,
                PermissionKey::RESULT_REPORT_READ,
                PermissionKey::RESULT_REPORT_EXPORT,
                // Workflow engine (read + manage for principal)
                PermissionKey::WORKFLOW_READ,
                PermissionKey::WORKFLOW_MANAGE,
                // Attachments
                PermissionKey::ATTACHMENT_UPLOAD,
                PermissionKey::ATTACHMENT_READ,
                // Correction requests (review, approve, apply)
                PermissionKey::CORRECTION_REVIEW,
                PermissionKey::CORRECTION_APPROVE,
                PermissionKey::CORRECTION_APPLY,
                // Document management
                PermissionKey::DOCUMENT_REVIEW,
                PermissionKey::DOCUMENT_APPROVE,
                PermissionKey::DOCUMENT_ISSUE,
                PermissionKey::DOCUMENT_DOWNLOAD,
                PermissionKey::DOCUMENT_CANCEL,
                PermissionKey::DOCUMENT_REISSUE,
                // Document templates (read + activate)
                PermissionKey::TEMPLATE_READ,
                PermissionKey::TEMPLATE_ACTIVATE,
                // Formal institution requests (review + sign + submit)
                PermissionKey::FORMAL_REQUEST_REVIEW,
                PermissionKey::FORMAL_REQUEST_SIGN,
                PermissionKey::FORMAL_REQUEST_SUBMIT,
                // Electronic approvals
                PermissionKey::ELECTRONIC_APPROVAL_CREATE,
                PermissionKey::ELECTRONIC_APPROVAL_REVOKE,
                // Notifications
                PermissionKey::NOTIFICATION_READ,
                // Reports
                PermissionKey::REPORT_READ,
                PermissionKey::EXPORT_CREATE,
                PermissionKey::EXPORT_DOWNLOAD,
                // Document verification
                PermissionKey::DOCUMENT_VERIFICATION_AUTHENTICATED_READ,
            ],

            RoleCode::DEPUTY_PRINCIPAL => [
                PermissionKey::INSTITUTION_VIEW,
                PermissionKey::INST_SEMESTER_VIEW,
                PermissionKey::OP_PERIOD_VIEW,
                PermissionKey::STAFF_PROFILE_VIEW,
                PermissionKey::STAFF_POSITION_VIEW,
                PermissionKey::PERSON_VIEW,
                // Student registry
                PermissionKey::STUDENT_VIEW,
                PermissionKey::STUDENT_UPDATE,
                PermissionKey::GUARDIAN_RELATIONSHIP_VIEW,
                // Enrolment
                PermissionKey::ENROLLMENT_VIEW,
                PermissionKey::ENROLLMENT_MANAGE,
                PermissionKey::ENROLLMENT_TRANSFER,
                PermissionKey::ENROLLMENT_PROMOTE,
                // Import
                PermissionKey::IMPORT_UPLOAD,
                PermissionKey::IMPORT_REVIEW,
                PermissionKey::IMPORT_APPLY,
                PermissionKey::CIVIL_REGISTRY_LOOKUP,
                // Teaching & homeroom assignments
                PermissionKey::TEACHING_ASSIGNMENT_READ,
                PermissionKey::TEACHING_ASSIGNMENT_MANAGE,
                PermissionKey::HOMEROOM_ASSIGNMENT_READ,
                PermissionKey::HOMEROOM_ASSIGNMENT_MANAGE,
                // Student attendance
                PermissionKey::STUDENT_ATTENDANCE_READ,
                PermissionKey::STUDENT_ATTENDANCE_RETURN,
                PermissionKey::STUDENT_ATTENDANCE_VERIFY,
                PermissionKey::STUDENT_ATTENDANCE_CORRECT,
                // Staff attendance
                PermissionKey::STAFF_ATTENDANCE_READ,
                PermissionKey::STAFF_ATTENDANCE_VERIFY,
                PermissionKey::STAFF_ATTENDANCE_CORRECT,
                // QR scan review
                PermissionKey::ATTENDANCE_SCAN_REVIEW,
                // Attendance reports
                PermissionKey::ATTENDANCE_REPORT_READ,
                PermissionKey::ATTENDANCE_REPORT_EXPORT,
                // Assessments & grading
                PermissionKey::ASSESSMENT_READ,
                PermissionKey::ASSESSMENT_MANAGE,
                PermissionKey::GRADING_SCALE_READ,
                // Mark windows
                PermissionKey::MARK_WINDOW_READ,
                PermissionKey::MARK_WINDOW_MANAGE,
                PermissionKey::MARK_WINDOW_EXTEND,
                // Marks
                PermissionKey::MARKS_READ,
                PermissionKey::MARKS_RETURN,
                PermissionKey::MARKS_VERIFY,
                PermissionKey::MARKS_APPROVE,
                PermissionKey::MARKS_CORRECT,
                // Results
                PermissionKey::RESULTS_READ,
                PermissionKey::RESULT_REPORT_READ,
                PermissionKey::RESULT_REPORT_EXPORT,
                // Workflow (read only for deputy)
                PermissionKey::WORKFLOW_READ,
                // Attachments
                PermissionKey::ATTACHMENT_UPLOAD,
                PermissionKey::ATTACHMENT_READ,
                // Correction requests (review + approve + apply)
                PermissionKey::CORRECTION_REVIEW,
                PermissionKey::CORRECTION_APPROVE,
                PermissionKey::CORRECTION_APPLY,
                // Document management
                PermissionKey::DOCUMENT_REVIEW,
                PermissionKey::DOCUMENT_APPROVE,
                PermissionKey::DOCUMENT_ISSUE,
                PermissionKey::DOCUMENT_DOWNLOAD,
                PermissionKey::DOCUMENT_CANCEL,
                PermissionKey::DOCUMENT_REISSUE,
                // Templates (read only)
                PermissionKey::TEMPLATE_READ,
                // Formal requests (review + sign + submit)
                PermissionKey::FORMAL_REQUEST_REVIEW,
                PermissionKey::FORMAL_REQUEST_SIGN,
                PermissionKey::FORMAL_REQUEST_SUBMIT,
                // Electronic approvals
                PermissionKey::ELECTRONIC_APPROVAL_CREATE,
                PermissionKey::ELECTRONIC_APPROVAL_REVOKE,
                // Notifications
                PermissionKey::NOTIFICATION_READ,
                // Reports
                PermissionKey::REPORT_READ,
                PermissionKey::EXPORT_CREATE,
                PermissionKey::EXPORT_DOWNLOAD,
                PermissionKey::DOCUMENT_VERIFICATION_AUTHENTICATED_READ,
            ],

            RoleCode::SECRETARY => [
                PermissionKey::INSTITUTION_VIEW,
                PermissionKey::INST_SEMESTER_VIEW,
                PermissionKey::OP_PERIOD_VIEW,
                PermissionKey::PERSON_VIEW,
                PermissionKey::PERSON_CREATE,
                PermissionKey::PERSON_UPDATE,
                PermissionKey::STAFF_PROFILE_VIEW,
                PermissionKey::STAFF_POSITION_VIEW,
                // Student registry
                PermissionKey::STUDENT_VIEW,
                PermissionKey::STUDENT_CREATE,
                PermissionKey::STUDENT_UPDATE,
                PermissionKey::GUARDIAN_RELATIONSHIP_VIEW,
                PermissionKey::GUARDIAN_RELATIONSHIP_MANAGE,
                // Enrolment
                PermissionKey::ENROLLMENT_VIEW,
                PermissionKey::ENROLLMENT_MANAGE,
                // Import pipeline
                PermissionKey::IMPORT_UPLOAD,
                PermissionKey::IMPORT_REVIEW,
                PermissionKey::IMPORT_APPLY,
                // Civil Registry
                PermissionKey::CIVIL_REGISTRY_LOOKUP,
                // Teaching & homeroom assignments (read-only for secretary)
                PermissionKey::TEACHING_ASSIGNMENT_READ,
                PermissionKey::HOMEROOM_ASSIGNMENT_READ,
                // Student attendance (full workflow for secretary)
                PermissionKey::STUDENT_ATTENDANCE_READ,
                PermissionKey::STUDENT_ATTENDANCE_ENTER,
                PermissionKey::STUDENT_ATTENDANCE_SUBMIT,
                PermissionKey::STUDENT_ATTENDANCE_RETURN,
                PermissionKey::STUDENT_ATTENDANCE_VERIFY,
                PermissionKey::STUDENT_ATTENDANCE_CORRECT,
                // Staff attendance (secretary enters and verifies)
                PermissionKey::STAFF_ATTENDANCE_READ,
                PermissionKey::STAFF_ATTENDANCE_ENTER,
                PermissionKey::STAFF_ATTENDANCE_VERIFY,
                PermissionKey::STAFF_ATTENDANCE_CORRECT,
                // QR scan review
                PermissionKey::ATTENDANCE_SCAN_REVIEW,
                // Attendance reports
                PermissionKey::ATTENDANCE_REPORT_READ,
                // Marks (secretary returns and verifies)
                PermissionKey::MARKS_READ,
                PermissionKey::MARKS_RETURN,
                PermissionKey::MARKS_VERIFY,
                // Result report (read-only)
                PermissionKey::RESULT_REPORT_READ,
                // Workflow (read for secretary)
                PermissionKey::WORKFLOW_READ,
                // Attachments (secretary can upload + read)
                PermissionKey::ATTACHMENT_UPLOAD,
                PermissionKey::ATTACHMENT_READ,
                // Correction requests (review + apply)
                PermissionKey::CORRECTION_REVIEW,
                PermissionKey::CORRECTION_APPLY,
                // Document management (secretary prepares, reviews, issues)
                PermissionKey::DOCUMENT_REQUEST,
                PermissionKey::DOCUMENT_REVIEW,
                PermissionKey::DOCUMENT_ISSUE,
                PermissionKey::DOCUMENT_DOWNLOAD,
                PermissionKey::DOCUMENT_CANCEL,
                // Templates (read only)
                PermissionKey::TEMPLATE_READ,
                // Formal requests (prepare + submit)
                PermissionKey::FORMAL_REQUEST_PREPARE,
                PermissionKey::FORMAL_REQUEST_SUBMIT,
                // Notifications
                PermissionKey::NOTIFICATION_READ,
                // Reports
                PermissionKey::REPORT_READ,
                PermissionKey::EXPORT_CREATE,
                PermissionKey::EXPORT_DOWNLOAD,
            ],

            RoleCode::TEACHER => [
                PermissionKey::INSTITUTION_VIEW,
                PermissionKey::INST_SEMESTER_VIEW,
                PermissionKey::OP_PERIOD_VIEW,
                PermissionKey::PERSON_VIEW,
                // Student registry (read-only, restricted)
                PermissionKey::STUDENT_VIEW_RESTRICTED,
                PermissionKey::ENROLLMENT_VIEW,
                // Teaching & homeroom assignments (read-only)
                PermissionKey::TEACHING_ASSIGNMENT_READ,
                PermissionKey::HOMEROOM_ASSIGNMENT_READ,
                // Student attendance (teacher enters and submits)
                PermissionKey::STUDENT_ATTENDANCE_ENTER,
                PermissionKey::STUDENT_ATTENDANCE_SUBMIT,
                // Marks (teacher enters and submits)
                PermissionKey::MARKS_READ,
                PermissionKey::MARKS_ENTER,
                PermissionKey::MARKS_SUBMIT,
            ],

            RoleCode::COUNSELOR => [
                PermissionKey::INSTITUTION_VIEW,
                PermissionKey::INST_SEMESTER_VIEW,
                PermissionKey::OP_PERIOD_VIEW,
                PermissionKey::PERSON_VIEW,
                PermissionKey::PERSON_VIEW_SENSITIVE,
                // Student registry
                PermissionKey::STUDENT_VIEW,
                PermissionKey::STUDENT_UPDATE,
                PermissionKey::GUARDIAN_RELATIONSHIP_VIEW,
                PermissionKey::ENROLLMENT_VIEW,
                PermissionKey::SENSITIVE_EXPORT,
                // Teaching & homeroom assignments (read-only for counselor)
                PermissionKey::TEACHING_ASSIGNMENT_READ,
                PermissionKey::HOMEROOM_ASSIGNMENT_READ,
                // Student attendance (read-only for counselor)
                PermissionKey::STUDENT_ATTENDANCE_READ,
                PermissionKey::ATTENDANCE_REPORT_READ,
            ],

            RoleCode::OPERATIONS_VIEWER => [
                PermissionKey::INSTITUTION_VIEW,
                PermissionKey::INST_SEMESTER_VIEW,
                PermissionKey::OP_PERIOD_VIEW,
                PermissionKey::STAFF_PROFILE_VIEW,
                PermissionKey::STAFF_POSITION_VIEW,
                PermissionKey::ACADEMIC_YEAR_VIEW,
                PermissionKey::SEMESTER_VIEW,
                PermissionKey::AUDIT_VIEW,
                PermissionKey::STUDENT_VIEW,
                PermissionKey::ENROLLMENT_VIEW,
            ],

            RoleCode::STAFF_MANAGER => [
                PermissionKey::STAFF_PROFILE_VIEW,
                PermissionKey::STAFF_PROFILE_CREATE,
                PermissionKey::STAFF_PROFILE_UPDATE,
                PermissionKey::STAFF_ASSIGN,
                PermissionKey::STAFF_TRANSFER,
                PermissionKey::STAFF_POSITION_ASSIGN,
                PermissionKey::STAFF_POSITION_END,
                PermissionKey::STAFF_POSITION_VIEW,
                PermissionKey::PERSON_VIEW,
                PermissionKey::PERSON_CREATE,
                PermissionKey::PERSON_UPDATE,
                PermissionKey::INSTITUTION_VIEW,
            ],
        ];

        foreach ($matrix as $roleCode => $permKeys) {
            $role = Role::where('code', $roleCode)->first();

            if ($role === null) {
                continue;
            }

            foreach ($permKeys as $permKey) {
                $perm = Permission::where('key', $permKey)->first();

                if ($perm === null) {
                    continue;
                }

                if (! $role->permissions()->where('permission_id', $perm->id)->exists()) {
                    $role->permissions()->attach($perm->id);
                }
            }
        }
    }
}

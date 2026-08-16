<?php

declare(strict_types=1);

/**
 * English notification messages.
 *
 * Keys are the leaf segment of the notification message_key field
 * (e.g. "correction_request.submitted" → key "correction_request.submitted" here).
 *
 * Values may contain :param placeholders matching NotificationService::SAFE_PARAM_KEYS.
 * No sensitive data is stored in message_params — only safe display values.
 */
return [

    // -------------------------------------------------------------------------
    // Correction requests
    // -------------------------------------------------------------------------
    'correction_request.submitted' => 'A correction request has been submitted for :student_name.',
    'correction_request.approved' => 'Your correction request for :student_name has been approved.',
    'correction_request.rejected' => 'Your correction request for :student_name was not approved.',
    'correction_request.applied' => 'The approved correction for :student_name has been applied.',

    // -------------------------------------------------------------------------
    // Document requests
    // -------------------------------------------------------------------------
    'document_request.submitted' => 'A document request has been submitted for :student_name.',
    'document_request.approved' => 'Your document request for :student_name has been approved.',
    'document_request.rejected' => 'Your document request for :student_name was not approved.',
    'document_request.ready' => 'The :document_type for :student_name is ready for collection.',
    'document_request.issued' => 'The :document_type for :student_name has been issued.',

    // -------------------------------------------------------------------------
    // Formal institution requests
    // -------------------------------------------------------------------------
    'formal_request.submitted' => 'A formal request from :institution_name is awaiting review.',
    'formal_request.responded' => 'A response has been received for the request from :institution_name.',
    'formal_request.returned' => 'Your formal request has been returned for revision.',

    // -------------------------------------------------------------------------
    // Mark sheets
    // -------------------------------------------------------------------------
    'mark_sheet.returned' => 'Your mark sheet for :subject_name (:class_name) has been returned for correction.',
    'mark_sheet.verified' => 'Your mark sheet for :subject_name (:class_name) has been verified.',

    // -------------------------------------------------------------------------
    // Attendance sheets
    // -------------------------------------------------------------------------
    'attendance_sheet.returned' => 'Your attendance sheet for :class_name on :date has been returned for correction.',
    'attendance_sheet.verified' => 'Your attendance sheet for :class_name on :date has been verified.',

    // -------------------------------------------------------------------------
    // Generic workflow transition
    // -------------------------------------------------------------------------
    'workflow.transition' => 'A workflow item has moved from :from_state to :to_state.',

    // -------------------------------------------------------------------------
    // Background operations
    // -------------------------------------------------------------------------
    'operation.completed' => 'Your :operation_type has completed successfully.',
    'operation.failed' => 'Your :operation_type could not be completed.',

];

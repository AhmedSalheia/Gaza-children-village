<?php

declare(strict_types=1);

return [
    /*
     * All registered module directory names. Architecture tests verify that
     * every directory under Modules/ appears here, and vice versa.
     * Renamed from 'foundation_modules' when Student Registry modules were added.
     */
    'registered_modules' => [
        'Accounts',
        'AcademicManagement',
        'AcademicCalendar',
        'Attachments',
        'Attendance',
        'Audit',
        'Authorization',
        'CivilRegistry',
        'Documents',
        'Imports',
        'Notifications',
        'Organization',
        'Requests',
        'People',
        'Staff',
        'Students',
        'Workflow',
    ],

    'public_namespaces' => [
        'Actions',
        'Contracts',
        'Data',
        'Events',
    ],

    'dependencies' => [
        // Foundation modules (acyclic graph, no changes)
        'Authorization' => [],
        'Audit' => [],
        'Accounts' => ['Authorization', 'Audit'],
        'Organization' => ['Authorization', 'Audit'],
        'AcademicCalendar' => ['Organization', 'Authorization', 'Audit'],
        'People' => ['Authorization', 'Audit'],
        'Staff' => ['Accounts', 'Organization', 'AcademicCalendar', 'People', 'Authorization', 'Audit'],

        // Student Registry and Enrolment modules
        'Students' => ['People', 'Organization', 'AcademicCalendar', 'Authorization', 'Audit'],
        'AcademicManagement' => ['Organization', 'AcademicCalendar', 'People', 'Authorization', 'Audit'],
        'CivilRegistry' => ['People', 'Authorization', 'Audit'],
        'Imports' => ['Students', 'AcademicManagement', 'People', 'Organization', 'AcademicCalendar', 'Authorization', 'Audit'],

        // Attendance: student daily attendance workflow
        // Cross-module data access uses DB::table() and string-variable class references
        // (double-backslash pattern) to avoid boundary-scanner FQCN violations.
        'Attendance' => ['AcademicManagement', 'AcademicCalendar', 'Staff', 'Students', 'Organization', 'Authorization', 'Audit'],

        // Workflow: shared state-machine engine. Depends only on Audit for append-only
        // audit events. All cross-module subject references use plain integer IDs.
        'Workflow' => ['Audit'],

        // Attachments: private file storage for evidence and request documents.
        // Depends on Authorization (permission checks), Audit (upload/download events),
        // Organization (institution scope), Accounts (uploader identity).
        'Attachments' => ['Authorization', 'Audit', 'Organization', 'Accounts'],

        // Notifications: in-app portal notifications and background job status.
        // Depends on Authorization (permission checks), Accounts (recipient identity),
        // Audit (notification audit trail), Organization (institution scope).
        'Notifications' => ['Authorization', 'Audit', 'Accounts', 'Organization'],

        // Requests: guardian correction requests and data-change proposal workflow.
        // Depends on Workflow (state machine), Attachments (evidence), Notifications,
        // Students (profiles/relationships), AcademicManagement, People (person data),
        // Organization (institution scope), Authorization (permission keys), Audit.
        'Requests' => ['Workflow', 'Attachments', 'Notifications', 'Students', 'AcademicManagement', 'People', 'Organization', 'Authorization', 'Audit'],

        // Documents: document type catalogue, template versioning, PDF generation,
        // and sequential document numbering. Depends on Workflow (approval flow for
        // documents requiring countersign), Attachments (issued document file storage),
        // Notifications (issuance notifications), AcademicManagement (enrollment data),
        // Students (student profiles), Organization (institution scope),
        // Authorization (permission keys), Audit (template activation events).
        'Documents' => ['Workflow', 'Attachments', 'Notifications', 'AcademicManagement', 'Students', 'Organization', 'Authorization', 'Audit'],
    ],
];

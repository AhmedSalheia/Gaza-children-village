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
        'Attendance',
        'Audit',
        'Authorization',
        'CivilRegistry',
        'Imports',
        'Organization',
        'People',
        'Staff',
        'Students',
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
    ],
];

<?php

declare(strict_types=1);

return [
    'foundation_modules' => [
        'Accounts',
        'Organization',
        'AcademicCalendar',
        'People',
        'Staff',
        'Authorization',
        'Audit',
    ],

    'public_namespaces' => [
        'Actions',
        'Contracts',
        'Data',
        'Events',
    ],

    'dependencies' => [
        'Authorization' => [],
        'Audit' => [],
        'Accounts' => ['Authorization', 'Audit'],
        'Organization' => ['Authorization', 'Audit'],
        'AcademicCalendar' => ['Organization', 'Authorization', 'Audit'],
        'People' => ['Authorization', 'Audit'],
        'Staff' => ['Accounts', 'Organization', 'AcademicCalendar', 'People', 'Authorization', 'Audit'],
    ],
];

<?php
return [
    'expiring_days' => 90,
    'institution_type_code' => 'medical_point',
    'profession' => [
        'doctor','nurse','pharmacist','other',
    ],
    'patient_statuses' => ['active','inactive'],
    'visit_statuses' => ['open','in_progress','completed','cancelled'],
];

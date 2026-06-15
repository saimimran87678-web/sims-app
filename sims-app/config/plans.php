<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SIMS Subscription Plans
    |--------------------------------------------------------------------------
    |
    | Defines the resource limits and allowed features for each plan tier.
    |
    */

    'basic' => [
        'name' => 'Basic',
        'limits' => [
            'students' => 150,
            'teachers' => 10,
            'classes' => 5,
        ],
        'features' => [
            'attendance',
            'exams',
            'results',
            'schedules',
            'reports',
        ],
    ],

    'standard' => [
        'name' => 'Standard',
        'limits' => [
            'students' => 400,
            'teachers' => 25,
            'classes' => 15,
        ],
        'features' => [
            'attendance',
            'exams',
            'results',
            'schedules',
            'reports',
            'fee_management',
            'student_ledger',
            'payment_tracking',
            'whatsapp_fee_reminders',
            'defaulter_reports',
        ],
    ],

    'premium' => [
        'name' => 'Premium',
        'limits' => [
            'students' => -1, // Unlimited
            'teachers' => -1,
            'classes' => -1,
        ],
        'features' => [
            'attendance',
            'exams',
            'results',
            'schedules',
            'reports',
            'fee_management',
            'student_ledger',
            'payment_tracking',
            'whatsapp_fee_reminders',
            'defaulter_reports',
            'digital_invoices',
            'whatsapp_invoices',
            'custom_branding',
            'invoice_history_analytics',
            'priority_support',
        ],
    ],
];

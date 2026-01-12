<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Warning Days
    |--------------------------------------------------------------------------
    |
    | Number of days from today that should trigger a warning when applying
    | for advance leave. Users will see a confirmation modal if they select
    | dates within this range.
    |
    */
    'warning_days' => 2,

    /*
    |--------------------------------------------------------------------------
    | Default Leave Types
    |--------------------------------------------------------------------------
    |
    | Default leave type codes for different application types.
    |
    */
    'default_advance_leave_type' => 'casual',
    'default_post_leave_type' => 'sick',

    /*
    |--------------------------------------------------------------------------
    | Approval Steps
    |--------------------------------------------------------------------------
    |
    | Define the approval sequence for different leave types.
    | Steps: 1 = Cover Person, 2 = Manager, 3 = Admin
    |
    */
    'approval_steps' => [
        'advance' => [
            1 => 'cover_person',
            2 => 'manager',
            3 => 'admin',
        ],
        'post' => [
            1 => 'manager',
            2 => 'admin',
        ],
    ],
];

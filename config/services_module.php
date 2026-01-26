<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Certificate Purposes
    |--------------------------------------------------------------------------
    |
    | Available purposes for employment certificate requests.
    |
    */
    'certificate_purposes' => [
        'visa_application' => 'Visa Application',
        'bank_loan' => 'Bank Loan / Mortgage',
        'apartment_leasing' => 'Apartment Leasing',
        'higher_education' => 'Higher Education',
        'other' => 'Other',
    ],

    /*
    |--------------------------------------------------------------------------
    | Certificate Approval Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for certificate approval workflow.
    | Approvers: manager, admin
    | Unlike leave which has cover_person step, certificates go directly to manager.
    |
    */
    'certificate_approval' => [
        // Approvers in order (manager first, then admin can issue)
        'approvers' => ['manager', 'admin'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Company Information
    |--------------------------------------------------------------------------
    |
    | Company details used in certificate generation.
    |
    */
    'company' => [
        'name' => 'BD Funnel Builder Limited',
        'short_name' => 'BDFB',
    ],

    /*
    |--------------------------------------------------------------------------
    | Issuer Information
    |--------------------------------------------------------------------------
    |
    | Default issuer details for certificates.
    |
    */
    'issuer' => [
        'name' => 'Towsif Sakib',
        'title' => 'People & Operations Manager',
        'phone' => '+8801718506665',
    ],
];

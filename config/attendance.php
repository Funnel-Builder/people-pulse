<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Office Start Time
    |--------------------------------------------------------------------------
    |
    | The official start time of the workday. Used to determine late arrivals.
    | Format: HH:MM (24-hour format)
    |
    */
    'office_start_time' => env('OFFICE_START_TIME', '09:30'),

    /*
    |--------------------------------------------------------------------------
    | Late Grace Period
    |--------------------------------------------------------------------------
    |
    | Number of minutes after office start time before marking as late.
    |
    */
    'late_grace_minutes' => (int) env('LATE_GRACE_MINUTES', 15),

    /*
    |--------------------------------------------------------------------------
    | Office End Time
    |--------------------------------------------------------------------------
    |
    | The official end time of the workday. Used to determine early exits.
    | Format: HH:MM (24-hour format)
    |
    */
    'office_end_time' => env('OFFICE_END_TIME', '17:30'),

    /*
    |--------------------------------------------------------------------------
    | Default Break Duration
    |--------------------------------------------------------------------------
    |
    | Default break duration in minutes to subtract from gross hours.
    |
    */
    'default_break_minutes' => (int) env('DEFAULT_BREAK_MINUTES', 60),

    /*
    |--------------------------------------------------------------------------
    | Default Weekend Days
    |--------------------------------------------------------------------------
    |
    | Default weekend days for new users if not specified.
    |
    */
    'default_weekend_days' => ['saturday', 'sunday'],

    /*
    |--------------------------------------------------------------------------
    | Weekend Options
    |--------------------------------------------------------------------------
    |
    | Available weekend configurations that can be assigned to users.
    |
    */
    'weekend_options' => [
        'fri_sat' => ['friday', 'saturday'],
        'sat_sun' => ['saturday', 'sunday'],
        'fri_only' => ['friday'],
        'sat_only' => ['saturday'],
        'sun_only' => ['sunday'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Office Location Geofence
    |--------------------------------------------------------------------------
    |
    | Clock in / clock out are only allowed when the user is physically within
    | "geofence_radius_meters" of the office coordinates below. Login itself is
    | NOT restricted by location — only attendance punches are.
    |
    | Set OFFICE_LATITUDE / OFFICE_LONGITUDE to your office coordinates and
    | toggle the feature with ATTENDANCE_GEOFENCE_ENABLED.
    |
    */
    'geofence_enabled' => filter_var(env('ATTENDANCE_GEOFENCE_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    'office_latitude' => env('OFFICE_LATITUDE') !== null ? (float) env('OFFICE_LATITUDE') : null,

    'office_longitude' => env('OFFICE_LONGITUDE') !== null ? (float) env('OFFICE_LONGITUDE') : null,

    'geofence_radius_meters' => (int) env('ATTENDANCE_GEOFENCE_RADIUS_METERS', 300),

    /*
    |--------------------------------------------------------------------------
    | Attendance Adjustment Requests
    |--------------------------------------------------------------------------
    |
    | "Request Late Entry" / "Request Early Out" applications follow the same
    | multi-step approval flow as advance leave:
    |   Step 1 = Cover Person, Step 2 = Manager, Step 3 = Admin.
    | On final approval the day's late/early flag is excused and an audit log
    | entry is recorded. Attachments are optional supporting documents.
    |
    */
    'adjustment_approval_steps' => [
        1 => 'cover_person',
        2 => 'manager',
        3 => 'admin',
    ],

    'adjustment_attachment' => [
        'mimes' => 'pdf,jpg,jpeg,png',
        'max_kb' => 5120,
    ],
];

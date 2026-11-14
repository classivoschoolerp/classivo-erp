<?php

return [
    /*
    | null = enabled only when APP_ENV=demo. Set true/false to force.
    */
    'enabled' => env('DEMO_GUARD_ENABLED'),

    /* Let artisan / seeders / tinker through, so the nightly reset can reseed. */
    'allow_console' => true,

    'banner' => [
        'enabled' => true,
        'reset_time' => '03:00 IST',
    ],

    'uploads' => [
        'max_kb' => 2048,
        'max_files' => 3,
    ],

    /*
    | Models guarded per Eloquent event. Classes that are not installed are skipped.
    | 'all' = every event below; or list a subset of: creating, updating, deleting.
    | 'dirty' (User only) = block updates only when these attributes change.
    */
    'users' => [
        'model' => null, // null = auth.providers.users.model
        // Sign-in details of the shared demo accounts. The two-factor columns stop a
        // visitor from switching on an authenticator app and locking everyone else out.
        'protected_attributes' => [
            'password', 'email',
            'app_authentication_secret', 'app_authentication_recovery_codes', 'force_password_change',
        ],
        'block_delete' => true,
    ],

    'role_models' => [
        \Spatie\Permission\Models\Role::class,
        \Spatie\Permission\Models\Permission::class,
    ],

    'locked_models' => [
        // payment credentials: no create / update / delete
        \Classivo\Payments\Models\SchoolPaymentCredential::class,
        // mail / SMS / WhatsApp switches
        \Classivo\Communication\Models\NotificationSetting::class,
        // core school settings
        \Classivo\Core\Models\School::class,
    ],
];

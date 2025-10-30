<?php

return [
    // URL prefix for generated or dynamic CRUD routes (affects routes/crud.php)
    'route_prefix' => 'crud',

    // Middleware applied to CRUD routes (e.g. add throttle here for rate limiting)
    'middleware' => ['web'],

    // Default namespace where generated models will be written in --generate mode
    'models_namespace' => 'App\\Models',

    // Relative path (from project root) where generated migrations will be written
    'migrations_path' => 'database/migrations/crud',

    // Relative path (from project root) where views will be published/overridden
    'views_path' => 'resources/views/vendor/laravel-crud',

    // If true, run migrations automatically after generation (prefer using CLI --migrate)
    'auto_migrate' => false,

    // If true, do not write files; only simulate and preview planned changes
    'preview_mode' => false,

    // Optional explicit allow-list of tables for dynamic CRUD routes; when empty, any existing DB table is allowed
    'allowed_tables' => [],

    // Optional per-table custom validation rules (appended or override)
    // Example: 'custom_validation' => ['users' => ['email' => 'email|unique:users,email']]
    'custom_validation' => [],

    // File upload handling configuration
    // 'uploads' => [
    //     'disk' => 'public',
    //     'fields' => [ 'users.avatar' => ['path' => 'uploads/avatars'] ]
    // ]
    'uploads' => [
        'disk' => 'public',
        'fields' => [],
    ],

    // Field sources for dropdowns/selects per table.column (used by OptionsResolver)
    // Example entries:
    // 'field_sources' => [
    //   'users.group_id' => ['type' => 'table', 'table' => 'groups', 'value' => 'id', 'label' => 'name'],
    //   'users.status' => ['type' => 'static', 'options' => ['active' => 'Active', 'inactive' => 'Inactive']],
    //   'users.country' => ['type' => 'api', 'callback' => '\\App\\Support\\OptionCallbacks::countries']
    // ]
    'field_sources' => [],
    // For security, API callbacks are disabled by default. Enable with caution; ensure callbacks are audited and trusted.
    'field_sources_allow_api_callbacks' => false,

    // Permissions integration
    // If enabled, calls Gate::authorize with provided abilities
    'permissions' => [
        'enabled' => false,
        // ability names may include :table placeholder
        'abilities' => [
            'index' => 'crud.view-:table',
            'show' => 'crud.view-:table',
            'create' => 'crud.create-:table',
            'store' => 'crud.create-:table',
            'edit' => 'crud.update-:table',
            'update' => 'crud.update-:table',
            'destroy' => 'crud.delete-:table',
        ],
    ],

    // Auditing: set user id on created_by/updated_by if columns exist (controller layer)
    'audit' => [
        'enabled' => false,
        'created_by' => 'created_by',
        'updated_by' => 'updated_by',
    ],
];


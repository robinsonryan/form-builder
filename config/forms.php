<?php

declare(strict_types=1);

/*
 * Default configuration for the Form Builder package.
 * Host applications may publish this config and override values as needed.
 */

return [
    // Whether forms are tenant-scoped by default.
    'tenant_scoped' => env('FORM_BUILDER_TENANT_SCOPED', false),

    'tenant_model' => env('FORM_BUILDER_TENANT_MODEL', 'app\Models\Tenant'),
    'tenant_column_name' => env('FORM_BUILDER_TENANT_COLUMN_NAME', 'tenant_id'),

    // Default owner scope for newly created forms: 'tenant' or 'global'.
    'default_owner_scope' => env('FORM_BUILDER_DEFAULT_OWNER_SCOPE', 'tenant'),

    // Drafts feature flag (default OFF).
    'drafts_enabled' => env('FORM_BUILDER_DRAFTS_ENABLED', false),

    // Idempotency protection for final submissions (default OFF).
    'idempotency_required' => env('FORM_BUILDER_IDEMPOTENCY_REQUIRED', false),

    // Header names to check for idempotency keys (in priority order).
    'idempotency_header_names' => ['Idempotency-Key', 'X-Idempotency-Key'],

    // Storage disk to use for form uploads and assets.
    'storage_disk' => env('FORM_BUILDER_DISK', 'local'),

    // Maximum upload size in kilobytes (for file fields).
    'max_upload_size_kb' => env('FORM_BUILDER_MAX_UPLOAD_SIZE_KB', 10240),

    // Allowed MIME types for uploaded files.
    'allowed_file_types' => [
        'image/jpeg',
        'image/png',
        'application/pdf',
    ],

    // Default schema validator implementation (FQCN). Host apps may bind SchemaValidatorInterface differently.
    'schema_validator' => \Packages\FormBuilder\Services\Validation\OpisJsonSchemaValidator::class,

    // Pagination defaults for listing endpoints.
    'pagination' => [
        'default_per_page' => 25,
        'max_per_page' => 100,
    ],

    // Versioning / publishing behaviour flags.
    'versioning' => [
        'immutable_published' => true,
    ],
];

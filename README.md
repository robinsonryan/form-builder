# Vendor / Form Builder

A Laravel package to provide multi-tenant, schema-driven form building and validation.

This repository contains a lightweight scaffold of the package for local development and as a composer-installable package. The README below is a developer-oriented Getting Started + usage guide covering how the package models forms, steps (categorization), fragments & slots (overrides), validation, drafts, idempotency and integration points.

Table of contents
- Overview
- Requirements
- Quick install (host app)
- Publish config & migrations
- Seed sample data (dev)
- Routes & HTTP endpoints
- Data model: forms, versions, fragments, variants
- Steps & Categorization (UI-driven steps)
- Fragments, Slots & Overrides
- Drafts and resumable submissions
- Validation pipeline (JSON Schema + x-rules)
- Idempotency for final submissions
- Multitenancy & visibility rules (FormPolicy)
- Console commands
- Testing
- Examples
- Troubleshooting & tips

Overview
--------
Form Builder provides a schema-first approach to building multi-step forms:
- JSON Schema drives validation and canonical structure.
- UI schema (JSON Forms / Ajv-friendly) drives step layout and categorization.
- Fragments and slots allow reusable blocks that can be composed into base schemas.
- Published form versions are immutable; drafts allow iterative editing.
- Submissions are validated server-side with parity to FE (Ajv) and support x-rules for business checks.

Requirements
------------
- PHP 8.3+
- Laravel 12.x (or host app compatible with package)
- PostgreSQL (jsonb recommended for schema storage)
- Composer

Quick install (host application)
-------------------------------
1. Require the package (replace vendor/name with actual package name when published):
```bash
composer require vendor/form-builder
```

2. (Optional) Publish configuration and/or migrations:
```bash
php artisan vendor:publish --tag=form-builder-config
php artisan vendor:publish --tag=form-builder-migrations
```

3. Run migrations:
```bash
php artisan migrate
```

4. (Optional) Seed sample data to experiment with endpoints:
```bash
php artisan forms:seed-sample
```

Routes & HTTP endpoints
-----------------------
The package exposes endpoints under the /api/form-builder prefix. Core routes include:

- GET /api/form-builder/forms/{key}
  - Lookup form metadata (minimal scalar fields).

- POST /api/form-builder/forms/{key}/drafts
  - Create a draft (schema + ui payloads).

- GET /api/form-builder/forms/{key}/drafts/{id}
  - Fetch a draft.

- PATCH /api/form-builder/forms/{key}/drafts/{id}/steps/{stepKey}
  - Patch a single step fragment of a draft.

- POST /api/form-builder/forms/{key}/versions/{version}/validate
  - Validate provided data against a schema (returns errors per standard error contract).

- POST /api/form-builder/forms/{key}/versions/{version}/submit
  - Final submit endpoint; returns 201 and a submission id on success.

Data model (high level)
-----------------------
- forms: metadata for a form (id, key, title, owner_scope, account_id, tenant_visible, parent_form_id, status)
- form_drafts → form_versions: drafts are mutable; published versions are immutable snapshots (schema_json + ui_json)
- form_fragments + form_fragment_versions: reusable schema/ui pieces that can be inserted by pointer
- form_variants: UI variants (A/B) for the same form/version
- form_submissions: persisted final answers (answers_json)
- idempotency keys: optional table to store first response per Idempotency-Key

Steps & Categorization
----------------------
The UI schema drives step derivation (categorization). The StepMapper derives ordered step descriptors from the UI schema which map to JSON Pointers into the base schema. The submission flow can validate per-step by building a sub-schema for the step (StepSubschemaBuilder) and running the validator.

Fragments, Slots & Overrides
----------------------------
- Fragments: reusable JSON Schema + UI fragments stored in the package. FragmentComposer can insert a fragment into a base schema at a JSON Pointer, applying rename maps and parameter substitution.
- Slots: named insertion points in the base schema that allow tenant-specific or extension-provided fragments to be composed into the final published schema.
- Overrides:
  - Variants (form_variants) allow alternative UI for experiments or A/B testing.
  - Extensions (form_extensions) allow tenants to provide content for slots.

Drafts & Resumable Submissions
------------------------------
Drafts are optional and feature-flagged. They enable:
- Creating a draft from a base form
- Patching individual steps (useful for autosave)
- Resuming and finalizing drafts into a publish step (business logic required)

Validation pipeline
-------------------
1. JSON Schema validation (primary): performed using a SchemaValidatorInterface implementation. The package ships a JsonSchemaValidator adapter.
2. x-rules (additional domain rules): e.g. unique_in_period, exists_ref, file_exists, hash_match. These are executed after schema validation and produce the error contract entries.
3. Business hooks (custom serverside logic) can inject additional errors or mutate derived values.

Error contract
--------------
Always use a consistent error shape:
```json
{
  "ok": false,
  "errors": [
    { "path": "#/properties/email", "code": "unique_in_period", "message": "Email already used in the last 90 days." }
  ]
}
```

Idempotency
-----------
- Controlled by config/forms.php -> idempotency_required.
- Middleware EnsureIdempotencyKey expects Idempotency-Key (or X-Idempotency-Key) on final POST submissions when enabled.
- Middleware stores the first response and returns the stored result for subsequent requests with the same key (scoped by X-Account-Id when present).

Multitenancy & FormPolicy
-------------------------
- owner_scope: 'global' or 'tenant'
- tenant_visible: boolean for global forms to allow tenant visibility
- FormPolicy enforces view/create/update/delete/clone behaviors and resolves a lightweight account id from the $user argument. The package registers the policy in the service provider.

Console commands
----------------
- php artisan forms:seed-sample — seed a sample form and a published version for local dev
- php artisan forms:publish — placeholder for publish pipeline
- php artisan forms:lint — placeholder for schema linting

Testing
-------
The package includes Pest specs which run under Orchestra\Testbench (package TestCase). To run package tests:

```bash
./vendor/bin/pest packages/form-builder
```

Examples
--------

Using the Forms facade (short snippet)
--------------------------------------
The `Former` facade provides a concise entry point to the package flows. It proxies to the
registered FormsManager implementation (`forms.manager`) and returns typed DTOs.

Publish a composed schema:
```php
use Packages\FormBuilder\Facades\Former;

$result = Former::publish($baseSchema, $extensions, $uiSchema);

if ($result->ok) {
    // published form version id available at $result->published_form_version_id
}
```

Register an x-rule and a business rule:
```php
Former::registerXRule('noop', new class implements \Packages\FormBuilder\Contracts\XRuleInterface {
    public function handle(array $ctx): ?\Packages\FormBuilder\Data\ValidationErrorData {
        return null;
    }
});

Former::registerBusinessRule('contact-form', new class implements \Packages\FormBuilder\Contracts\BusinessRuleInterface {
    public function handle(array $ctx): ?\Packages\FormBuilder\Data\ValidationErrorData {
        return null;
    }
});
```

Listen for events:
```php
use Packages\FormBuilder\Events\FormPublished;
use Illuminate\Support\Facades\Event;

Event::listen(FormPublished::class, fn($evt) => logger('Form published', $evt->data));
```

1) Minimal form (JSON Schema)
```json
{
  "type": "object",
  "properties": {
    "name": { "type": "string" },
    "email": { "type": "string", "format": "email" }
  },
  "required": ["name", "email"]
}
```

2) UI schema with categorization (steps)
```json
{
  "type": "Categorization",
  "elements": [
    {
      "type": "Category",
      "label": "Personal",
      "elements": [
        { "type": "Control", "scope": "#/properties/name" }
      ]
    },
    {
      "type": "Category",
      "label": "Contact",
      "elements": [
        { "type": "Control", "scope": "#/properties/email" }
      ]
    }
  ]
}
```
- StepMapper will derive two steps: "personal" and "contact" with pointers into the schema.
- StepSubschemaBuilder can construct a step-level subschema for per-step validation.

3) Fragment insertion (slot)
- Base schema defines a slot:
```json
{
  "type": "object",
  "properties": {
    "existing": { "type": "string" }
  },
  "slots": {
    "extras": "/properties"
  }
}
```
- An extension provides a fragment inserted at /properties to add new fields.

Integration tips & host overrides
---------------------------------
- Bind a concrete SchemaValidatorInterface in your app container if you need different validator behavior.
- Use published migrations when you want to extend schema columns.
- Consider setting forms.drafts_enabled and forms.idempotency_required in published config to match host requirements.
- Register additional X-rules by implementing XRuleInterface and registering with the package registry.

Developer troubleshooting
-------------------------
- If Carbon/Date serialization errors appear during tests, ensure Pest spec files call:
```
uses(Packages\FormBuilder\Tests\TestCase::class);
```
- If Eloquent Model connection is null in package tests, ensure your package TestCase extends Orchestra\Testbench\TestCase and performs migrations in setUp().

Helpful commands
----------------
Run the package tests:
```bash
./vendor/bin/pest packages/form-builder
```

Seed a sample form for local testing:
```bash
php artisan forms:seed-sample
```

Dump autoload after creating classes during development:
```bash
composer dump-autoload
```

Contributing
------------
Contributions are welcome. Follow PSR-12 and declare(strict_types=1) in PHP files. Add Pest tests for new features and keep changes focused in a single commit.

License & attribution
---------------------
This scaffold is provided as-is for development purposes. Update license and vendor metadata when publishing the package.

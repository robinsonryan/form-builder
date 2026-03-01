# Form Builder — Install & Usage Guide (expanded examples)

This guide expands the quick install with concise, practical examples that demonstrate the package features:
- creating basic forms and published versions
- reusing fragments (fragments & slots)
- deriving steps (categorization → steps)
- variants and overrides
- drafts and patching steps
- validation and idempotent submission

Keep examples short; they purposefully show "how" rather than exhaustive production-ready code.

Prereqs (host app)
- Laravel 12.x, PHP 8.3+, PostgreSQL (jsonb recommended)
- Run migrations after publishing as described in the quick install

Using the Forms facade (quick examples)
--------------------------------------
The package exposes a thin, ergonomic facade called `Former` which proxies to the Forms manager
registered in the container as `forms.manager`. Use the facade in host applications or package
code to perform common flows without resolving implementation classes:

- Publish a composed schema (returns PublishingResultData):
```php
use Packages\FormBuilder\Facades\Former;
use Packages\FormBuilder\Events\FormPublished;

$result = Former::publish($baseSchema, $extensions, $uiSchema);

if ($result->ok) {
    // $result->published_form_version_id contains the FormVersion id (uuid) when published.
}
```

- Derive UI steps from a JSON-Forms Categorization ui_json:
```php
$steps = Former::deriveSteps($uiJson); // returns array of StepDescriptorData
```

- Build a step-scoped subschema for server-side per-step validation:
```php
$subschema = Former::buildStepSubschema($schemaJson, $uiStepMaps, $stepId);
```

- Validate arbitrary data against a schema (returns ValidationResultData):
```php
$validation = Former::validate($data, $schema);
if (! $validation->isValid()) {
    // $validation contains ValidationErrorsData / ValidationErrorData entries
}
```

- Submit final answers (idempotency and tenant scoping are handled by the manager):
```php
$submitResult = Former::submit($answers, 'contact-form', '1', ['account_id' => $accountId]);

if ($submitResult->ok) {
    echo "submission id: {$submitResult->submission_id}";
} else {
    // $submitResult->errors is an array of ValidationErrorData-like items
}
```

- Register an x-rule handler:
```php
Former::registerXRule('unique_in_period', new \App\XRules\UniqueInPeriodHandler());
```

- Register a business rule handler for a form key (exact key or base key without version suffix):
```php
Former::registerBusinessRule('contact-form', new \App\BusinessRules\CheckPhoneRule());
```

- Listen to published / submitted events in your app:
```php
use Packages\FormBuilder\Events\FormPublished;
use Packages\FormBuilder\Events\FormSubmitted;
use Packages\FormBuilder\Events\FormSubmissionFailed;
use Illuminate\Support\Facades\Event;

Event::listen(FormPublished::class, function (FormPublished $event) {
    // $event->data contains ok/published_form_version_id/errors
});

Event::listen(FormSubmitted::class, function (FormSubmitted $event) {
    // $event->data['submission_id']
});

Event::listen(FormSubmissionFailed::class, function (FormSubmissionFailed $event) {
    // $event->data['errors']
});
```

Notes:
- The facade resolves an implementation of Packages\FormBuilder\Contracts\FormsManagerInterface.
- Methods return typed DTOs (PublishingResultData, SubmissionResultData, ValidationResultData)
  — consult their constructors/fields when handling results in host code.

1) Create a basic form (artisan tinker / code)
----------------------------------------------
Example: create a global form and a published version via tinker or in a seeder.

php artisan tinker
```php
use Packages\FormBuilder\Models\Form;
use Packages\FormBuilder\Models\FormVersion;
use Illuminate\Support\Str;

$formId = Str::uuid7()->toString();

$form = Form::create([
  'id' => $formId,
  'key' => 'contact-form',
  'title' => 'Contact Form',
  'owner_scope' => 'global',
  'account_id' => null,
  'tenant_visible' => true,
  'status' => 'active',
]);

FormVersion::create([
  'id' => Str::uuid7()->toString(),
  'form_id' => $form->id,
  'version_number' => 1,
  'schema_json' => [
    'type' => 'object',
    'properties' => [
      'name' => ['type' => 'string'],
      'email' => ['type' => 'string', 'format' => 'email'],
    ],
    'required' => ['name', 'email'],
  ],
  'ui_json' => [
    // A simple JSON Forms UI layout; categorization example below will derive steps
  ],
  'published_at' => now(),
]);
```

2) Reusing forms: fragments & slots
------------------------------------
Fragments are small reusable schema/UI blocks stored as FormFragment + FormFragmentVersion.
Slots are named insertion points in a base schema where fragments are composed.

Base schema with a slot (author of base form):
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

A tenant or extension provides a fragment to insert into the "extras" slot:

Fragment schema
```json
{
  "type": "object",
  "properties": {
    "phone": { "type": "string" },
    "postcode": { "type": "string" }
  }
}
```

Composition (conceptual; service used by publishing pipeline)
- SlotComposer composes base schema + array of extensions.
- FragmentComposer inserts fragment at pointer "/properties" preserving naming and applying optional param/rename maps.

3) UI steps (Categorization → StepMapper)
------------------------------------------
UI schema (JSON Forms) with Categorization:
```json
{
  "type": "Categorization",
  "elements": [
    {
      "type": "Category",
      "label": "Personal",
      "elements": [{ "type": "Control", "scope": "#/properties/name" }]
    },
    {
      "type": "Category",
      "label": "Contact",
      "elements": [{ "type": "Control", "scope": "#/properties/email" }]
    }
  ]
}
```

- StepMapper::derive($uiJson) returns an ordered array of StepDescriptorData:
  - id, title, index, ui_schema, schema pointer(s)
- Use StepSubschemaBuilder::build($schema, $uiStepMaps, $stepId) to produce a step-scoped subschema for per-step validation.

4) Variants & overrides (UI A/B)
--------------------------------
Create FormVariant records to provide alternative ui_json for the same form/version. At render-time, the FE can request a variant key; server-side validation uses the same immutable schema_json so validation parity is maintained.

5) Drafts usage (create, patch step, resume)
--------------------------------------------
Create draft via API:
POST /api/form-builder/forms/contact-form/drafts
Body:
```json
{ "schema": { /* draft schema or empty to start */ }, "ui": null }
```
Patch a step (autosave):
PATCH /api/form-builder/forms/contact-form/drafts/{draftId}/steps/personal
Body:
```json
{ "patch": { "name": "Alice" } }
```
- The DraftController.patchStep merges step patch into stored schema_json for the draft.

6) Validation (API example)
----------------------------
POST /api/form-builder/forms/contact-form/versions/1/validate
Body:
```json
{ "schema": { /* step or full schema */ }, "data": { "name": "Alice" } }
```
- Server returns { ok: true, errors: [] } when valid.
- For full pipeline, backend runs JSON Schema validator, then x-rules handlers; errors are returned in the standard { path, code, message } shape.

7) Idempotent final submissions
-------------------------------
When enabled via config.forms.idempotency_required, include an Idempotency-Key header on final POST to /submit:

Example (curl):
```bash
curl -X POST /api/form-builder/forms/contact-form/versions/1/submit \
  -H "Idempotency-Key: abc-123" \
  -H "X-Account-Id: <tenant-uuid>" \
  -H "Content-Type: application/json" \
  -d '{"answers":{"name":"Alice","email":"alice@example.com"}}'
```
- First request stores the response keyed by (Idempotency-Key, account_id). Repeating the same key returns the stored response (no duplicate submission).

8) Publish pipeline (compose, derive steps, validate)
------------------------------------------------------
The publishing pipeline composes slots/fragments (SlotComposer + FragmentComposer), derives steps (StepMapper), validates final schema (SchemaValidatorInterface) and produces a FormVersion with an immutable schema_json and published_at.

Example (conceptual service usage):
```php
$publisher = app(\Packages\FormBuilder\Services\Publishing\FormPublisher::class);
$result = $publisher->publish($baseSchema, $extensions, $uiSchema);
// $result is PublishingResultData: ok, published_form_version_id, errors
```

9) Example: end-to-end (create base form, add fragment, publish)
----------------------------------------------------------------
1. Create base form as in step (1).
2. Create a FormFragment and FormFragmentVersion (fragment contains schema + optional params).
3. Call the publishing service (FormPublisher) with:
   - $baseSchema (form draft schema)
   - $extensions (array of fragment descriptors for slots)
   - $uiSchema
4. On success FormPublisher returns a published version id; persist as FormVersion.

10) Developer notes & tips
--------------------------
- Tests: include `uses(Packages\FormBuilder\Tests\TestCase::class);` in Pest specs so Orchestra\Testbench boots the app.
- If you plan to swap the JSON Schema validator, bind SchemaValidatorInterface in your application's container to your adapter.
- Use published migrations to review and, if needed, alter schema before running in production.
- For large forms, use fragments & slots to maintain reusable components and avoid schema duplication.

11) Quick reference (CLI)
-------------------------
- Publish config: `php artisan vendor:publish --tag=form-builder-config`
- Publish migrations: `php artisan vendor:publish --tag=form-builder-migrations`
- Run migrations: `php artisan migrate`
- Seed sample: `php artisan forms:seed-sample`
- Lint (placeholder): `php artisan forms:lint`
- Publish pipeline (placeholder): `php artisan forms:publish`

12) Next Steps
--------------------------------
form-builder simply stores forms with a json_schema (defines the inputs) and a ui_schema (defines the rendering).
It is inteded to be used with a json_schema form rendering library, like eclipsesource/jsonforms
- Implement front-end rendering with JSON Forms + Ajv using the package's ui_json.
- Add x-rules handlers for domain rules you need (unique_in_period, exists_ref, etc).
- Consider integrating S3 or remote storage for file uploads and wire FileExistsHandler accordingly.

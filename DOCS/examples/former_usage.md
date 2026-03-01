# Former facade — Usage examples

This small examples file collects copy-paste ready snippets that demonstrate how to use the
Forms facade (`Packages\FormBuilder\Facades\Former`) in a host application.

Prerequisite: the package service provider binds the FormsManagerInterface and exposes a
'forms.manager' entry that the facade resolves. The facade methods return typed DTOs
(see PublishingResultData, SubmissionResultData, ValidationResultData) that you can inspect.

Publish a composed schema
-------------------------
Publish a base schema with extensions and a ui schema:

```php
use Packages\FormBuilder\Facades\Former;

$baseSchema = [
    'type' => 'object',
    'properties' => [
        'name' => ['type' => 'string'],
        'email' => ['type' => 'string', 'format' => 'email'],
    ],
    'required' => ['name', 'email'],
];

$extensions = []; // fragments/extensions for slots
$uiSchema = []; // JSON-Forms ui schema

$result = Former::publish($baseSchema, $extensions, $uiSchema);

if ($result->ok) {
    // success: $result->published_form_version_id contains the new FormVersion ULID
} else {
    // $result->errors is an array of ValidationErrorData-like entries
}
```

Derive UI steps
---------------
If you store a JSON-Forms Categorization ui schema, derive step descriptors on the server:

```php
$uiStepDescriptors = Former::deriveSteps($uiSchema);
foreach ($uiStepDescriptors as $step) {
    // $step is a StepDescriptorData DTO: id, title, index, ui_schema, schema
}
```

Per-step subschema for validation
--------------------------------
Build a step-scoped subschema for per-step validation (useful for save/next flows):

```php
$stepId = 'personal';
$subschema = Former::buildStepSubschema($publishedSchemaJson, $uiStepMaps, $stepId);

// Then validate with the facade/manager:
$validation = Former::validate($dataForStep, $subschema);
if (! $validation->isValid()) {
    // handle validation errors ($validation contains ValidationErrorsData)
}
```

Final submit
------------
Submit final answers (the manager persists to form_submissions and dispatches events):

```php
$answers = ['name' => 'Alice', 'email' => 'alice@example.com'];
$submitResult = Former::submit($answers, 'contact-form', '1', ['account_id' => $tenantId]);

if ($submitResult->ok) {
    echo "Submission saved: {$submitResult->submission_id}";
} else {
    // $submitResult->errors contains ValidationErrorData entries
}
```

Register an x-rule
------------------
Implement XRuleInterface and register it with the facade:

```php
use Packages\FormBuilder\Contracts\XRuleInterface;
use Packages\FormBuilder\Facades\Former;
use Packages\FormBuilder\Data\ValidationErrorData;

class UniqueInPeriodHandler implements XRuleInterface {
    public function handle(array $context): ?ValidationErrorData {
        // return ValidationErrorData when rule fails, or null on success
        return null;
    }
}

Former::registerXRule('unique_in_period', new UniqueInPeriodHandler());
```

Register a business rule
------------------------
Business rule handlers run after schema + x-rules validation. Register per-form:

```php
use Packages\FormBuilder\Contracts\BusinessRuleInterface;
use Packages\FormBuilder\Data\ValidationErrorData;

class DenyBlockedPhone implements BusinessRuleInterface {
    public function handle(array $context): ?ValidationErrorData {
        // inspect $context['answers'] etc; return a ValidationErrorData on failure
        return new ValidationErrorData('#/phone', 'phone_blocked', 'Phone number is blocked.');
    }
}

Former::registerBusinessRule('contact-form', new DenyBlockedPhone());
```

Listen for events
-----------------
The package dispatches simple DTO-like events. Use Event::listen to react:

```php
use Illuminate\Support\Facades\Event;
use Packages\FormBuilder\Events\FormPublished;
use Packages\FormBuilder\Events\FormSubmitted;
use Packages\FormBuilder\Events\FormSubmissionFailed;

Event::listen(FormPublished::class, function (FormPublished $e) {
    // $e->data contains ok, published_form_version_id, errors
});

Event::listen(FormSubmitted::class, function (FormSubmitted $e) {
    // $e->data contains ok, submission_id, replayed, errors
});

Event::listen(FormSubmissionFailed::class, function (FormSubmissionFailed $e) {
    // $e->data contains details about the failed submission
});
```

Notes
-----
- The facade resolves Packages\FormBuilder\Contracts\FormsManagerInterface. If you prefer,
  resolve the interface from the container via app(FormsManagerInterface::class).
- Methods return DTOs defined under Packages\FormBuilder\Data; inspect them for result shapes.
- When registering handlers, prefer concrete classes and register them during app boot (ServiceProvider)
  or a dedicated package bootstrapping step in your app.

<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Services;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Event;
use Packages\FormBuilder\Contracts\FormsManagerInterface;
use Packages\FormBuilder\Contracts\SchemaValidatorInterface;
use Packages\FormBuilder\Contracts\XRuleInterface;
use Packages\FormBuilder\Contracts\BusinessRuleInterface;
use Packages\FormBuilder\Data\FormData;
use Packages\FormBuilder\Data\FormDraftData;
use Packages\FormBuilder\Data\PublishingResultData;
use Packages\FormBuilder\Data\StepDescriptorData;
use Packages\FormBuilder\Data\ValidationErrorData;
use Packages\FormBuilder\Data\SubmissionResultData;
use Packages\FormBuilder\Data\ValidationErrorsData;
use Packages\FormBuilder\Data\ValidationResultData;
use Packages\FormBuilder\Models\Form;
use Packages\FormBuilder\Models\FormDraft;
use Packages\FormBuilder\Models\FormVersion;
use Packages\FormBuilder\Models\FormResponse;
use Packages\FormBuilder\Services\Publishing\SlotComposer;
use Packages\FormBuilder\Services\Publishing\StepMapper;
use Packages\FormBuilder\Services\Publishing\ContentHasher;
use Packages\FormBuilder\Services\Publishing\FormPublisher;
use Packages\FormBuilder\Services\Validation\XRulesRegistry;
use Packages\FormBuilder\Services\Validation\BusinessRuleResolver;
use Packages\FormBuilder\Services\Validation\StepSubschemaBuilder;
use Packages\FormBuilder\Services\Validation\ErrorFormatter;
use Packages\FormBuilder\Events\FormPublished;
use Packages\FormBuilder\Events\FormSubmitted;
use Packages\FormBuilder\Events\FormSubmissionFailed;

/**
 * Concrete FormsManager implementation (Step 3)
 *
 * Orchestrates publishing, validation and submission flows by delegating to
 * package services. This is intentionally a thin coordinator — heavy lifting
 * remains in the injected services.
 */
final class FormsManager implements FormsManagerInterface
{
    public function __construct(
        private SlotComposer $slotComposer,
        private StepMapper $stepMapper,
        private ContentHasher $contentHasher,
        private SchemaValidatorInterface $validator,
        private FormPublisher $publisher,
        private XRulesRegistry $xrules,
        private BusinessRuleResolver $businessResolver,
        private StepSubschemaBuilder $stepSubschemaBuilder,
        private ErrorFormatter $errorFormatter,
        private ?CacheFactory $cache = null,
    ) {
    }

    public function create(array $attrs, ?string $accountId = null): FormData
    {
        $model = Form::create(array_merge($attrs, ['account_id' => $accountId]));

        return new FormData(
            id: (string) $model->id,
            key: (string) $model->key,
            title: (string) $model->title,
            owner_scope: (string) $model->owner_scope,
            account_id: $model->account_id,
            tenant_visible: (bool) $model->tenant_visible,
            parent_form_id: $model->parent_form_id,
            status: (string) $model->status,
            created_at: $model->created_at?->toDateTimeImmutable(),
            updated_at: $model->updated_at?->toDateTimeImmutable(),
        );
    }

    public function findByKey(string $key, ?string $accountId = null): ?FormData
    {
        $query = Form::query();

        // scopeByAccount trait provides byAccount scope
        if (method_exists(Form::class, 'scopeByAccount')) {
            $query = $query->byAccount($accountId);
        } elseif ($accountId !== null) {
            $query = $query->where('account_id', $accountId);
        }

        $model = $query->where('key', $key)->first();

        if ($model === null) {
            return null;
        }

        return new FormData(
            id: (string) $model->id,
            key: (string) $model->key,
            title: (string) $model->title,
            owner_scope: (string) $model->owner_scope,
            account_id: $model->account_id,
            tenant_visible: (bool) $model->tenant_visible,
            parent_form_id: $model->parent_form_id,
            status: (string) $model->status,
            created_at: $model->created_at?->toDateTimeImmutable(),
            updated_at: $model->updated_at?->toDateTimeImmutable(),
        );
    }

    public function createDraft(string $formKey, array $payload = [], ?string $createdBy = null): FormDraftData
    {
        $form = Form::where('key', $formKey)->firstOrFail();

        $draft = FormDraft::create([
            'form_id' => $form->id,
            'schema_json' => $payload['schema_json'] ?? null,
            'ui_schema_json' => $payload['ui_schema_json'] ?? null,
            'created_by' => $createdBy,
        ]);

        return new FormDraftData(
            id: (string) $draft->id,
            form_id: (string) $draft->form_id,
            schema_json: $draft->schema_json,
            ui_schema_json: $draft->ui_schema_json,
            created_by: $draft->created_by,
            created_at: $draft->created_at?->toDateTimeImmutable(),
            updated_at: $draft->updated_at?->toDateTimeImmutable(),
        );
    }

    public function publish(array $baseSchema, array $extensions = [], ?array $uiSchema = null, array $options = []): PublishingResultData
    {
        // Delegate publishing to the FormPublisher service which returns PublishingResultData
        $result = $this->publisher->publish($baseSchema, $extensions, $uiSchema ?? [], $options);

        // Emit a domain event for successful publish so host apps can react
        if ($result->ok) {
            $payload = [
                'ok' => $result->ok,
                'published_form_version_id' => $result->published_form_version_id ?? null,
                'errors' => $result->errors ?? [],
            ];
            event(new FormPublished($payload));
        }

        return $result;
    }

    /** @return StepDescriptorData[] */
    public function deriveSteps(array $uiSchema): array
    {
        // Allow callers to pass either:
        //  - a JSON-Forms Categorization UI schema (handled by StepMapper::derive)
        //  - an already-derived list of step descriptors (plain arrays or DTOs)
        // Detect the latter and bypass StepMapper when appropriate.
        $derived = null;

        if (!empty($uiSchema) && array_values($uiSchema) === $uiSchema) {
            $first = $uiSchema[0] ?? null;
            if (is_array($first) && (isset($first['id']) || isset($first['title']) || isset($first['ui_schema']))) {
                $derived = $uiSchema;
            }
            if ($first instanceof StepDescriptorData) {
                $derived = $uiSchema;
            }
        }

        if ($derived === null) {
            $derived = $this->stepMapper->derive($uiSchema);
        }

        // Normalize into StepDescriptorData instances
        $result = [];
        foreach ($derived as $item) {
            if ($item instanceof StepDescriptorData) {
                $result[] = $item;
                continue;
            }

            $result[] = new StepDescriptorData(
                id: (string) ($item['id'] ?? Str::uuid7()->toString()),
                title: (string) ($item['title'] ?? ''),
                index: (int) ($item['index'] ?? 0),
                ui_schema: $item['ui_schema'] ?? null,
                schema: $item['schema'] ?? null,
            );
        }

        return $result;
    }

    public function buildStepSubschema(array $schema, array $uiStepMaps, string $stepId): array
    {
        return $this->stepSubschemaBuilder->build($schema, $uiStepMaps, $stepId);
    }

    public function validate(array|object $data, array|object $schema, array $options = []): ValidationResultData
    {
        $validationResult = $this->validator->validate($data, $schema);

        // If validator reports valid, return a success result
        if ($validationResult->isValid()) {
            return ValidationResultData::success();
        }

        $errors = $validationResult->errors ?? [];

        // Normalize errors into ValidationErrorData instances
        $out = [];
        foreach ($errors as $err) {
            if ($err instanceof ValidationErrorData) {
                $out[] = $err;
                continue;
            }

            // assume $err is array and use ErrorFormatter to produce ValidationErrorData
            if (is_array($err)) {
                $out[] = $this->errorFormatter->format($err, $schema);
                continue;
            }

            // If the mapper returned a plain object shape, convert to ValidationErrorData
            if (is_object($err)) {
                $path = $err->path ?? ($err->dataPointer ?? '#');
                $code = $err->code ?? ($err->keyword ?? 'invalid');
                $message = $err->message ?? (method_exists($err, '__toString') ? (string)$err : 'Validation failed.');
                $out[] = new ValidationErrorData($path, $code, $message);
                continue;
            }

            // fallback: create a generic ValidationErrorData
            $out[] = new ValidationErrorData(
                path: $err['path'] ?? '#',
                code: $err['code'] ?? 'invalid',
                message: $err['message'] ?? 'Validation failed.'
            );
        }

        return ValidationResultData::failure($out);
    }

    public function submit(array|object $answers, string $formKey, string $version, array $options = []): SubmissionResultData
    {
        $form = Form::where('key', $formKey)->first();

        if ($form === null) {
            return new SubmissionResultData(ok: false, submission_id: null, replayed: false, errors: [
                new ValidationErrorData('#', 'form_not_found', 'Form not found.'),
            ]);
        }

        // Resolve form version by id only (use relation)
        $versionModel = $form->versions()->find($version);

        if ($versionModel === null) {
            return new SubmissionResultData(ok: false, submission_id: null, replayed: false, errors: [
                new ValidationErrorData('#', 'version_not_found', 'Form version not found.'),
            ]);
        }

        // Step 1: schema validation
        $validationResult = $this->validator->validate($answers, $versionModel->schema_json);
        $validationErrors = $validationResult->errors ?? [];

        $formatted = [];
        foreach ($validationErrors as $e) {
            if ($e instanceof ValidationErrorData) {
                $formatted[] = $e;
            } elseif (is_array($e)) {
                $formatted[] = $this->errorFormatter->format($e, $versionModel->schema_json);
            } else {
                $formatted[] = new ValidationErrorData('#', 'invalid', 'Validation failed.');
            }
        }

        // Step 2: business rules
        $businessErr = $this->businessResolver->execute($form->key, ['answers' => $answers]);
        if ($businessErr !== null) {
            $formatted[] = $businessErr;
        }

        if (count($formatted) > 0) {
            $result = new SubmissionResultData(ok: false, submission_id: null, replayed: false, errors: $formatted);
            $payload = [
                'ok' => false,
                'submission_id' => null,
                'replayed' => false,
                'errors' => array_map(fn($e) => $e instanceof \Packages\FormBuilder\Data\ValidationErrorData ? ['path' => $e->path, 'code' => $e->code, 'message' => $e->message] : (is_array($e) ? $e : []), $formatted),
            ];
            event(new FormSubmissionFailed($payload));
            return $result;
        }

        // Persist submission as a FormResponse
        $submission = FormResponse::create([
            'id' => Str::uuid7()->toString(),
            'account_id' => $form->account_id ?? $options['account_id'] ?? null,
            'form_id' => $form->id,
            'form_version_id' => $versionModel->id,
            'form_variant_id' => $options['variant_id'] ?? null,
            'subject_type' => $options['subject_type'] ?? null,
            'subject_id' => $options['subject_id'] ?? null,
            'responses_json' => $answers,
        ]);

        $result = new SubmissionResultData(ok: true, submission_id: (string) $submission->id, replayed: false, errors: []);
        $payload = [
            'ok' => true,
            'submission_id' => (string) $submission->id,
            'replayed' => false,
            'errors' => [],
        ];
        event(new FormSubmitted($payload));

        return $result;
    }

    public function registerBusinessRule(string $formKey, BusinessRuleInterface $handler): void
    {
        $this->businessResolver->register($formKey, $handler);
    }

    public function registerXRule(string $name, XRuleInterface $rule): void
    {
        $this->xrules->register($name, $rule);
    }

}

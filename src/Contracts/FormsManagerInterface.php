<?php
declare(strict_types=1);

namespace Packages\FormBuilder\Contracts;

use Packages\FormBuilder\Data\FormData;
use Packages\FormBuilder\Data\FormDraftData;
use Packages\FormBuilder\Data\PublishingResultData;
use Packages\FormBuilder\Data\StepDescriptorData;
use Packages\FormBuilder\Data\ValidationErrorData;
use Packages\FormBuilder\Data\SubmissionResultData;
use Packages\FormBuilder\Data\ValidationResultData;

/**
 * Step 1 — Forms manager contract
 *
 * Provides a stable interface that the Facade and host apps will target.
 *
 * Notes:
 * - Use fully-qualified DTO types where available.
 * - Keep method contracts minimal and focused; concrete behavior is implemented
 *   in the FormsManager service (future steps).
 */
interface FormsManagerInterface
{
    public function create(array $attrs, ?string $accountId = null): FormData;

    public function findByKey(string $key, ?string $accountId = null): ?FormData;

    public function createDraft(string $formKey, array $payload = [], ?string $createdBy = null): FormDraftData;

    public function publish(array $baseSchema, array $extensions = [], ?array $uiSchema = null, array $options = []): PublishingResultData;

    /** @return StepDescriptorData[] */
    public function deriveSteps(array $uiSchema): array;

    public function buildStepSubschema(array $schema, array $uiStepMaps, string $stepId): array;

    public function validate(array|object $data, array|object $schema, array $options = []): ValidationResultData;

    public function submit(array|object $answers, string $formKey, string $version, array $options = []): SubmissionResultData;

    public function registerBusinessRule(string $formKey, BusinessRuleInterface $handler): void;

    public function registerXRule(string $name, XRuleInterface $rule): void;
}

<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Services\Publishing;

use Packages\FormBuilder\Contracts\SchemaValidatorInterface;
use Packages\FormBuilder\Data\PublishingResultData;

/**
 * FormPublisher
 *
 * Orchestrates fragment + slot composition, step mapping, schema linting,
 * and produces a publishing result (in a real system this would persist
 * form_versions / form_variants / form_access_periods).
 */
final class FormPublisher
{
    private SlotComposer $slotComposer;
    private StepMapper $stepMapper;
    private ContentHasher $hasher;
    private SchemaValidatorInterface $validator;

    public function __construct(
        SlotComposer $slotComposer,
        StepMapper $stepMapper,
        ContentHasher $hasher,
        SchemaValidatorInterface $validator
    ) {
        $this->slotComposer = $slotComposer;
        $this->stepMapper = $stepMapper;
        $this->hasher = $hasher;
        $this->validator = $validator;
    }

    /**
     * Compose and "publish" a form schema.
     *
     * @param array $baseSchema  Base JSON Schema
     * @param array $extensions  Array of extensions (slot/fragment/params/rename_map)
     * @param array $uiSchema    UI schema used to derive steps
     *
     * @return PublishingResultData
     */
    public function publish(array $baseSchema, array $extensions, array $uiSchema): PublishingResultData
    {
        // Compose fragments into the base schema according to slots
        $composed = $this->slotComposer->compose($baseSchema, $extensions);

        // Derive steps from ui schema
        $steps = $this->stepMapper->derive($uiSchema);

        // Compute stable content hash for the combined content
        $hash = $this->hasher->hash([
            'schema' => $composed,
            'ui' => $uiSchema,
            'steps' => $steps,
        ]);

        // Lint/validate schema by calling the validator. The validator returns a ValidationResultData.
        try {
            $validationResult = $this->validator->validate((object) [], $composed);
        } catch (\Throwable $e) {
            return new PublishingResultData(
                ok: false,
                published_form_version_id: null,
                errors: [
                    [
                        'path' => '#',
                        'code' => 'schema_lint_exception',
                        'message' => $e->getMessage(),
                    ],
                ]
            );
        }

        if (!$validationResult->isValid()) {
            return new PublishingResultData(
                ok: false,
                published_form_version_id: null,
                errors: $validationResult->errors?->toArray() ?? []
            );
        }

        // In a full implementation we'd persist form_versions, variants and access periods here.
        // For this package-level orchestrator we return the computed hash as the published version id.
        return new PublishingResultData(
            ok: true,
            published_form_version_id: $hash,
            errors: []
        );
    }
}

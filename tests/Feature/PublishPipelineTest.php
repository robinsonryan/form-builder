<?php

declare(strict_types=1);

use Packages\FormBuilder\Services\Publishing\FormPublisher;
use Packages\FormBuilder\Services\Publishing\SlotComposer;
use Packages\FormBuilder\Services\Publishing\FragmentComposer;
use Packages\FormBuilder\Services\Publishing\StepMapper;
use Packages\FormBuilder\Services\Publishing\ContentHasher;
use Packages\FormBuilder\Contracts\SchemaValidatorInterface;
use Packages\FormBuilder\Data\ValidationResultData;

it('composes slots/fragments, derives steps, lints schema, and returns a published version id (hash)', function (): void {
    $base = [
        'type' => 'object',
        'properties' => [
            'existing' => ['type' => 'string', 'ui:widget' => 'core:InputText'],
        ],
        'slots' => [
            'extras' => '/properties',
        ],
    ];

    $fragment = [
        'type' => 'object',
        'properties' => [
            'age' => ['type' => 'integer', 'title' => 'Age', 'ui:widget' => 'core:InputText'],
        ],
        'required' => ['age'],
    ];

    $extensions = [
        [
            'slot' => 'extras',
            'fragment' => $fragment,
            'params' => ['age' => ['title' => 'Applicant Age']],
            'rename_map' => ['age' => 'applicant_age'],
        ],
    ];

    $uiSchema = [
        'ui:order' => ['existing', 'applicant_age'],
    ];

    // Use a trivial validator double that always returns success (schema lint passes).
    // This keeps this pipeline-focused feature test deterministic while exercising
    // the real composition/hasher/step mapping logic.
    $validator = new class () implements SchemaValidatorInterface {
        public function validate(array|object $data, array|object $schema): ValidationResultData
        {
            return ValidationResultData::success();
        }
    };

    $fragmentComposer = new FragmentComposer();
    $slotComposer = new SlotComposer($fragmentComposer);
    $stepMapper = new StepMapper();
    $hasher = new ContentHasher();

    $publisher = new FormPublisher($slotComposer, $stepMapper, $hasher, $validator);

    $result = $publisher->publish($base, $extensions, $uiSchema);
    expect($result)->toBeInstanceOf(Packages\FormBuilder\Data\PublishingResultData::class);
    expect($result->ok)->toBeTrue();


    // Compute expected composed schema and hash to assert identity
    $expectedComposed = $slotComposer->compose($base, $extensions);
    $expectedSteps = $stepMapper->derive($uiSchema);
    $expectedHash = $hasher->hash([
        'schema' => $expectedComposed,
        'ui' => $uiSchema,
        'steps' => $expectedSteps,
    ]);

    expect($result->published_form_version_id)->toBe($expectedHash);
});

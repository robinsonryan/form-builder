<?php

declare(strict_types=1);

use Packages\FormBuilder\Services\Publishing\StepMapper;
use Packages\FormBuilder\Data\StepDescriptorData;

test('StepMapper produces step descriptors from a simple Categorization UI schema', function () {
    $ui = [
        'type' => 'Categorization',
        'elements' => [
            [
                'type' => 'Category',
                'label' => 'Personal',
                'elements' => [
                    ['type' => 'Control', 'scope' => '#/properties/name'],
                ],
            ],
            [
                'type' => 'Category',
                'label' => 'Contact Info',
                'elements' => [
                    ['type' => 'Control', 'scope' => '#/properties/email'],
                ],
            ],
        ],
    ];

    $deriver = new StepMapper();
    $steps = $deriver->derive($ui);

    // Basic assertions about structure and types
    expect($steps)->toBeArray();
    expect(count($steps))->toBe(2);
    expect($steps[0])->toBeInstanceOf(StepDescriptorData::class);
    expect($steps[0]->title)->toBe('Personal');
    expect($steps[0]->index)->toBe(0);
    expect($steps[1]->title)->toBe('Contact Info');
    expect($steps[1]->index)->toBe(1);

    // IDs should be derived (slugified) from labels
    expect($steps[0]->id)->toBe('personal');
    expect($steps[1]->id)->toBe('contact-info');

    // ui_schema should carry the original category node
    expect($steps[0]->ui_schema)->toBeArray();
    expect($steps[0]->ui_schema['label'])->toBe('Personal');
})->group('publishing');

test('StepMapper returns empty array for non-categorization schemas', function () {
    $ui = ['type' => 'Control', 'scope' => '#/properties/name'];

    $deriver = new StepMapper();
    $steps = $deriver->derive($ui);

    expect($steps)->toBeArray();
    expect($steps)->toBeEmpty();
})->group('publishing');

test('StepMapper generates fallback ids when label missing', function () {
    $ui = [
        'type' => 'Categorization',
        'elements' => [
            ['type' => 'Category', 'elements' => []],
            ['type' => 'Category', 'elements' => []],
        ],
    ];

    $deriver = new StepMapper();
    $steps = $deriver->derive($ui);

    expect(count($steps))->toBe(2);
    expect($steps[0]->id)->toBe('step-0');
    expect($steps[1]->id)->toBe('step-1');
})->group('publishing');

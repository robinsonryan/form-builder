<?php

declare(strict_types=1);

use Packages\FormBuilder\Services\Validation\StepSubschemaBuilder;

it('builds a subschema containing top-level and nested properties with required preserved', function (): void {
    $schema = [
        'type' => 'object',
        'properties' => [
            'name' => ['type' => 'string'],
            'age' => ['type' => 'integer'],
            'address' => [
                'type' => 'object',
                'properties' => [
                    'street' => ['type' => 'string'],
                    'zip' => ['type' => 'string'],
                ],
                'required' => ['street'],
            ],
        ],
        'required' => ['name', 'address'],
    ];

    $uiStepMaps = [
        'step_1' => [
            '/properties/name',
            '/properties/address/properties/street',
        ],
    ];

    $builder = new StepSubschemaBuilder();
    $sub = $builder->build($schema, $uiStepMaps, 'step_1');

    // top-level properties present
    expect($sub['properties'])->toHaveKey('name');
    expect($sub['properties'])->toHaveKey('address');
    expect($sub['properties'])->not->toHaveKey('age');

    // top-level required should include name and address (address was required in original)
    expect($sub)->toHaveKey('required');
    expect($sub['required'])->toContain('name');
    expect($sub['required'])->toContain('address');

    // nested address should contain only street and preserve its required
    expect($sub['properties']['address'])->toHaveKey('properties');
    expect($sub['properties']['address']['properties'])->toHaveKey('street');
    expect($sub['properties']['address']['properties'])->not->toHaveKey('zip');
    expect($sub['properties']['address'])->toHaveKey('required');
    expect($sub['properties']['address']['required'])->toContain('street');
});

it('throws when requested step id is missing', function (): void {
    $schema = ['type' => 'object', 'properties' => ['a' => ['type' => 'string']]];
    $uiStepMaps = [];
    $builder = new StepSubschemaBuilder();

    $builder->build($schema, $uiStepMaps, 'missing_step');
})->throws(InvalidArgumentException::class);

it('throws when pointer does not resolve to a property', function (): void {
    $schema = ['type' => 'object', 'properties' => ['a' => ['type' => 'string']]];
    $uiStepMaps = ['s' => ['/properties/nonexistent']];
    $builder = new StepSubschemaBuilder();

    $builder->build($schema, $uiStepMaps, 's');
})->throws(InvalidArgumentException::class);

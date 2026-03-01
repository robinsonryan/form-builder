<?php

declare(strict_types=1);

use Packages\FormBuilder\Services\Publishing\FragmentComposer;

it('inserts fragment and applies params and rename map', function (): void {
    $base = [
        'type' => 'object',
        'properties' => [
            'existing' => ['type' => 'string'],
        ],
    ];

    $fragment = [
        'type' => 'object',
        'properties' => [
            'age' => ['type' => 'integer', 'title' => 'Age'],
            'status' => ['type' => 'string', 'enum' => ['a', 'b']],
        ],
        'required' => ['age'],
    ];

    $params = [
        'age' => ['title' => 'Applicant Age', 'default' => 30],
        'status' => ['enum' => ['active', 'inactive']],
    ];

    $renameMap = [
        'age' => 'applicant_age',
    ];

    $composer = new FragmentComposer();

    $result = $composer->insertInto($base, '/properties', $fragment, $params, $renameMap);

    // existing prop remains
    expect($result['properties'])->toHaveKey('existing');

    // renamed property exists with substituted title and default
    expect($result['properties'])->toHaveKey('applicant_age');
    expect($result['properties']['applicant_age']['title'])->toBe('Applicant Age');
    expect($result['properties']['applicant_age']['default'])->toBe(30);

    // enum substituted
    expect($result['properties'])->toHaveKey('status');
    expect($result['properties']['status']['enum'])->toBe(['active', 'inactive']);

    // required updated to renamed name
    expect($result)->toHaveKey('required');
    expect($result['required'])->toContain('applicant_age');
});

it('throws on invalid param key', function (): void {
    $base = ['type' => 'object', 'properties' => []];
    $fragment = ['type' => 'object', 'properties' => ['x' => ['type' => 'string']]];
    $params = ['x' => ['not_allowed_key' => 'value']];

    $composer = new FragmentComposer();
    $composer->insertInto($base, '/properties', $fragment, $params, []);
})->throws(InvalidArgumentException::class);

it('throws when renaming missing property', function (): void {
    $base = ['type' => 'object', 'properties' => []];
    $fragment = ['type' => 'object', 'properties' => ['a' => ['type' => 'string']]];
    $renameMap = ['missing' => 'new'];

    $composer = new FragmentComposer();
    $composer->insertInto($base, '/properties', $fragment, [], $renameMap);
})->throws(InvalidArgumentException::class);

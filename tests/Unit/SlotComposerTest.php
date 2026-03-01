<?php

declare(strict_types=1);

use Packages\FormBuilder\Services\Publishing\SlotComposer;
use Packages\FormBuilder\Services\Publishing\FragmentComposer;

it('applies extensions only within declared slots', function (): void {
    $base = [
        'type' => 'object',
        'properties' => [
            'existing' => ['type' => 'string'],
        ],
        'slots' => [
            'slot_a' => '/properties',
            'slot_b' => '/properties',
        ],
    ];

    $fragmentA = [
        'type' => 'object',
        'properties' => [
            'a1' => ['type' => 'string'],
        ],
    ];

    $fragmentB = [
        'type' => 'object',
        'properties' => [
            'b1' => ['type' => 'integer'],
        ],
    ];

    $extensions = [
        ['slot' => 'slot_a', 'fragment' => $fragmentA],
        ['slot' => 'slot_b', 'fragment' => $fragmentB],
    ];

    $composer = new SlotComposer(new FragmentComposer());
    $result = $composer->compose($base, $extensions);

    expect($result['properties'])->toHaveKey('existing');
    expect($result['properties'])->toHaveKey('a1');
    expect($result['properties'])->toHaveKey('b1');
});

it('throws when extension references undeclared slot', function (): void {
    $base = [
        'type' => 'object',
        'properties' => [],
        'slots' => [
            'slot_a' => '/properties',
        ],
    ];

    $extensions = [
        ['slot' => 'not_declared', 'fragment' => ['type' => 'object', 'properties' => ['x' => ['type' => 'string']]]],
    ];

    $composer = new SlotComposer(new FragmentComposer());
    $composer->compose($base, $extensions);
})->throws(InvalidArgumentException::class);

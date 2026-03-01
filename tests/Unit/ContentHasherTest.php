<?php

declare(strict_types=1);

use Packages\FormBuilder\Services\Publishing\ContentHasher;

it('returns the same hash for semantically equivalent content with different key orders', function (): void {
    $a = [
        'b' => 2,
        'a' => 1,
        'nested' => [
            'y' => 2,
            'x' => 1,
        ],
    ];

    $b = [
        'a' => 1,
        'b' => 2,
        'nested' => [
            'x' => 1,
            'y' => 2,
        ],
    ];

    $hasher = new ContentHasher();

    expect($hasher->hash($a))->toBe($hasher->hash($b));
});

it('produces different hashes for different content', function (): void {
    $hasher = new ContentHasher();

    $first = ['a' => 1];
    $second = ['a' => 2];

    expect($hasher->hash($first))->not->toBe($hasher->hash($second));
});

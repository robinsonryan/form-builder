<?php

declare(strict_types=1);

uses(Packages\FormBuilder\Tests\TestCase::class);

it('renderer core smoke test', function (): void {
    // Minimal smoke test to ensure the PHP-side test harness runs.
    // Frontend correctness is covered by Vitest.
    expect(true)->toBeTrue();
});

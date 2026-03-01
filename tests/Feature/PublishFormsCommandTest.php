<?php

declare(strict_types=1);

uses(Packages\FormBuilder\Tests\TestCase::class);

it('runs forms:publish and returns exit code 0', function () {
    $this->artisan('forms:publish')->assertExitCode(0);
});

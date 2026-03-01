<?php

declare(strict_types=1);

uses(Packages\FormBuilder\Tests\TestCase::class);

it('runs forms:lint without path and returns exit code 0', function () {
    $this->artisan('forms:lint')->assertExitCode(0);
});

it('runs forms:lint with path option and returns exit code 0', function () {
    $this->artisan('forms:lint', ['--path' => '.'])->assertExitCode(0);
});

<?php

declare(strict_types=1);

use Packages\FormBuilder\Services\Validation\ErrorFormatter;
use Packages\FormBuilder\Data\ValidationErrorData;

it('formats a json-schema validator error into ValidationErrorData', function (): void {
    $error = [
        'keyword' => 'minLength',
        'instancePath' => '/name',
        'message' => 'must NOT be shorter than 3 characters',
    ];

    $formatter = new ErrorFormatter();
    $result = $formatter->format($error, []);

    expect($result)->toBeInstanceOf(ValidationErrorData::class);
    expect($result->path)->toBe('#/name');
    expect($result->code)->toBe('minLength');
    expect($result->message)->toBe('must NOT be shorter than 3 characters');
});

it('applies x-messages override from the schema', function (): void {
    $error = [
        'keyword' => 'unique_in_period',
        'instancePath' => '/email',
        'message' => 'Email already used',
    ];

    $schema = [
        'x-messages' => [
            'unique_in_period' => 'Email already used in the last 90 days.',
        ],
    ];

    $formatter = new ErrorFormatter();
    $result = $formatter->format($error, $schema);

    expect($result->code)->toBe('unique_in_period');
    expect($result->path)->toBe('#/email');
    expect($result->message)->toBe('Email already used in the last 90 days.');
});

it('interpolates params into x-messages templates', function (): void {
    $error = [
        'keyword' => 'required',
        'instancePath' => '/',
        'params' => ['missingProperty' => 'email'],
    ];

    $schema = [
        'x-messages' => [
            'required' => 'Missing property {missingProperty}',
        ],
    ];

    $formatter = new ErrorFormatter();
    $result = $formatter->format($error, $schema);

    expect($result->code)->toBe('required');
    expect($result->path)->toBe('#/');
    expect($result->message)->toBe('Missing property email');
});

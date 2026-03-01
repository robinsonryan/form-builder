<?php

declare(strict_types=1);

use Illuminate\Container\Container;
use Packages\FormBuilder\Data\ValidationErrorsData;
use Packages\FormBuilder\Data\ValidationResultData;
use Packages\FormBuilder\Services\Validation\OpisJsonSchemaValidator;
use Packages\FormBuilder\Data\ValidationErrorData;

beforeEach(function () {
    // Ensure container and config binding exist for the implementation
    if (!Container::getInstance()) {
        Container::setInstance(new Container());
        app()->instance('config', new \Illuminate\Config\Repository([]));
    }
});

afterEach(function () {
    \Mockery::close();
});

test('JsonSchemaValidator returns empty result when underlying validator returns true', function () {
    // Use the real Opis validator/formatter for this unit-level contract test so we
    // exercise the integration point and avoid issues with anonymous classes that
    // don't satisfy the constructor type-hint.
    $opisValidator = \Mockery::mock(\Opis\JsonSchema\Validator::class);

    $report = \Mockery::mock(\Opis\JsonSchema\ValidationResult::class);
    $report->shouldReceive('isValid')->andReturn(true);
    $report->shouldReceive('hasError')->andReturn(false);
    $report->shouldReceive('error')->andReturn(null);

    $opisValidator->shouldReceive('validate')->andReturn($report);

    $formatter = \Mockery::mock(\Opis\JsonSchema\Errors\ErrorFormatter::class);
    $formatter->shouldReceive('format')->andReturn([]);

    $validator = new OpisJsonSchemaValidator($opisValidator, $formatter);
    $result = $validator->validate((object) [], ['type' => 'object']);

    expect($result)->toBeInstanceOf(ValidationResultData::class)
        ->and($result->isValid())->toBeTrue()
        ->and($result->errors)->toBe([]);
})->group('validation');

test('JsonSchemaValidator returns provided errors when underlying validator returns array of ValidationErrorData', function () {
    $err = new ValidationErrorData(
        path: '#/properties/email',
        code: 'unique_in_period',
        message: 'Email already used in the last 90 days.'
    );

    $opisValidator = \Mockery::mock(\Opis\JsonSchema\Validator::class);

    $report = \Mockery::mock(\Opis\JsonSchema\ValidationResult::class);
    $report->shouldReceive('isValid')->andReturn(false);
    $report->shouldReceive('hasError')->andReturn(true);

    $opisError = \Mockery::mock(\Opis\JsonSchema\Errors\ValidationError::class);
    $report->shouldReceive('error')->andReturn($opisError);

    $opisValidator->shouldReceive('validate')->andReturn($report);

    $formatter = \Mockery::mock(\Opis\JsonSchema\Errors\ErrorFormatter::class);
    $formatter->shouldReceive('format')->andReturn([
        ['pointer' => '#/properties/email', 'keyword' => 'unique_in_period', 'message' => 'Email already used in the last 90 days.']
    ]);

    $validator = new OpisJsonSchemaValidator($opisValidator, $formatter);
    $result = $validator->validate(['email' => 'x@example.com'], ['type' => 'object']);

    expect($result)->toBeInstanceOf(ValidationResultData::class)
        ->and($result->isValid())->toBeFalse()
        ->and($result->errors)->toBeArray()
        ->and(count($result->errors))->toBe(1)
        ->and($result->errors[0])->toBeInstanceOf(ValidationErrorsData::class)
        ->and($result->errors[0]->first())->toBeInstanceOf(ValidationErrorData::class)
        ->and($result->errors[0]->first()->message)->toBe('Email already used in the last 90 days.');
})->group('validation');

test('JsonSchemaValidator returns a generic error when underlying validator returns false', function () {
    $opisValidator = \Mockery::mock(\Opis\JsonSchema\Validator::class);

    $report = \Mockery::mock(\Opis\JsonSchema\ValidationResult::class);
    $report->shouldReceive('isValid')->andReturn(false);
    $report->shouldReceive('hasError')->andReturn(true);

    $opisError = \Mockery::mock(\Opis\JsonSchema\Errors\ValidationError::class);
    $report->shouldReceive('error')->andReturn($opisError);

    $opisValidator->shouldReceive('validate')->andReturn($report);

    $formatter = \Mockery::mock(\Opis\JsonSchema\Errors\ErrorFormatter::class);
    $formatter->shouldReceive('format')->andReturn(null);

    $validator = new OpisJsonSchemaValidator($opisValidator, $formatter);
    $result = $validator->validate([], ['type' => 'object']);

    expect($result)->toBeInstanceOf(ValidationResultData::class)
        ->and($result->isValid())->toBeFalse()
        ->and($result->errors)->toBeArray()
        ->and(count($result->errors))->toBeGreaterThanOrEqual(1)
        ->and($result->errors[0])->toBeInstanceOf(ValidationErrorsData::class);
})->group('validation');

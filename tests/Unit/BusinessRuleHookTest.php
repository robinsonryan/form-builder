<?php

declare(strict_types=1);

use Packages\FormBuilder\Services\Validation\BusinessRuleResolver;
use Packages\FormBuilder\Contracts\BusinessRuleInterface;
use Packages\FormBuilder\Data\ValidationErrorData;

it('returns null when no business rule handler is registered', function (): void {
    $resolver = new BusinessRuleResolver();

    $result = $resolver->execute('form.unknown', []);

    expect($result)->toBeNull();
});

it('invokes a registered business rule handler and returns a ValidationErrorData', function (): void {
    $resolver = new BusinessRuleResolver();

    $handler = new class () implements BusinessRuleInterface {
        public function handle(array $context): ?ValidationErrorData
        {
            return new ValidationErrorData('#/email', 'unique_in_period', 'Email already used in the last 90 days.');
        }
    };

    $resolver->register('form.foo', $handler);

    $result = $resolver->execute('form.foo', ['submission' => ['email' => 'x@example.com']]);

    expect($result)->toBeInstanceOf(ValidationErrorData::class);
    expect($result->path)->toBe('#/email');
    expect($result->code)->toBe('unique_in_period');
    expect($result->message)->toBe('Email already used in the last 90 days.');
});

it('falls back to base form key when versioned key provided', function (): void {
    $resolver = new BusinessRuleResolver();

    $handler = new class () implements BusinessRuleInterface {
        public function handle(array $context): ?ValidationErrorData
        {
            return new ValidationErrorData('#/phone', 'phone_blocked', 'Phone number is blocked.');
        }
    };

    // register handler for base key
    $resolver->register('form.bar', $handler);

    // execute using versioned form key; resolver should fallback to base key handler
    $result = $resolver->execute('form.bar:42', []);

    expect($result)->toBeInstanceOf(ValidationErrorData::class);
    expect($result->path)->toBe('#/phone');
    expect($result->code)->toBe('phone_blocked');
});

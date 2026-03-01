<?php

declare(strict_types=1);

use Packages\FormBuilder\Services\Validation\XRulesRegistry;
use Packages\FormBuilder\Contracts\XRuleInterface;
use Packages\FormBuilder\Data\ValidationErrorData;

it('returns null when handler passes', function (): void {
    $registry = new XRulesRegistry();

    $registry->register('noop', new class implements XRuleInterface {
        public function handle(array $context): ?ValidationErrorData
        {
            return null;
        }
    });

    expect($registry->execute('noop', []))->toBeNull();
});

it('returns validation error from handler', function (): void {
    $registry = new XRulesRegistry();

    $registry->register('unique_in_period', new class implements XRuleInterface {
        public function handle(array $context): ?ValidationErrorData
        {
            return new ValidationErrorData('#/email', 'unique_in_period', 'Email already used in the last 90 days.');
        }
    });

    $result = $registry->execute('unique_in_period', []);
    expect($result)->toBeInstanceOf(ValidationErrorData::class);
    expect($result->code)->toBe('unique_in_period');
});

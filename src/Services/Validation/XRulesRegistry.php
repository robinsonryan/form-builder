<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Services\Validation;

use Packages\FormBuilder\Contracts\XRuleInterface;
use Packages\FormBuilder\Data\ValidationErrorData;
use InvalidArgumentException;

final class XRulesRegistry
{
    /** @var array<string, XRuleInterface> */
    private array $rules = [];

    public function register(string $name, XRuleInterface $rule): void
    {
        $this->rules[$name] = $rule;
    }

    public function execute(string $name, array $context): ?ValidationErrorData
    {
        if (!isset($this->rules[$name])) {
            throw new InvalidArgumentException(sprintf('No x-rule rule registered for "%s".', $name));
        }

        return $this->rules[$name]->handle($context);
    }
}

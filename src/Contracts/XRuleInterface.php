<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Contracts;

use Packages\FormBuilder\Data\ValidationErrorData;

interface XRuleInterface
{
    /**
     * Handle the x-rule.
     *
     * @param array $context Arbitrary context for the rule (payload, config, etc.)
     * @return ValidationErrorData|null Return an error object when rule fails, or null when it passes.
     */
    public function handle(array $context): ?ValidationErrorData;
}

<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Services\Validation\Rules;

use Packages\FormBuilder\Contracts\XRuleInterface;
use Packages\FormBuilder\Data\ValidationErrorData;

/**
 * Skeleton Rule for conditional_required x-rule.
 *
 * Real implementation should enforce conditional required semantics (e.g. field B required when A has value X).
 */
final class ConditionalRequiredHandler implements XRuleInterface
{
    public function handle(array $context): ?ValidationErrorData
    {
        // TODO: implement conditional required logic
        return null;
    }
}

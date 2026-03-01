<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Services\Validation\Rules;

use Packages\FormBuilder\Contracts\XRuleInterface;
use Packages\FormBuilder\Data\ValidationErrorData;

/**
 * Skeleton Rule for unique_in_period x-rule.
 *
 * Real implementation should check whether a given value is unique within a configured time window.
 */
final class UniqueInPeriodHandler implements XRuleInterface
{
    public function handle(array $context): ?ValidationErrorData
    {
        // TODO: implement actual uniqueness check against submissions
        return null;
    }
}

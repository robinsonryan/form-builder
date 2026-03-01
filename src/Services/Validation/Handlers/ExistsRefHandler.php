<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Services\Validation\Rules;

use Packages\FormBuilder\Contracts\XRuleInterface;
use Packages\FormBuilder\Data\ValidationErrorData;

/**
 * Skeleton Rule for exists_ref x-rule.
 *
 * Real implementation should verify that a referenced entity exists (e.g. foreign key, or external reference).
 */
final class ExistsRefHandler implements XRuleInterface
{
    public function handle(array $context): ?ValidationErrorData
    {
        // TODO: implement check against referenced resource
        return null;
    }
}

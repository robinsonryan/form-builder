<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Services\Validation\Rules;

use Packages\FormBuilder\Contracts\XRuleInterface;
use Packages\FormBuilder\Data\ValidationErrorData;

/**
 * Skeleton Rule for hash_match x-rule.
 *
 * Real implementation should compute and compare hashes (e.g. to verify file integrity).
 */
final class HashMatchHandler implements XRuleInterface
{
    public function handle(array $context): ?ValidationErrorData
    {
        // TODO: implement hash computation and comparison
        return null;
    }
}

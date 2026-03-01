<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Services\Validation\Rules;

use Packages\FormBuilder\Contracts\XRuleInterface;
use Packages\FormBuilder\Data\ValidationErrorData;

/**
 * Skeleton Rule for file_exists x-rule.
 *
 * Real implementation should confirm that a referenced file (e.g. S3 key) exists and is accessible.
 */
final class FileExistsHandler implements XRuleInterface
{
    public function handle(array $context): ?ValidationErrorData
    {
        // TODO: implement storage check for existence
        return null;
    }
}

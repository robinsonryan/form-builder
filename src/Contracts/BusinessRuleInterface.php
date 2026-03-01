<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Contracts;

use Packages\FormBuilder\Data\ValidationErrorData;

/**
 * BusinessRuleInterface
 *
 * A simple contract for business-rule handlers that run after x-rules and
 * schema validation. Handlers receive a contextual payload and may return a
 * ValidationErrorData to indicate a validation failure, or null when the
 * check passes.
 */
interface BusinessRuleInterface
{
    /**
     * Execute the business rule against the given context.
     *
     * @param array $context contextual data (form id, form version id, submission, repo services, etc.)
     *
     * @return ValidationErrorData|null
     */
    public function handle(array $context): ?ValidationErrorData;
}

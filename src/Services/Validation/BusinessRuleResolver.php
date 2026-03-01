<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Services\Validation;

use Packages\FormBuilder\Contracts\BusinessRuleInterface;
use Packages\FormBuilder\Data\ValidationErrorData;

/**
 * BusinessRuleResolver
 *
 * Lightweight registry/resolver for per-form (and optionally per-version) business rule handlers.
 *
 * Handlers are registered keyed by a form identifier (e.g. "form.key" or "form.key:1").
 * Resolution attempts an exact match on the provided form key first.
 */
final class BusinessRuleResolver
{
    /**
     * @var array<string,BusinessRuleInterface>
     */
    private array $handlers = [];

    public function register(string $formKey, BusinessRuleInterface $handler): void
    {
        $this->handlers[$formKey] = $handler;
    }

    public function execute(string $formKey, array $context): ?ValidationErrorData
    {
        // Exact match
        if (isset($this->handlers[$formKey])) {
            return $this->handlers[$formKey]->handle($context);
        }

        // Fallback: attempt to match by base form key without version suffix (e.g. "form.key:1" -> "form.key")
        if (strpos($formKey, ':') !== false) {
            [$base] = explode(':', $formKey, 2);
            if (isset($this->handlers[$base])) {
                return $this->handlers[$base]->handle($context);
            }
        }

        return null;
    }
}

<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Services\Validation;

use InvalidArgumentException;
use Packages\FormBuilder\Data\ValidationErrorData;

/**
 * ErrorFormatter
 *
 * Normalize various validator error shapes into the package ValidationErrorData
 * and support schema-level "x-messages" overrides with simple placeholder
 * interpolation from error params (e.g. "{missingProperty}").
 */
final class ErrorFormatter
{
    /**
     * Format a validator error into ValidationErrorData.
     *
     * @param array $error  Error information from a validator (shape may vary)
     * @param array $schema Optional schema to consult for x-messages overrides
     *
     * @return ValidationErrorData
     */
    public function format(array $error, array $schema = []): ValidationErrorData
    {
        $path = $this->extractPath($error);
        $code = $this->extractCode($error);
        $message = $this->extractMessage($error, $code);

        // Apply x-messages override from schema if present
        if (isset($schema['x-messages']) && is_array($schema['x-messages']) && array_key_exists($code, $schema['x-messages'])) {
            $template = $schema['x-messages'][$code];
            $message = $this->interpolateMessage($template, $error['params'] ?? []);
        }

        return new ValidationErrorData($path, $code, $message);
    }

    private function extractPath(array $error): string
    {
        // Prefer common pointer fields, normalize to #/pointer form
        if (!empty($error['instancePath']) && is_string($error['instancePath'])) {
            return '#' . $error['instancePath'];
        }

        if (!empty($error['dataPointer']) && is_string($error['dataPointer'])) {
            return '#' . $error['dataPointer'];
        }

        if (!empty($error['dataPath']) && is_string($error['dataPath'])) {
            return '#' . $error['dataPath'];
        }

        if (!empty($error['path']) && is_string($error['path'])) {
            return $error['path'];
        }

        // Fallback to root
        return '#';
    }

    private function extractCode(array $error): string
    {
        if (!empty($error['keyword']) && is_string($error['keyword'])) {
            return $error['keyword'];
        }

        if (!empty($error['rule']) && is_string($error['rule'])) {
            return $error['rule'];
        }

        if (!empty($error['code']) && is_string($error['code'])) {
            return $error['code'];
        }

        return 'validation_error';
    }

    private function extractMessage(array $error, string $code): string
    {
        if (!empty($error['message']) && is_string($error['message'])) {
            return $error['message'];
        }

        // Some validators provide a 'params' array with extra info; try to render a minimal message
        if (!empty($error['params']) && is_array($error['params'])) {
            try {
                $parts = [];
                foreach ($error['params'] as $k => $v) {
                    if (is_scalar($v)) {
                        $parts[] = sprintf('%s=%s', $k, (string) $v);
                    }
                }
                if ($parts !== []) {
                    return $code . ': ' . implode(', ', $parts);
                }
            } catch (\Throwable $e) {
                // ignore and fallback
            }
        }

        return $code;
    }

    /**
     * Interpolate placeholders in the template using the params array.
     *
     * Placeholders are of the form {key} and will be replaced with the string value
     * of $params['key'] when available. Missing params are left unchanged.
     */
    private function interpolateMessage(string $template, array $params): string
    {
        if (strpos($template, '{') === false) {
            return $template;
        }

        $replacements = [];
        foreach ($params as $k => $v) {
            if (is_scalar($v)) {
                $replacements['{' . $k . '}'] = (string) $v;
            }
        }

        if ($replacements === []) {
            return $template;
        }

        return strtr($template, $replacements);
    }
}

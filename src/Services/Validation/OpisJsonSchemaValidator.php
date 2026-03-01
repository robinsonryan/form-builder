<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Services\Validation;

use Composer\InstalledVersions;
use Opis\JsonSchema\Helper;
use Opis\JsonSchema\Validator as OpisValidator;
use Opis\JsonSchema\Errors\ErrorFormatter as OpisErrorFormatter;
use Packages\FormBuilder\Contracts\SchemaValidatorInterface;
use Packages\FormBuilder\Data\ValidationErrorData;
use Packages\FormBuilder\Data\ValidationErrorsData;
use Packages\FormBuilder\Data\ValidationResultData;
use ReflectionClass;
use ReflectionMethod;

/**
 * Concrete JSON Schema validator wrapper for Opis\JsonSchema\Validator
 *
 */
final class OpisJsonSchemaValidator implements SchemaValidatorInterface
{
    private OpisValidator $validator;
    private OpisErrorFormatter $formatter;

    public function __construct(OpisValidator $validator, ?OpisErrorFormatter $formatter = null)
    {
        // Dependency injected Opis validator and optional formatter.
        $this->validator = $validator;
        $this->formatter = $formatter ?? new OpisErrorFormatter();
    }

    public function validate($data, $schema): ValidationResultData
    {
        try {
            $schema = is_array($schema) ? Helper::toJSON($schema) : $schema;
            $data = is_array($data) ? Helper::toJSON($data) : $data;

            // Use Opis to perform schema validation and obtain a report object.
            $report = $this->validator->validate($data, $schema);

            if ($report->isValid()) {
                return ValidationResultData::success();
            }

            $error = $report->hasError() ? $report->error() : [];

            // Prefer the Opis ErrorFormatter output when available.
            try {
                $formatted = $this->formatter->format($error);
            } catch (\Throwable $e) {
                $formatted = null;
            }

            if (is_array($formatted) && !empty($formatted)) {
                return ValidationResultData::failure($this->mapOpisFormattedErrors($formatted));
            }

            // Fallback: try to extract basic info from the Opis error object.
            $path = '#';
            $code = 'json_schema';
            $message = 'Validation failed';

            try {
                if (method_exists($error, 'dataPointer')) {
                    $path = (string) ($error->dataPointer() ?? '#');
                } elseif (method_exists($error, 'instance')) {
                    $instance = $error->instance();
                    if (is_string($instance)) {
                        $path = $instance;
                    }
                }

                if (method_exists($error, 'keyword')) {
                    $code = (string) ($error->keyword() ?? 'json_schema');
                }

                if (method_exists($error, 'message')) {
                    $message = (string) ($error->message() ?? $message);
                }
            } catch (\Throwable $_) {
                // ignore and use defaults
            }

            return ValidationResultData::failure([new ValidationErrorData($path, $code, $message)]);
        } catch (\Throwable $e) {
            // Always return a ValidationResultData; convert unexpected exceptions into a DTO.
            return ValidationResultData::failure([new ValidationErrorData('#', 'exception', $e->getMessage())], $e->getMessage());
        }
    }

    /**
     * Map a mixed array of errors to ValidationErrorData[]
     */
    private function mapErrorArray(array $errors): array
    {
        $mapped = [];

        foreach ($errors as $err) {
            if ($err instanceof ValidationErrorData) {
                $mapped[] = $err;
                continue;
            }

            if (is_array($err)) {
                $path = $err['path'] ?? $err['dataPointer'] ?? '#';
                $code = $err['code'] ?? $err['keyword'] ?? 'json_schema';
                $message = $err['message'] ?? $this->safeJsonEncode($err);
                $mapped[] = new ValidationErrorData((string)$path, (string)$code, (string)$message);
                continue;
            }

            if (is_object($err)) {
                $path = $err->path ?? ($err->dataPointer ?? '#');
                $code = $err->code ?? ($err->keyword ?? 'json_schema');
                $message = $err->message ?? $this->safeJsonEncode($err);
                $mapped[] = new ValidationErrorData((string)$path, (string)$code, (string)$message);
                continue;
            }

            // Fallback generic error for unknown shapes
            $mapped[] = new ValidationErrorData('#', 'json_schema', $this->safeJsonEncode($err));
        }

        return $mapped;
    }

    /**
     * Safely encode a value to JSON for use as a fallback message.
     */
    private function safeJsonEncode(mixed $value): string
    {
        try {
            return (string) json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            if (is_scalar($value)) {
                return (string) $value;
            }
            return (string) var_export($value, true);
        }
    }

    /**
     * Map the array output from Opis ErrorFormatter to ValidationErrorData DTOs.
     *
     * Opis formatted errors can be nested; this method flattens them while
     * extracting pointer/path, keyword/code and message where present.
     *
     * @param array<int, mixed> $errors
     * @return array<int, ValidationErrorData>
     */
    private function mapOpisFormattedErrors(array $errors): array
    {
        $mapped = [];

        foreach ($errors as $err) {
            if (!is_array($err)) {
                // If unexpected shape, attempt to json encode as fallback.
                $mapped[] = new ValidationErrorData('#', 'json_schema', $this->safeJsonEncode($err));
                continue;
            }

            // Nested errors
            if (isset($err['errors']) && is_array($err['errors'])) {
                $mapped = array_merge($mapped, $this->mapOpisFormattedErrors($err['errors']));
                continue;
            }

            // Common keys produced by Opis ErrorFormatter vary by formatter config.
            $path = $err['pointer'] ?? $err['dataPointer'] ?? $err['instancePointer'] ?? ($err['instance'] ?? '#');
            $code = $err['keyword'] ?? $err['code'] ?? 'json_schema';
            $message = $err['message'] ?? $this->safeJsonEncode($err);

            $mapped[] = new ValidationErrorData((string)$path, (string)$code, (string)$message);
        }

        return $mapped;
    }
}

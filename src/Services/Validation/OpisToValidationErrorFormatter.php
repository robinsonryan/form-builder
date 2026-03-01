<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Services\Validation;

use Opis\JsonSchema\Errors\ErrorFormatter as OpisErrorFormatter;
use Opis\JsonSchema\Errors\ValidationError;

/**
 * OpisToValidationErrorFormatter
 *
 * Purpose:
 * - Use Opis\JsonSchema\Errors\ErrorFormatter helpers to produce a flat list
 *   of validation errors, then map them into the small, deterministic array
 *   shape our application expects: [ ['path'=>..., 'code'=>..., 'message'=>...], ... ]
 *
 * Notes:
 * - This class is intentionally small and focused: the JsonSchemaValidator
 *   injects it and converts the produced arrays into ValidationErrorData DTOs.
 * - The formatter normalizes instance/data pointer formats to start with '#'
 *   so callers get consistent pointers.
 */
final class OpisToValidationErrorFormatter
{
    private OpisErrorFormatter $opis;

    public function __construct(?OpisErrorFormatter $opis = null)
    {
        $this->opis = $opis ?? new OpisErrorFormatter();
    }

    /**
     * Format an Opis ValidationError into an array of errors with keys:
     *  - path  : instance pointer (prefixed with '#')
     *  - code  : keyword or machine-friendly code
     *  - message : human readable message
     *
     * @param ValidationError $error
     * @return array<int, array{path:string,code:string,message:string}>
     */
    public function format(ValidationError $error): array
    {
        // Use Opis' flat formatter helper with a custom callback that keeps
        // the useful pieces: keyword, formatted message, and a dataPath.
        $custom = function (ValidationError $err) {
            return [
                'keyword' => $err->keyword(),
                'message' => $this->opis->formatErrorMessage($err),
                'dataPath' => $this->opis->formatErrorKey($err),
            ];
        };

        $flat = $this->opis->formatFlat($error, $custom);

        $mapped = [];
        foreach ($flat as $item) {
            if (!is_array($item)) {
                $mapped[] = [
                    'path' => '#',
                    'code' => 'json_schema',
                    'message' => is_scalar($item) ? (string)$item : $this->safeJsonEncode($item),
                ];
                continue;
            }

            $path = $item['dataPath'] ?? '#';
            $code = $item['keyword'] ?? 'json_schema';
            $message = $item['message'] ?? (is_scalar($item) ? (string)$item : $this->safeJsonEncode($item));

            $mapped[] = [
                'path' => $this->normalizeInstancePath((string)$path),
                'code' => (string)$code,
                'message' => (string)$message,
            ];
        }

        return $mapped;
    }

    /**
     * Normalize instance/data paths into a consistent JSON Pointer style:
     * - '' or null -> '#'
     * - '/foo'     -> '#/foo'
     * - '#/foo'    -> '#/foo' (unchanged)
     */
    private function normalizeInstancePath(string $path): string
    {
        if ($path === '' || $path === null) {
            return '#';
        }

        if (str_starts_with($path, '#')) {
            return $path;
        }

        if (str_starts_with($path, '/')) {
            return '#' . $path;
        }

        return '#/' . ltrim($path, '/');
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
}

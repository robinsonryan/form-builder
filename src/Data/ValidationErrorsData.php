<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Data;

final class ValidationErrorsData
{
    /**
     * @param ValidationErrorData[] $items
     */
    public function __construct(
        public array $items = []
    ) {
    }

    /**
     * Create from mixed array of errors.
     *
     * Accepts ValidationErrorData instances, arrays or objects that map to ValidationErrorData.
     *
     * @param array<int, mixed> $errors
     */
    public static function fromArray(array $errors): self
    {
        $items = [];

        foreach ($errors as $err) {
            if ($err instanceof ValidationErrorData) {
                $items[] = $err;
                continue;
            }

            if (is_array($err)) {
                $items[] = new ValidationErrorData(
                    $err['path'] ?? ($err['dataPointer'] ?? '#'),
                    $err['code'] ?? ($err['keyword'] ?? 'json_schema'),
                    $err['message'] ?? json_encode($err, JSON_UNESCAPED_UNICODE)
                );
                continue;
            }

            if (is_object($err)) {
                $items[] = new ValidationErrorData(
                    $err->path ?? ($err->dataPointer ?? '#'),
                    $err->code ?? ($err->keyword ?? 'json_schema'),
                    $err->message ?? (method_exists($err, '__toString') ? (string)$err : json_encode($err, JSON_UNESCAPED_UNICODE))
                );
                continue;
            }

            $items[] = new ValidationErrorData('#', 'json_schema', is_scalar($err) ? (string)$err : json_encode($err, JSON_UNESCAPED_UNICODE));
        }

        return new self($items);
    }

    /**
     * Return plain array representation.
     *
     * @return array<int, ValidationErrorData>
     */
    public function toArray(): array
    {
        return $this->items;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function first(): ?ValidationErrorData
    {
        return $this->items[0] ?? null;
    }
}

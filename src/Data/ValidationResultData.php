<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Data;

use Spatie\LaravelData\Data;

final class ValidationResultData extends Data
{
    public bool $valid;
    public array $errors;
    public ?string $message;

    private function __construct(bool $valid, array $errors = [], ?string $message = null)
    {
        $this->valid = $valid;
        $this->errors = $errors;
        $this->message = $message;
    }

    public static function success(?string $message = null): self
    {
        return new self(true, [], $message);
    }

    /**
     * @param array<int, mixed>|ValidationErrorsData $errors
     */
    public static function failure(array|ValidationErrorsData $errors, ?string $message = null): self
    {
        if ($errors instanceof ValidationErrorsData) {
            $errs[] = $errors;
        } else {
            $errs[] = ValidationErrorsData::fromArray($errors);
        }

        return new self(false, $errs, $message);
    }

    public function isValid(): bool
    {
        return $this->valid;
    }
}

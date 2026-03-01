<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Data;

use Spatie\LaravelData\Data;

final class ValidationErrorData extends Data
{
    public function __construct(
        public string $path,
        public string $code,
        public string $message,
    ) {
    }
}

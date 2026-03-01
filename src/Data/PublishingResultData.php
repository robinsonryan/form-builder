<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Data;

use Spatie\LaravelData\Data;

/**
 * @implements array<int, ValidationErrorData>
 */
final class PublishingResultData extends Data
{
    /**
     * @param ValidationErrorData[] $errors
     */
    public function __construct(
        public bool $ok,
        public ?string $published_form_version_id = null,
        public array $errors = [],
    ) {
    }
}

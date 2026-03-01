<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Data;

use Spatie\LaravelData\Data;

final class SubmissionResultData extends Data
{
    public function __construct(
        public bool $ok,
        public ?string $submission_id = null,
        public bool $replayed = false,
        public array $errors = []
    ) {
    }
}

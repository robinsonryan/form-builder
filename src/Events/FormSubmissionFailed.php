<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Events;

final class FormSubmissionFailed
{
    public function __construct(
        public array $data
    ) {
    }
}

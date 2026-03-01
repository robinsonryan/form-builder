<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Events;

final class FormPublished
{
    public function __construct(
        public array $data
    ) {
    }
}

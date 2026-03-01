<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Data;

use Spatie\LaravelData\Data;

final class StepDescriptorData extends Data
{
    public function __construct(
        public string $id,
        public string $title,
        public int $index,
        public ?array $ui_schema = null,
        public ?array $schema = null,
    ) {
    }
}

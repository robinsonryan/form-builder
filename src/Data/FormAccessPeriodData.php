<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Data;

use Spatie\LaravelData\Data;

final class FormAccessPeriodData extends Data
{
    public function __construct(
        public string $id,
        public string $form_id,
        public ?\DateTimeImmutable $starts_at = null,
        public ?\DateTimeImmutable $ends_at = null,
        public bool $enabled = true,
        public ?\DateTimeImmutable $created_at = null,
        public ?\DateTimeImmutable $updated_at = null,
    ) {
    }
}

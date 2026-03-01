<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Data;

use Spatie\LaravelData\Data;

final class FormVariantData extends Data
{
    public function __construct(
        public string $id,
        public string $form_id,
        public string $key,
        public string $title,
        public ?array $ui_schema_json = null,
        public ?\DateTimeImmutable $created_at = null,
        public ?\DateTimeImmutable $updated_at = null,
    ) {
    }
}

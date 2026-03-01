<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Data;

use Spatie\LaravelData\Data;

final class FormVersionData extends Data
{
    public function __construct(
        public string $id,
        public string $form_id,
        public int $version_number,
        public array $schema_json,
        public ?array $ui_schema_json = null,
        public ?\DateTimeImmutable $published_at = null,
        public ?\DateTimeImmutable $created_at = null,
        public ?\DateTimeImmutable $updated_at = null,
    ) {
    }
}

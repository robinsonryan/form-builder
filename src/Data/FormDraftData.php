<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Data;

use Spatie\LaravelData\Data;

final class FormDraftData extends Data
{
    public function __construct(
        public string $id,
        public string $form_id,
        public ?array $schema_json = null,
        public ?array $ui_schema_json = null,
        public ?string $created_by = null,
        public ?\DateTimeImmutable $created_at = null,
        public ?\DateTimeImmutable $updated_at = null,
    ) {
    }
}

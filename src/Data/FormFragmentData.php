<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Data;

use Spatie\LaravelData\Data;

final class FormFragmentData extends Data
{
    public function __construct(
        public string $id,
        public string $key,
        public string $title,
        public string $owner_scope,
        public ?string $account_id = null,
        public array $schema_fragment_json = [],
        public ?array $ui_fragment_json = null,
        public ?\DateTimeImmutable $created_at = null,
        public ?\DateTimeImmutable $updated_at = null,
    ) {
    }
}

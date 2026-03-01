<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Data;

use Spatie\LaravelData\Data;

final class FormData extends Data
{
    public function __construct(
        public string $id,
        public string $key,
        public string $title,
        public string $owner_scope,
        public ?string $account_id,
        public bool $tenant_visible,
        public ?string $parent_form_id,
        public string $status,
        public ?\DateTimeImmutable $created_at = null,
        public ?\DateTimeImmutable $updated_at = null,
    ) {
    }
}

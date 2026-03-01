<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Data;

use Spatie\LaravelData\Data;

final class PublishingCommandData extends Data
{
    public function __construct(
        public string $form_id,
        public ?string $form_draft_id = null,
        public ?string $initiator_user_id = null,
        public bool $force = false,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Console\Commands;

use Illuminate\Console\Command;

/**
 * Thin wrapper command that triggers the package publishing pipeline.
 * For now this command is a no-op placeholder that informs the developer.
 * Implement full behaviour to integrate with FormPublisher service.
 */
final class PublishFormsCommand extends Command
{
    protected $signature = 'forms:publish';
    protected $description = 'Compose slots/fragments, lint and publish a form (placeholder)';

    public function handle(): int
    {
        $this->comment('forms:publish is not yet fully implemented in this workspace. Use forms:seed-sample for demo data.');

        return 0;
    }
}

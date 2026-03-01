<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Console\Commands;

use Illuminate\Console\Command;

/**
 * Placeholder command for linting form JSON schemas.
 * Extend to call the package schema validator or external tooling.
 */
final class LintFormsCommand extends Command
{
    protected $signature = 'forms:lint {--path= : Optional path to lint}';
    protected $description = 'Lint form JSON schemas (placeholder)';

    public function handle(): int
    {
        $this->comment('forms:lint is a placeholder. No schemas were linted.');

        return 0;
    }
}

<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Packages\FormBuilder\Models\Form;
use Packages\FormBuilder\Models\FormVersion;

final class SeedSampleFormsCommand extends Command
{
    protected $signature = 'forms:seed-sample';
    protected $description = 'Seed a sample global form and a published version for development';

    public function handle(): int
    {
        $this->info('Seeding sample form...');

        $formId = Str::uuid7()->toString();

        $form = Form::create([
            'id' => $formId,
            'key' => 'sample-form',
            'title' => 'Sample Form (seeded)',
            'owner_scope' => 'global',
            'account_id' => null,
            'tenant_visible' => true,
            'parent_form_id' => null,
            'status' => 'active',
        ]);

        FormVersion::create([
            'id' => Str::uuid7()->toString(),
            'form_id' => $form->id,
            'version_number' => 1,
            'schema_json' => [
                'type' => 'object',
                'properties' => [
                    'name' => ['type' => 'string'],
                    'email' => ['type' => 'string', 'format' => 'email'],
                ],
                'required' => ['name', 'email'],
            ],
            'ui_schema_json' => null,
            'published_at' => now(),
        ]);

        $this->info('Sample form seeded with key "sample-form".');

        return 0;
    }
}

<?php
declare(strict_types=1);

namespace Packages\FormBuilder\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Packages\FormBuilder\Models\Form;
use Packages\FormBuilder\Models\FormDraft;

final class FormDraftFactory extends Factory
{
    protected $model = FormDraft::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid7(),
            'form_id' => Form::factory(),
            'account_id' => null,
            'schema_json' => ['type' => 'object', 'properties' => []],
            'ui_schema_json' => [],
            'slots_json' => [],
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}

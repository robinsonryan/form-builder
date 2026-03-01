<?php
declare(strict_types=1);

namespace Packages\FormBuilder\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Packages\FormBuilder\Models\Form;
use Packages\FormBuilder\Models\FormExtension;

final class FormExtensionFactory extends Factory
{
    protected $model = FormExtension::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid7(),
            'base_form_id' => Form::factory(),
            'account_id' => null,
            'name' => $this->faker->word,
            'extension_schema_json' => ['type' => 'object', 'properties' => []],
            'extension_ui_json' => [],
        ];
    }
}

<?php
declare(strict_types=1);

namespace Packages\FormBuilder\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Packages\FormBuilder\Models\FormVersion;
use Packages\FormBuilder\Models\FormVariant;

final class FormVariantFactory extends Factory
{
    protected $model = FormVariant::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid7(),
            'account_id' => null,
            'form_version_id' => FormVersion::factory(),
            'key' => $this->faker->unique()->word,
            'ui_schema_key' => 'default',
            'traffic_allocation' => 100,
        ];
    }
}

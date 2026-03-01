<?php
declare(strict_types=1);

namespace Packages\FormBuilder\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Packages\FormBuilder\Models\FormFragment;

final class FormFragmentFactory extends Factory
{
    protected $model = FormFragment::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid7(),
            'key' => $this->faker->unique()->slug(2),
            'title' => $this->faker->sentence(2),
            'owner_scope' => 'global',
            'tenant_visible' => true,
            'account_id' => null,
            'schema_fragment_json' => ['type' => 'object', 'properties' => []],
            'ui_fragment_json' => null,
            'params_schema_json' => null,
            'slots_json' => null,
            'status' => 'active',
        ];
    }
}

<?php
declare(strict_types=1);

namespace Packages\FormBuilder\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Packages\FormBuilder\Models\Form;

final class FormFactory extends Factory
{
    protected $model = Form::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid7(),
            'key' => $this->faker->unique()->slug(2),
            'title' => $this->faker->sentence(3),
            'owner_scope' => 'global',
            'account_id' => null,
            'tenant_visible' => true,
            'parent_form_id' => null,
            'status' => 'active',
        ];
    }
}

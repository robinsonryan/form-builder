<?php
declare(strict_types=1);

namespace Packages\FormBuilder\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Packages\FormBuilder\Models\Form;
use Packages\FormBuilder\Models\FormVersion;

final class FormVersionFactory extends Factory
{
    protected $model = FormVersion::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid7()->toString(),
            'form_id' => Form::factory(),
            'account_id' => null,
            'semver' => $this->faker->numerify('1.0.0'),
            'ui_schema_json' => ['type' => 'object', 'properties' => (object) []],
            'schema_json' => [],
            'slots_json' => [],
            'ui_step_maps' => [],
            'fragment_version_ids' => [],
            'content_hash' => $this->faker->sha1,
            'published_by' => null,
            'published_at' => Carbon::now(),
        ];
    }
}

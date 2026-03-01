<?php
declare(strict_types=1);

namespace Packages\FormBuilder\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Packages\FormBuilder\Models\FormFragment;
use Packages\FormBuilder\Models\FormFragmentVersion;

final class FormFragmentVersionFactory extends Factory
{
    protected $model = FormFragmentVersion::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid7(),
            'account_id' => null,
            'fragment_id' => FormFragment::factory(),
            'semver' => $this->faker->numerify('1.0.0'),
            'schema_fragment_json' => ['type' => 'object', 'properties' => []],
            'ui_fragment_json' => null,
            'params_schema_json' => null,
            'slots_json' => null,
            'content_hash' => $this->faker->sha1,
            'published_at' => Carbon::now(),
        ];
    }
}

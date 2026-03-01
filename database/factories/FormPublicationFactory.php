<?php
declare(strict_types=1);

namespace Packages\FormBuilder\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Packages\FormBuilder\Models\Form;
use Packages\FormBuilder\Models\FormVersion;
use Packages\FormBuilder\Models\FormPublication;

final class FormPublicationFactory extends Factory
{
    protected $model = FormPublication::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid7(),
            'base_form_id' => Form::factory(),
            'account_id' => null,
            'form_version_id' => FormVersion::factory(),
            'extension_ids' => [],
        ];
    }
}

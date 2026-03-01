<?php
declare(strict_types=1);

namespace Packages\FormBuilder\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Packages\FormBuilder\Models\Form;
use Packages\FormBuilder\Models\FormVersion;
use Packages\FormBuilder\Models\FormResponse;

final class FormResponseFactory extends Factory
{
    protected $model = FormResponse::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid7(),
            'account_id' => (string) Str::uuid7(),
            'form_id' => Form::factory(),
            'form_version_id' => FormVersion::factory(),
            'form_variant_id' => null,
            'subject_type' => 'user',
            'subject_id' => Str::uuid7(),
            'responses_json' => ['answers' => []],
            'submitted_at' => Carbon::now()
        ];
    }
}

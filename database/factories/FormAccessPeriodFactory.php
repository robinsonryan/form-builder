<?php
declare(strict_types=1);

namespace Packages\FormBuilder\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Packages\FormBuilder\Models\FormVersion;
use Packages\FormBuilder\Models\FormAccessPeriod;

final class FormAccessPeriodFactory extends Factory
{
    protected $model = FormAccessPeriod::class;

    public function definition(): array
    {
        $starts = Carbon::now()->subDays(rand(0, 10));
        $ends = (clone $starts)->addDays(rand(1, 30));

        return [
            'id' => (string) Str::uuid7(),
            'account_id' => null,
            'form_version_id' => FormVersion::factory(),
            'starts_at' => $starts,
            'ends_at' => $ends,
            'status' => 'scheduled',
        ];
    }
}

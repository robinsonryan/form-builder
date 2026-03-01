<?php
declare(strict_types=1);

namespace Packages\FormBuilder\Tests\Unit;

use Packages\FormBuilder\Tests\TestCase;
use Packages\FormBuilder\Models\FormVersion;
use Packages\FormBuilder\Models\Form;
use Illuminate\Support\Carbon;

class FormVersionModelTest extends TestCase
{
    public function test_factory_creates_a_form_version_and_relates_to_form(): void
    {
        $version = FormVersion::factory()->create();
        $this->assertNotEmpty($version->id);
        $this->assertInstanceOf(Form::class, $version->form()->getRelated());
    }

    public function test_published_at_is_cast_to_datetime(): void
    {
        $version = FormVersion::factory()->create(['published_at' => Carbon::now()]);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $version->fresh()->published_at);
    }
}

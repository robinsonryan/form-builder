<?php
declare(strict_types=1);

namespace Packages\FormBuilder\Tests\Unit;

use Packages\FormBuilder\Tests\TestCase;
use Packages\FormBuilder\Models\FormFragment;

class FormFragmentModelTest extends TestCase
{
    public function test_fragment_fields_and_casts(): void
    {
        $fragment = FormFragment::factory()->create([
            'tenant_visible' => false,
            'schema_fragment_json' => ['foo' => 'bar'],
            'ui_fragment_json' => null,
            'params_schema_json' => null,
            'slots_json' => null,
        ]);

        $fresh = $fragment->fresh();

        $this->assertIsArray($fresh->schema_fragment_json);
        $this->assertFalse($fresh->tenant_visible);
        $this->assertArrayHasKey('foo', $fresh->schema_fragment_json);
    }
}

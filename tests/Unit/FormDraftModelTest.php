<?php
declare(strict_types=1);

namespace Packages\FormBuilder\Tests\Unit;

use Packages\FormBuilder\Tests\TestCase;
use Packages\FormBuilder\Models\FormDraft;

class FormDraftModelTest extends TestCase
{
    public function test_json_fields_are_cast_to_array(): void
    {
        $draft = FormDraft::factory()->create([
            'schema_json' => ['type' => 'object'],
            'ui_schema_json' => ['ui' => []],
            'slots_json' => ['slot' => []],
        ]);

        $fresh = $draft->fresh();

        $this->assertIsArray($fresh->schema_json);
        $this->assertIsArray($fresh->ui_schema_json);
        $this->assertIsArray($fresh->slots_json);
    }
}

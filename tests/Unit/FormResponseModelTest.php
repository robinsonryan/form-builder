<?php
declare(strict_types=1);

namespace Packages\FormBuilder\Tests\Unit;

use Packages\FormBuilder\Tests\TestCase;
use Packages\FormBuilder\Models\FormResponse;

class FormResponseModelTest extends TestCase
{
    public function test_responses_json_casts_and_timestamps(): void
    {
        $submission = FormResponse::factory()->create([
            'responses_json' => ['answers' => ['q1' => 'a1']],
        ]);

        $fresh = $submission->fresh();
        $this->assertIsArray($fresh->responses_json);
        $this->assertArrayHasKey('answers', $fresh->responses_json);
        $this->assertNotNull($fresh->submitted_at);
    }
}

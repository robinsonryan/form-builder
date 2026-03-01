<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Packages\FormBuilder\Models\Form;
use Packages\FormBuilder\Models\FormVersion;

uses(Packages\FormBuilder\Tests\TestCase::class);

it('returns published form version payload', function () {
    $form = Form::factory()->create([
        'key' => 'contact-form',
        'title' => 'Contact Form',
    ]);

    $version = FormVersion::factory()->create([
        'form_id' => $form->id,
        'semver' => '1.0.0',
        'schema_json' => ['type' => 'object', 'properties' => (object) ['name' => ['type' => 'string']]],
        'ui_schema_json' => ['ui' => ['layout' => 'default']],
        'content_hash' => 'deadbeef',
        'published_at' => now(),
    ]);

    $response = $this->getJson("/api/form-builder/forms/{$form->key}/versions/{$version->id}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'ok',
            'form' => ['id', 'key', 'title', 'owner_scope', 'account_id', 'tenant_visible', 'status'],
            'version' => ['id', 'content_hash', 'schema', 'ui', 'ui_step_maps', 'published_at'],
        ])
        ->assertJson(['ok' => true]);

    // ui_step_maps should be an array (may be empty)
    $this->assertIsArray($response->json('version.ui_step_maps'));

    // Caching headers for immutable published versions (order-insensitive)
    $cache = $response->headers->get('Cache-Control');
    $this->assertNotEmpty($cache);
    $this->assertStringContainsString('public', $cache);
    $this->assertStringContainsString('max-age=31536000', $cache);
    $response->assertHeader('ETag', '"' . $version->content_hash . '"');
});

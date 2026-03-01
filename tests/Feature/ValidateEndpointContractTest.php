<?php

declare(strict_types=1);

require_once __DIR__ . '/../fixtures/form_fixtures.php';

uses(Packages\FormBuilder\Tests\TestCase::class);

it('validate endpoint returns contract shape', function (): void {
    // Arrange: use fixture to create a minimal form + form version
    [$form, $version] = create_test_form_and_version();

    $payload = [
        'form_version_id' => $version->id,
        'data' => ['example' => 'value'],
    ];

    // Act: POST to the validate endpoint (contract: { ok: bool, errors: [] })
    $response = $this->postJson("/api/form-builder/forms/{$form->key}/versions/{$version->id}/validate", $payload);

    // Assert: response shape and types
    $response->assertStatus(200)
        ->assertJsonStructure([
            'valid',
            'errors',
            'message'
        ]);

    expect(is_bool($response->json('valid')))->toBeTrue()
        ->and(is_array($response->json('errors')))->toBeTrue();
});

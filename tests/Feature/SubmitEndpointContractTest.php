<?php

declare(strict_types=1);

require_once __DIR__ . '/../fixtures/form_fixtures.php';

uses(Packages\FormBuilder\Tests\TestCase::class);

it('submit endpoint returns contract shape', function (): void {
    // Arrange: use fixture to create a minimal form + form version
    [$form, $version] = create_test_form_and_version();

    $payload = [
        'form_version_id' => $version->id,
        'data' => ['example' => 'value'],
        'idempotency_key' => 'test-key-123',
    ];

    // Act: POST to the submit endpoint (contract: { ok: bool, submission_id|null, errors: [] })
    $response = $this->postJson("/api/form-builder/forms/{$form->key}/versions/{$version->id}/submit", $payload);

    // Assert: response shape and types (allow any 2xx successful status)
    $response->assertSuccessful()
        ->assertJsonStructure([
            'ok',
            'submission_id',
            'errors',
        ]);

    expect(is_bool($response->json('ok')))->toBeTrue();
    $submissionId = $response->json('submission_id');
    expect($submissionId === null || is_string($submissionId))->toBeTrue();
    expect(is_array($response->json('errors')))->toBeTrue();
});

<?php

declare(strict_types=1);

uses(Packages\FormBuilder\Tests\TestCase::class);

it('file presign endpoint returns uploadUrl and key', function (): void {
    // Arrange
    $payload = [
        'filename' => 'example.pdf',
        'content_type' => 'application/pdf',
    ];

    // Act
    $response = $this->postJson('/api/form-builder/file-presign', $payload);

    // Assert
    $response->assertSuccessful()
        ->assertJsonStructure([
            'ok',
            'uploadUrl',
            'key',
            'filename',
            'content_type',
        ]);

    expect($response->json('ok'))->toBeTrue();
    expect(is_string($response->json('uploadUrl')))->toBeTrue();
    expect(is_string($response->json('key')))->toBeTrue();
});

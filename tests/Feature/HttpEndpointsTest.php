<?php

declare(strict_types=1);

uses(Packages\FormBuilder\Tests\TestCase::class);

use Packages\FormBuilder\Models\Form;
use Packages\FormBuilder\Models\FormVersion;
use Packages\FormBuilder\Models\FormResponse;

it('lookup returns 200 for existing form', function () {
    $form = Form::factory()->create([
        'key' => 'test-lookup',
        'title' => 'Lookup Form',
        'owner_scope' => 'global',
    ]);

    $response = $this->getJson('/api/form-builder/forms/test-lookup');

    $response->assertStatus(200)
        ->assertJson(['ok' => true]);

    $body = $response->json();
    expect($body['form']['key'] ?? null)->toBe('test-lookup');
});

it('submit creates a submission and returns 201', function () {
    $form = Form::factory()->create([
        'key' => 'test-submit',
        'title' => 'Submit Form',
        'owner_scope' => 'global',
    ]);

    // create semver version 1.0.0 so submission endpoint can find it
    $formVersion = FormVersion::factory()->create([
        'form_id' => $form->id,
        'semver' => '1.0.0',
        'schema_json' => ['type' => 'object', 'properties' => (object) []],
    ]);

    $payload = ['answers' => ['field' => 'value'], 'options' => ['subject_type' => 'user', 'subject_id' => 'c9dd24f5-e1d0-4a32-a982-eca391f521bb']];

    $uri = sprintf('/api/form-builder/forms/test-submit/versions/%s/submit', $formVersion->id);

    $response = $this->withHeaders(['X-Account-Id' => 'c9dd24f5-e1d0-4a32-a982-eca391f521bb'])->postJson($uri, $payload);

    $response->assertStatus(201)
      ->assertJsonFragment(['ok' => true]);

    expect(FormResponse::query()->count())->toBe(1);
});

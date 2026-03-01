<?php

declare(strict_types=1);

use Packages\FormBuilder\Models\Form;
use Packages\FormBuilder\Models\FormResponse;
use Packages\FormBuilder\Models\FormVersion;
use Illuminate\Support\Str;
use Packages\FormBuilder\Facades\Former;
use Packages\FormBuilder\Data\SubmissionResultData;

uses(Packages\FormBuilder\Tests\TestCase::class);

it('derives steps, validates and submits form end-to-end', function () {
    // Create a basic form and a published version with a permissive schema (object)
    $form = Form::factory()->create([
        'key' => 'contact-integration',
        'title' => 'Contact Integration Form',
    ]);

    $version = FormVersion::factory()->create([
        'form_id' => $form->id,
        'semver' => "1.0.0",
        'schema_json' => ['type' => 'object'],
        'ui_schema_json' => ['type' => 'object'],
    ]);

    // Derive steps (should not throw and should return an array)
    $steps = Former::deriveSteps([]);
    expect($steps)->toBeArray();

    // Validate data against the version schema (permissive; should pass)
    $validation = Former::validate((object) [1,2,3], $version->schema_json);
    expect($validation)->toBeInstanceOf(\Packages\FormBuilder\Data\ValidationResultData::class)
        ->and($validation->isValid())->toBeTrue()
        ->and($validation->errors)->toBe([]);

    // Submit and assert result valid and persistence
    $account_id = (string) Str::uuid7();
    $options = ['account_id' => $account_id,
        'subject_type' => 'user',
        'subject_id' => Str::uuid7()];
    $result = Former::submit(['foo' => 'bar'], $form->key, (string)$version->id, $options);
    expect($result)->toBeInstanceOf(SubmissionResultData::class)
        ->and($result->valid ?? $result->ok)->toBeTrue();

    $exists = FormResponse::where('form_id', $form->id)->exists();
    expect($exists)->toBeTrue();
})->group('integration');

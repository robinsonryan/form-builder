<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Packages\FormBuilder\Models\FormResponse;
use Packages\FormBuilder\Models\Form;
use Packages\FormBuilder\Models\FormVersion;
use Packages\FormBuilder\Models\IdempotencyKey;
use Packages\FormBuilder\Http\Middleware\EnsureIdempotencyKey;

uses(Packages\FormBuilder\Tests\TestCase::class);

it('prevents duplicate execution for the same idempotency key', function () {
    // Ensure a clean slate.
    FormResponse::query()->delete();
    IdempotencyKey::query()->delete();

    // Build a POST request that would represent a final submission.
    $payload = ['foo' => 'bar'];
    $request = Request::create('/api/form-builder/forms/test/versions/1/submit', 'POST', [], [], [], [], json_encode($payload));
    $request->headers->set('Content-Type', 'application/json');
    $request->headers->set('Idempotency-Key', 'abc-123-key');

    // Ensure idempotency handling is enabled in config for the test.
    config()->set('forms.idempotency_required', true);

    // Resolve middleware from the container so any dependencies are satisfied.
    $middleware = app(EnsureIdempotencyKey::class);

    $action = function ($req) {
        // Simulate creation of a submission (what the real controller would do).
        // Use model factories to satisfy foreign key constraints.
        $form = Form::factory()->create([
            'account_id' => Str::uuid7()->toString(),
            'key' => 'test-form',
            'title' => 'Test Form',
        ]);

        // Create a corresponding form version
        $version = FormVersion::factory()->create([
            'form_id' => $form->id,
        ]);

        FormResponse::factory()->create([
            'account_id' => Str::uuid7()->toString(),
            'form_id' => $form->id,
            'form_version_id' => $version->id,
            'responses_json' => json_encode(['a' => 1]),
            'subject_type' => 'user',
            'subject_id' => Str::uuid7()->toString(),
        ]);

        return response()->json(['ok' => true, 'created' => true], 201);
    };

    $response1 = $middleware->handle($request, $action);

    expect(FormResponse::query()->count())->toBe(1);
    expect($response1->status())->toBe(201);

    // Second run with same key should not cause another insertion and should return the same body.
    $response2 = $middleware->handle($request, $action);

    expect(FormResponse::query()->count())->toBe(1);
    expect($response2->status())->toBe(201);
    expect($response2->getContent())->toEqual($response1->getContent());
});

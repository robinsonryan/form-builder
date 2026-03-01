<?php

declare(strict_types=1);

uses(Packages\FormBuilder\Tests\TestCase::class);

use Illuminate\Support\Facades\Event;
use Packages\FormBuilder\Contracts\FormsManagerInterface;
use Packages\FormBuilder\Contracts\SchemaValidatorInterface;
use Packages\FormBuilder\Data\ValidationResultData;
use Packages\FormBuilder\Data\ValidationErrorData;
use Packages\FormBuilder\Models\Form;
use Packages\FormBuilder\Models\FormVersion;
use Packages\FormBuilder\Models\FormResponse;
use Packages\FormBuilder\Events\FormSubmitted;
use Packages\FormBuilder\Events\FormSubmissionFailed;

it('dispatches FormSubmitted and persists submission on success', function () {
    Event::fake();

    $form = Form::factory()->create([
        'key' => 'event-success-form',
        'title' => 'Event Success Form',
        'owner_scope' => 'global',
    ]);

    $formVersion = FormVersion::factory()->create([
        'form_id' => $form->id,
        'semver' => '1.0.0',
        'schema_json' => ['type' => 'object', 'properties' => (object) []],
    ]);

    // Provide a validator that always succeeds
    $validator = new class () implements SchemaValidatorInterface {
        public function validate(array|object $data, array|object $schema): ValidationResultData
        {
            return ValidationResultData::success();
        }
    };

    $this->app->instance(SchemaValidatorInterface::class, $validator);

    $forms = app(FormsManagerInterface::class);

    $result = $forms->submit(['field' => 'value'], $form->key, $formVersion->id, [
        'account_id' => Str::uuid7(),
        'subject_type' => 'user',
        'subject_id' => Str::uuid7(),
    ]);

    expect($result->ok)->toBeTrue();

    expect(FormResponse::query()->count())->toBe(1);

    Event::assertDispatched(FormSubmitted::class, function ($event) use ($result) {
        return is_array($event->data) && ($event->data['submission_id'] ?? null) === $result->submission_id;
    });
});

it('dispatches FormSubmissionFailed and does not persist on validation failure', function () {
    Event::fake();

    $form = Form::factory()->create([
        'key' => 'event-fail-form',
        'title' => 'Event Fail Form',
        'owner_scope' => 'global',
    ]);

    $formVersion = FormVersion::factory()->create([
        'form_id' => $form->id,
        'semver' => '1.0.0',
        'schema_json' => ['type' => 'object', 'properties' => (object) []],
    ]);

    // Provide a validator that fails with a validation error
    $validator = new class () implements SchemaValidatorInterface {
        public function validate(array|object $data, array|object $schema): ValidationResultData
        {
            $err = new ValidationErrorData(path: '#', code: 'test_error', message: 'Test validation failed');
            return ValidationResultData::failure([$err]);
        }
    };

    $this->app->instance(SchemaValidatorInterface::class, $validator);

    $forms = app(FormsManagerInterface::class);

    $result = $forms->submit(['bad' => 'value'], $form->key, $formVersion->id, [
        'account_id' => 'c9dd24f5-e1d0-4a32-a982-eca391f521bb',
    ]);

    expect($result->ok)->toBeFalse();

    expect(FormResponse::query()->count())->toBe(0);

    Event::assertDispatched(FormSubmissionFailed::class, function ($event) {
        return is_array($event->data) && is_array($event->data['errors'] ?? []) && count($event->data['errors']) > 0;
    });
});

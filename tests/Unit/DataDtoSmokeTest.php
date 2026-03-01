<?php

declare(strict_types=1);

use Packages\FormBuilder\Data\FormData;
use Packages\FormBuilder\Data\FormDraftData;
use Packages\FormBuilder\Data\FormVersionData;
use Packages\FormBuilder\Data\FormVariantData;
use Packages\FormBuilder\Data\FormAccessPeriodData;
use Packages\FormBuilder\Data\FormFragmentData;
use Packages\FormBuilder\Data\PublishingCommandData;
use Packages\FormBuilder\Data\PublishingResultData;
use Packages\FormBuilder\Data\ValidationErrorData;
use Packages\FormBuilder\Data\StepDescriptorData;
use Packages\FormBuilder\Data\SubmissionResultData;

test('FormData can be instantiated and contains expected properties', function () {
    $now = new DateTimeImmutable();
    $dto = new FormData(
        id: 'f-1',
        key: 'contact-form',
        title: 'Contact',
        owner_scope: 'global',
        account_id: null,
        tenant_visible: true,
        parent_form_id: null,
        status: 'active',
        created_at: $now,
        updated_at: $now,
    );

    expect($dto)->toBeInstanceOf(FormData::class);
    expect($dto->id)->toBe('f-1');
    expect($dto->key)->toBe('contact-form');
})->group('data');

test('FormDraftData can be instantiated and contains schema and ui', function () {
    $now = new DateTimeImmutable();
    $dto = new FormDraftData(
        id: 'd-1',
        form_id: 'f-1',
        schema_json: ['type' => 'object'],
        ui_schema_json: ['ui' => 'config'],
        created_by: 'user-1',
        created_at: $now,
        updated_at: $now,
    );

    expect($dto)->toBeInstanceOf(FormDraftData::class);
    expect($dto->schema_json)->toBeArray();
    expect($dto->ui_schema_json)->toBeArray();
})->group('data');

test('FormVersionData can be instantiated and contains version_number', function () {
    $now = new DateTimeImmutable();
    $dto = new FormVersionData(
        id: 'v-1',
        form_id: 'f-1',
        version_number: 1,
        schema_json: ['type' => 'object'],
        ui_schema_json: null,
        published_at: $now,
        created_at: $now,
        updated_at: $now,
    );

    expect($dto)->toBeInstanceOf(FormVersionData::class);
    expect($dto->version_number)->toBe(1);
})->group('data');

test('FormVariantData can be instantiated and contains key', function () {
    $now = new DateTimeImmutable();
    $dto = new FormVariantData(
        id: 'variant-1',
        form_id: 'f-1',
        key: 'A',
        title: 'Variant A',
        ui_schema_json: ['variant' => true],
        created_at: $now,
        updated_at: $now,
    );

    expect($dto)->toBeInstanceOf(FormVariantData::class);
    expect($dto->key)->toBe('A');
})->group('data');

test('FormAccessPeriodData can be instantiated and enabled defaults to true', function () {
    $now = new DateTimeImmutable();
    $dto = new FormAccessPeriodData(
        id: 'ap-1',
        form_id: 'f-1',
        starts_at: $now,
        ends_at: null,
        enabled: true,
        created_at: $now,
        updated_at: $now,
    );

    expect($dto)->toBeInstanceOf(FormAccessPeriodData::class);
    expect($dto->enabled)->toBe(true);
})->group('data');

test('FormFragmentData can be instantiated and contains schema_fragment_json', function () {
    $now = new DateTimeImmutable();
    $dto = new FormFragmentData(
        id: 'frag-1',
        key: 'address',
        title: 'Address Fragment',
        owner_scope: 'global',
        account_id: null,
        schema_fragment_json: ['type' => 'object'],
        ui_fragment_json: null,
        created_at: $now,
        updated_at: $now,
    );

    expect($dto)->toBeInstanceOf(FormFragmentData::class);
    expect($dto->schema_fragment_json)->toBeArray();
})->group('data');

test('PublishingCommandData can be instantiated', function () {
    $dto = new PublishingCommandData(
        form_id: 'f-1',
        form_draft_id: 'd-1',
        initiator_user_id: 'user-1',
        force: false,
    );

    expect($dto)->toBeInstanceOf(PublishingCommandData::class);
    expect($dto->form_id)->toBe('f-1');
})->group('data');

test('ValidationErrorData and PublishingResultData integrate and errors are accessible', function () {
    $err = new ValidationErrorData(
        path: '#/properties/email',
        code: 'unique_in_period',
        message: 'Email already used in the last 90 days.'
    );

    $res = new PublishingResultData(
        ok: false,
        published_form_version_id: null,
        errors: [$err],
    );

    expect($res)->toBeInstanceOf(PublishingResultData::class);
    expect($res->errors)->toBeArray();
    expect($res->errors[0])->toBeInstanceOf(ValidationErrorData::class);
    expect($res->errors[0]->path)->toBe('#/properties/email');
})->group('data');

test('StepDescriptorData can be instantiated and contains index and title', function () {
    $dto = new StepDescriptorData(
        id: 'step-1',
        title: 'Step One',
        index: 0,
        ui_schema: ['ui' => 'val'],
        schema: ['type' => 'object'],
    );

    expect($dto)->toBeInstanceOf(StepDescriptorData::class);
    expect($dto->index)->toBe(0);
    expect($dto->title)->toBe('Step One');
})->group('data');

test('SubmissionResultData can be instantiated and contains expected properties', function () {
    $dto = new SubmissionResultData(
        ok: true,
        submission_id: 's-1',
        replayed: false,
        errors: [],
    );

    expect($dto)->toBeInstanceOf(SubmissionResultData::class);
    expect($dto->ok)->toBeTrue();
    expect($dto->submission_id)->toBe('s-1');
    expect($dto->replayed)->toBeFalse();
})->group('data');

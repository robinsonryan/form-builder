<?php

declare(strict_types=1);

uses(Packages\FormBuilder\Tests\TestCase::class);

use Packages\FormBuilder\Data\SubmissionResultData;
use Packages\FormBuilder\Data\ValidationErrorData;

it('merges spatie data config and SubmissionResultData->toArray/toJson do not throw', function () {
    $dataConfig = config('data');
    //dd($dataConfig);
    // Basic sanity: config('data') should be present and an array (merged from package defaults).
    expect($dataConfig)->not->toBeNull();
    expect(is_array($dataConfig))->toBeTrue();
    expect(array_key_exists('max_transformation_depth', $dataConfig))->toEqual(5);

    // Construct a SubmissionResultData containing a ValidationErrorData and ensure
    // calling Spatie's toArray()/toJson() does not throw with the merged config.
    $dto = new SubmissionResultData(ok: false, submission_id: null, replayed: false, errors: [
        new ValidationErrorData('#', 'test_error', 'Test message'),
    ]);

    // If these calls throw, the test will fail. We also assert their return types.
    $arr = $dto->toArray();
    expect(is_array($arr))->toBeTrue();

    $json = $dto->toJson();
    expect(is_string($json))->toBeTrue();
});

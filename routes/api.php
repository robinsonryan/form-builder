<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Packages\FormBuilder\Http\Controllers\FormLookupController;
use Packages\FormBuilder\Http\Controllers\DraftController;
use Packages\FormBuilder\Http\Controllers\ValidationController;
use Packages\FormBuilder\Http\Controllers\SubmissionController;
use Packages\FormBuilder\Http\Controllers\PublishedFormController;
use Packages\FormBuilder\Http\Controllers\FilePresignController;

Route::prefix('form-builder')->name('formbuilder.')->group(function () {
    Route::get('/forms/{key}', [FormLookupController::class, 'lookup'])
        ->name('forms.lookup');

    Route::get('/forms/{key}/versions/{version}', [PublishedFormController::class, 'show'])
        ->name('forms.published');

    Route::post('/forms/{key}/drafts', [DraftController::class, 'store'])
        ->name('drafts.store');
    Route::get('/forms/{key}/drafts/{id}', [DraftController::class, 'show'])
        ->name('drafts.show');
    Route::patch('/forms/{key}/drafts/{id}/steps/{stepKey}', [DraftController::class, 'patchStep'])
        ->name('drafts.patchStep');

    Route::post('/forms/{key}/versions/{version}/validate', [ValidationController::class, 'validate'])
        ->name('forms.validate');

    // File presign: returns an uploadUrl and a server-side key reference for the uploaded file.
    Route::post('/file-presign', [FilePresignController::class, 'presign'])
        ->name('file.presign');

    $submit = Route::post('/forms/{key}/versions/{version}/submit', [SubmissionController::class, 'submit'])
        ->name('forms.submit');

    if (config('forms.idempotency_required')) {
        $submit->middleware('idempotency');
    }
});

<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Packages\FormBuilder\Contracts\FormsManagerInterface;
use Packages\FormBuilder\Data\ValidationResultData;
use Packages\FormBuilder\Data\ValidationErrorData;
use Packages\FormBuilder\Models\Form;

final class ValidationController
{
    public function __construct(private FormsManagerInterface $forms)
    {
    }

    public function validate(Request $request, string $key, string $version): Response
    {
        $data = $request->input('data', (object)[]);


        $form = Form::where('key', $key)->first();
        if ($form === null) {
            $dto = ValidationResultData::failure([
                new ValidationErrorData('#', 'form_not_found', 'Form not found.')
            ]);

            return response($dto)->setStatusCode(Response::HTTP_NOT_FOUND);
        }

        $versionModel = $form->versions()->find($version);

        if ($versionModel === null) {
            $dto = ValidationResultData::failure([
                new ValidationErrorData('#', 'version_not_found', 'Form version not found.')
            ]);

            return response($dto)->setStatusCode(Response::HTTP_NOT_FOUND);
        }
        //dd($versionModel->schema_json);
        // Delegate to FormsManager which returns a ValidationResultData DTO
        return response($this->forms->validate((object)$data, (object)$versionModel->schema_json))->setStatusCode(Response::HTTP_OK);

    }
}

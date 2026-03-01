<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Packages\FormBuilder\Contracts\FormsManagerInterface;
use Packages\FormBuilder\Data\SubmissionResultData;
use Throwable;

final class SubmissionController
{
    public function __construct(private FormsManagerInterface $forms)
    {
    }

    public function submit(Request $request, string $key, string $version): SubmissionResultData
    {

            $answers = $request->input('answers', []);
            $options = $request->input('options', []);
            $accountId = $request->header('X-Account-Id') ?? null;
            $options['account_id'] = $accountId;

            return $this->forms->submit($answers, $key, $version, $options);

     }
}

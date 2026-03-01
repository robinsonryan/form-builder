<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Packages\FormBuilder\Models\Form;
use Packages\FormBuilder\Models\FormDraft;
use Packages\FormBuilder\Models\FormDraftSubmission;

final class DraftController
{
    public function store(Request $request, string $key): JsonResponse
    {
        // Attempt to resolve form id by key; if not present create a draft referencing null form.
        $form = Form::where('key', $key)->first();

        $draft = FormDraft::create([
            'id' => Str::uuid7()->toString(),
            'form_id' => $form?->id,
            'schema_json' => $request->input('schema', []),
            'ui_schema_json' => $request->input('ui', []),
        ]);

        // If answers are provided, persist them as a FormDraftSubmission (answers belong to submissions)
        $submission = null;
        if ($request->has('answers')) {
            // Determine a sensible form_version_id: prefer explicit payload, otherwise use the latest form version
            $formVersionId = $request->input('form_version_id') ?? $form?->versions()->latest()->first()?->id ?? null;

            $submission = FormDraftSubmission::create([
                'id' => Str::uuid7()->toString(),
                'form_version_id' => $formVersionId,
                'account_id' => $form?->account_id ?? null,
                'responses_json' => $request->input('answers', []),
            ]);
        }

        $data = [
            'id' => $draft->id,
            'form_id' => $draft->form_id,
            'schema_json' => $draft->schema_json,
            'ui_schema_json' => $draft->ui_schema_json,
            'responses_json' => $submission ? $submission->responses_json : [],
            'draft_submission_id' => $submission?->id ?? null,
        ];

        return response()->json([
            'ok' => true,
            'draft' => $data,
        ], 201);
    }

    public function show(Request $request, string $key, string $id): JsonResponse
    {
        $draft = FormDraft::with('form')->find($id);

        if (!$draft) {
            return response()->json([
                'ok' => false,
                'errors' => [
                    [
                        'path' => null,
                        'code' => 'not_found',
                        'message' => 'Draft not found.',
                    ],
                ],
            ], 404);
        }

        // Load the latest submission for this draft (answers are stored on submissions)
        $submission = FormDraftSubmission::where('form_draft_id', $draft->id)->latest('created_at')->first();

        $data = [
            'id' => $draft->id,
            'form_id' => $draft->form->id,
            'schema_json' => $draft->schema_json,
            'ui_schema_json' => $draft->ui_schema_json,
            'responses_json' => $submission ? $submission->responses_json : [],
            'draft_submission_id' => $submission?->id ?? null,
            'created_by' => $draft->created_by,
        ];

        return response()->json(['ok' => true, 'draft' => $data], 200);
    }

    public function patchStep(Request $request, string $key, string $id, string $stepKey): JsonResponse
    {
        $draft = FormDraft::find($id);

        if (!$draft) {
            return response()->json([
                'ok' => false,
                'errors' => [
                    [
                        'path' => null,
                        'code' => 'not_found',
                        'message' => 'Draft not found.',
                    ],
                ],
            ], 404);
        }

        $patch = $request->input('patch', []);
        $draft->update([
            'schema_json' => array_merge($draft->schema_json ?? [], $patch),
        ]);

        // Load latest submission for this draft (answers are on submissions)
        $submission = FormDraftSubmission::where('form_draft_id', $draft->id)->latest('created_at')->first();

        $data = [
            'id' => $draft->id,
            'form_id' => $draft->form->form_id,
            'schema_json' => $draft->schema_json,
            'ui_schema_json' => $draft->ui_schema_json,
            'responses_json' => $submission ? $submission->responses_json : [],
            'draft_submission_id' => $submission?->id ?? null,
            'created_by' => $draft->created_by,
        ];

        return response()->json(['ok' => true, 'draft' => $data], 200);
    }
}

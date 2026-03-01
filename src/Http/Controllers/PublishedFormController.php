<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Packages\FormBuilder\Models\Form;
use Packages\FormBuilder\Models\FormVersion;
use Packages\FormBuilder\Services\Publishing\StepMapper;

final class PublishedFormController
{
    /**
     * Return an explicit JSON payload for a published form version.
     *
     * GET /form-builder/forms/{key}/versions/{version}
     *
     * The {version} path segment may be a uild id.
     */
    public function show(Request $request, string $key, string $version): JsonResponse
    {
        $form = Form::where('key', $key)->first();

        if (! $form) {
            return response()->json([
                'ok' => false,
                'message' => 'Form not found',
            ], 404);
        }

        $formVersion = FormVersion::where('form_id', $form->id)
            ->where('id', $version)
            ->first();

        if (! $formVersion || $formVersion->published_at === null) {
            return response()->json([
                'ok' => false,
                'message' => 'Published version not found',
            ], 404);
        }

        // Derive ui_step_maps using StepMapper if not present on the stored version.
        $uiStepMaps = $formVersion->ui_step_maps;
        if (! is_array($uiStepMaps)) {
            /** @var StepMapper $stepMapper */
            $stepMapper = app(StepMapper::class);
            $uiStepMaps = $stepMapper->derive($formVersion->ui_schema_json ?? []);
        }

        $versionPayload = [
            'id' => $formVersion->id,
            'content_hash' => $formVersion->content_hash ?? null,
            'schema' => $formVersion->schema_json ?? [],
            'ui' => $formVersion->ui_schema_json ?? null,
            'ui_step_maps' => $uiStepMaps ?? [],
            'published_at' => $formVersion->published_at?->toIso8601String(),
        ];

        $formPayload = [
            'id' => $form->id,
            'key' => $form->key,
            'title' => $form->title,
            'owner_scope' => $form->owner_scope ?? null,
            'account_id' => $form->account_id ?? null,
            'tenant_visible' => (bool) ($form->tenant_visible ?? false),
            'status' => $form->status ?? null,
        ];

        $response = response()->json([
            'ok' => true,
            'form' => $formPayload,
            'version' => $versionPayload,
        ], 200);

        // Attach caching headers appropriate for immutable published versions.
        // Add ETag only when a content_hash is available.
        if (! empty($formVersion->content_hash)) {
            $response->header('ETag', '"' . $formVersion->content_hash . '"');
        }
        $response->header('Cache-Control', 'public, max-age=31536000');

        return $response;
    }
}

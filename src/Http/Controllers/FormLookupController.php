<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Packages\FormBuilder\Models\Form;

final class FormLookupController
{
    public function lookup(Request $request, string $key): JsonResponse
    {
        $form = Form::where('key', $key)->first();

        if (!$form) {
            return response()->json([
                'ok' => false,
                'errors' => [
                    [
                        'path' => null,
                        'code' => 'not_found',
                        'message' => 'Form not found.',
                    ],
                ],
            ], 404);
        }

        // Return a minimal scalar representation to avoid model / Carbon serialization issues
        $data = [
            'id' => $form->id,
            'key' => $form->key,
            'title' => $form->title,
            'owner_scope' => $form->owner_scope,
            'account_id' => $form->account_id,
            'tenant_visible' => (bool) $form->tenant_visible,
            'parent_form_id' => $form->parent_form_id,
            'status' => $form->status,
        ];

        return response()->json([
            'ok' => true,
            'form' => $data,
        ], 200);
    }
}

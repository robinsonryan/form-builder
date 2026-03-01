<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

final class FilePresignController
{
    /**
     * presign(Request): JsonResponse
     *
     * Accepts:
     * - filename: string
     * - content_type: string
     *
     * Returns:
     * {
     *   ok: true,
     *   uploadUrl: string,
     *   key: string,
     *   filename: string,
     *   content_type: string
     * }
     *
     * Note: This implementation returns a fake presigned URL suitable for local/dev tests.
     * Replace with S3/GCS presign logic in production (Storage::disk('s3')->temporaryUrl(...) or AWS SDK).
     */
    public function presign(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'filename' => ['required', 'string'],
            'content_type' => ['required', 'string'],
        ]);

        $filename = $validated['filename'];
        $contentType = $validated['content_type'];

        // Generate a stable-ish key for the uploaded object.
        $key = 'uploads/' . Str::uuid7()->toString() . '/' . basename($filename);

        // For tests and local dev, return a fake upload URL that tests can stub against.
        // In production replace with a real S3 presigned PUT URL.
        $uploadUrl = config('app.url') . '/_local_fake_upload_endpoint/' . $key;

        return response()->json([
            'ok' => true,
            'uploadUrl' => $uploadUrl,
            'key' => $key,
            'filename' => $filename,
            'content_type' => $contentType,
        ]);
    }
}

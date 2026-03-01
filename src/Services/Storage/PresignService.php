<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Services\Storage;

use Illuminate\Support\Str;

/**
 * Simple PresignService for local/dev usage.
 *
 * In production, replace or extend this service to use Storage disks and AWS SDK to
 * generate real presigned URLs.
 */
final class PresignService
{
    /**
     * Generate a fake presign response for the provided filename and content type.
     *
     * @param string $filename
     * @param string $contentType
     * @return array{uploadUrl:string, key:string, filename:string, content_type:string}
     */
    public function presign(string $filename, string $contentType): array
    {
        $key = 'uploads/' . Str::uuid7()->toString() . '/' . basename($filename);
        $uploadUrl = config('app.url') . '/_local_fake_upload_endpoint/' . $key;

        return [
            'uploadUrl' => $uploadUrl,
            'key' => $key,
            'filename' => $filename,
            'content_type' => $contentType,
        ];
    }
}

/*
RECOMMENDED_ADDITIONS:
- packages/form-builder/resources/js/form-renderer/adapters/primevue/__tests__/FormRenderer.integration.spec.js
- packages/form-builder/resources/js/form-renderer/core/__tests__/submit.spec.js

Notes:
- Consider adding a production implementation that uses Storage::disk('s3') to generate presigned URLs.
- The PresignService can be bound in a service provider for easier testing and swapping.
*/

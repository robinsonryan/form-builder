<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Packages\FormBuilder\Models\IdempotencyKey;

/**
 * Middleware that enforces and records Idempotency-Key for POST final submissions.
 *
 * Behaviour:
 *  - If config('forms.idempotency_required') is false the middleware is a no-op.
 *  - Requires header "Idempotency-Key" (or "X-Idempotency-Key") and returns 400 if missing.
 *  - If a record for the same key (scoped by account_id header when present) exists the stored
 *    response is returned directly.
 *  - Otherwise the request is forwarded, the response captured and stored for future replay.
 */
final class EnsureIdempotencyKey
{
    public function handle(Request $request, Closure $next)
    {
        if (!config('forms.idempotency_required')) {
            return $next($request);
        }

        // Only enforce for POST (final submit endpoints).
        if (!$request->isMethod('post')) {
            return $next($request);
        }

        $key = $request->header('Idempotency-Key') ?? $request->header('X-Idempotency-Key');

        if (empty($key)) {
            return response()->json([
                'ok' => false,
                'errors' => [
                    [
                        'path' => null,
                        'code' => 'missing_idempotency_key',
                        'message' => 'Missing Idempotency-Key header.',
                    ],
                ],
            ], 400);
        }

        // Allow scoping by account id when provided (hosts should set this header if multi-tenant).
        $accountId = $request->header('X-Account-Id') ?? null;

        $existing = IdempotencyKey::where('key', $key)
            ->when($accountId === null, fn($q) => $q->whereNull('account_id'), fn($q) => $q->where('account_id', $accountId))
            ->first();

        if ($existing) {
            $body = $existing->response_body;
            $status = $existing->response_status ?? 200;
            $headers = $existing->response_headers ?? [];

            $response = response()->json($body, $status);
            foreach ($headers as $k => $v) {
                // response headers in the DB are stored as arrays (header => [values])
                if (is_array($v)) {
                    foreach ($v as $vv) {
                        $response->headers->set($k, $vv);
                    }
                } else {
                    $response->headers->set($k, $v);
                }
            }

            return $response;
        }

        // Proceed and capture response for storage.
        $response = $next($request);

        $content = $response->getContent();
        $decoded = json_decode($content, true);
        $storeBody = $decoded === null ? $content : $decoded;

        IdempotencyKey::create([
            'key' => $key,
            'account_id' => $accountId,
            'request_hash' => sha1($request->getContent() ?? ''),
            'response_status' => $response->getStatusCode(),
            'response_body' => $storeBody,
            'response_headers' => $response->headers->all(),
        ]);

        return $response;
    }
}

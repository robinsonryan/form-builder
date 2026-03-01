<?php

declare(strict_types=1);

namespace Packages\FormBuilder\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent model for stored idempotency responses.
 */
final class IdempotencyKey extends Model
{
    use HasUuids;

    protected $table = 'form_idempotency_keys';

    protected $fillable = [
        'key',
        'account_id',
        'request_hash',
        'response_status',
        'response_body',
        'response_headers',
    ];

    protected $casts = [
        'response_body' => 'array',
        'response_headers' => 'array',
    ];
}

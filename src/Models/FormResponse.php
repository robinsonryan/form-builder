<?php
declare(strict_types=1);

namespace Packages\FormBuilder\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FormResponse extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'account_id',
        'form_id',
        'form_version_id',
        'form_variant_id',
        'subject_type',
        'subject_id',
        'responses_json',
        'submitted_by',
        'submitted_at',
        'status',
    ];

    protected $casts = [
        'responses_json' => 'array',
        'submitted_at' => 'datetime',
    ];
}

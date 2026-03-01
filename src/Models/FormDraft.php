<?php
declare(strict_types=1);

namespace Packages\FormBuilder\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Packages\FormBuilder\Models\Concerns\ScopesByAccount;

class FormDraft extends Model
{
    use HasFactory, HasUuids, ScopesByAccount;

    protected $table = 'form_drafts';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'form_id',
        'account_id',
        'schema_json',
        'ui_schema_json',
        'slots_json',
        'notes',
        'responses_json',
        'created_by',
    ];

    protected $casts = [
        'schema_json' => 'array',
        'ui_schema_json' => 'array',
        'responses_json' => 'array',
        'slots_json' => 'array',
    ];
}

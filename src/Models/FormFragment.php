<?php
declare(strict_types=1);

namespace Packages\FormBuilder\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Packages\FormBuilder\Models\Concerns\ScopesByAccount;

class FormFragment extends Model
{
    use HasFactory, HasUuids, ScopesByAccount;

    protected $table = 'form_fragments';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'key',
        'title',
        'owner_scope',
        'tenant_visible',
        'account_id',
        'schema_fragment_json',
        'ui_fragment_json',
        'params_schema_json',
        'slots_json',
        'status',
    ];

    protected $casts = [
        'schema_fragment_json' => 'array',
        'ui_fragment_json' => 'array',
        'params_schema_json' => 'array',
        'slots_json' => 'array',
        'tenant_visible' => 'boolean',
    ];
}

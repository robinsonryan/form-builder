<?php
declare(strict_types=1);

namespace Packages\FormBuilder\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FormFragmentVersion extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'form_fragment_versions';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'account_id',
        'fragment_id',
        'semver',
        'schema_fragment_json',
        'ui_fragment_json',
        'params_schema_json',
        'slots_json',
        'content_hash',
        'published_at',
    ];

    protected $casts = [
        'schema_fragment_json' => 'array',
        'ui_fragment_json' => 'array',
        'params_schema_json' => 'array',
        'slots_json' => 'array',
        'published_at' => 'datetime',
    ];
}

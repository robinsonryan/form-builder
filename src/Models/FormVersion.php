<?php
declare(strict_types=1);

namespace Packages\FormBuilder\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Packages\FormBuilder\Models\Concerns\ScopesByAccount;

class FormVersion extends Model
{
    use ScopesByAccount, HasFactory, HasUuids;

    protected $table = 'form_versions';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'form_id',
        'account_id',
        'semver',
        'schema_json',
        'ui_schema_json',
        'slots_json',
        'ui_step_maps',
        'fragment_version_ids',
        'content_hash',
        'published_by',
        'published_at',
    ];

    protected $casts = [
        'schema_json' => 'object',
        'ui_schema_json' => 'object',
        'slots_json' => 'array',
        'ui_step_maps' => 'array',
        'fragment_version_ids' => 'array',
        'published_at' => 'datetime',
    ];

    /**
     * The form this version belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function form(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Form::class);
    }
}

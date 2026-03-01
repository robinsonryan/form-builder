<?php
declare(strict_types=1);

namespace Packages\FormBuilder\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Packages\FormBuilder\Models\Concerns\ScopesByAccount;

class Form extends Model
{
    use HasFactory, ScopesByAccount, HasUuids;

    protected $table = 'forms';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'key',
        'title',
        'owner_scope',
        'account_id',
        'tenant_visible',
        'parent_form_id',
        'status',
    ];

    protected $casts = [
        'tenant_visible' => 'boolean',
    ];

    public function versions(): HasMany
    {
        return $this->hasMany(FormVersion::class);
    }
}

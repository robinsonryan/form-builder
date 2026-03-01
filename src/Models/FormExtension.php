<?php
declare(strict_types=1);

namespace Packages\FormBuilder\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Packages\FormBuilder\Models\Concerns\ScopesByAccount;

class FormExtension extends Model
{
    use HasFactory, HasUuids, ScopesByAccount;

    protected $table = 'form_extensions';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'base_form_id',
        'account_id',
        'name',
        'extension_schema_json',
        'extension_ui_json',
    ];

    protected $casts = [
        'extension_schema_json' => 'array',
        'extension_ui_json' => 'array',
    ];
}

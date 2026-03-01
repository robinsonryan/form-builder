<?php
declare(strict_types=1);

namespace Packages\FormBuilder\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FormPublication extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'form_publications';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'base_form_id',
        'account_id',
        'form_version_id',
        'extension_ids',
    ];

    protected $casts = [
        'extension_ids' => 'array',
    ];
}

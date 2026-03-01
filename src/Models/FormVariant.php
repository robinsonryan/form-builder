<?php
declare(strict_types=1);

namespace Packages\FormBuilder\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FormVariant extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'form_variants';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'account_id',
        'form_version_id',
        'key',
        'ui_schema_key',
        'traffic_allocation',
    ];
}

<?php
declare(strict_types=1);

namespace Packages\FormBuilder\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FormDraftSubmission extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'form_drafts_submissions';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'account_id',
        'applicant_id',
        'created_by',
        'form_version_id',
        'form_draft_id',
        'responses_json',
        'step_progress',
        'etag',

    ];

    protected $casts = [
        'responses_json' => 'array',
        'step_progress' => 'array',
    ];
}

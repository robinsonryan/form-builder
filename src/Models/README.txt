Create Form, FormDraft, FormVersion, FormVariant, FormAccessPeriod, FormExtension, FormPublication, FormFragment, FormFragmentVersion, FormResponse, FormDraftSubmission models with json casts.

Conventions:
- Namespace root: Vendor\FormBuilder\Models
- UUID primary keys: protected $keyType = 'string'; public $incrementing = false;
- Jsonb columns are cast to array in $casts.
- Shared tenant scoping helper available in Concerns\ScopesByAccount.

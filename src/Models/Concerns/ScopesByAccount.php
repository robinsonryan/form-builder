<?php
declare(strict_types=1);

namespace Packages\FormBuilder\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait ScopesByAccount
{
    /**
     * Scope a query to the given account_id.
     *
     * @param  Builder  $query
     * @param  string|null  $accountId
     * @return Builder
     */
    public function scopeByAccount(Builder $query, ?string $accountId): Builder
    {
        if ($accountId === null) {
            return $query->whereNull('account_id');
        }

        return $query->where('account_id', $accountId);
    }
}

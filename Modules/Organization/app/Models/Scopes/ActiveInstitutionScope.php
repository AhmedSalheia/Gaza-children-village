<?php

declare(strict_types=1);

namespace Modules\Organization\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Restricts all default Institution queries to active records only.
 *
 * Deactivated institutions are preserved for historical reference and can
 * still be retrieved by calling `withoutGlobalScopes()` on the query.
 */
class ActiveInstitutionScope implements Scope
{
    /**
     * @param  Builder<Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('is_active', true);
    }
}

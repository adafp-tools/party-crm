<?php

namespace App\Scopes;

use App\Services\UnitScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global Eloquent scope applied to every Member query.
 *
 * Uses UnitScope to filter members to only those whose org_unit.path
 * falls within the authenticated user's accessible path prefixes.
 */
class UnitScopedScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $scope = app(UnitScope::class);
        $prefixes = $scope->accessiblePathPrefixes();

        if ($prefixes === null) {
            return;
        }

        if (empty($prefixes)) {
            $builder->whereRaw('0 = 1');

            return;
        }

        $builder->whereHas('orgUnit', function (Builder $q) use ($prefixes): void {
            $q->where(function (Builder $q) use ($prefixes): void {
                foreach ($prefixes as $prefix) {
                    $q->orWhere('path', 'like', $prefix.'%');
                }
            });
        });
    }
}

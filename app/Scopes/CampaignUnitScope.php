<?php

namespace App\Scopes;

use App\Services\UnitScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Campaign-specific unit scope.
 *
 * Differs from UnitScopedScope: campaigns with org_unit_id = null are
 * organisation-wide and visible to all scoped users (not just unrestricted).
 */
class CampaignUnitScope implements Scope
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

        $builder->where(function (Builder $q) use ($prefixes): void {
            // Organisation-wide campaigns (no unit filter) are visible to all.
            $q->whereNull('org_unit_id');

            // Campaigns targeting a specific unit within the user's scope.
            $q->orWhereHas('orgUnit', function (Builder $q) use ($prefixes): void {
                $q->where(function (Builder $q) use ($prefixes): void {
                    foreach ($prefixes as $prefix) {
                        $q->orWhere('path', 'like', $prefix.'%');
                    }
                });
            });
        });
    }
}

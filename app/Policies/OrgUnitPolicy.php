<?php

namespace App\Policies;

use App\Models\OrgUnit;
use App\Models\User;
use App\Services\UnitScope;

class OrgUnitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            'national_admin',
            'national_leadership',
            'regional_coordinator',
        ]);
    }

    public function view(User $user, OrgUnit $orgUnit): bool
    {
        return app(UnitScope::class, ['user' => $user])->canAccessPath($orgUnit->path);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['national_admin', 'national_leadership']);
    }

    public function update(User $user, OrgUnit $orgUnit): bool
    {
        return $user->hasAnyRole(['national_admin', 'national_leadership']);
    }

    public function delete(User $user, OrgUnit $orgUnit): bool
    {
        return $user->hasRole('national_admin');
    }
}

<?php

namespace App\Policies;

use App\Models\Member;
use App\Models\User;
use App\Services\UnitScope;

class MemberPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('platform_support')) {
            return false;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            'national_admin',
            'national_leadership',
            'treasurer',
            'communications_officer',
            'regional_coordinator',
            'branch_secretary',
        ]);
    }

    public function view(User $user, Member $member): bool
    {
        if (! $member->orgUnit) {
            return false;
        }

        return app(UnitScope::class, ['user' => $user])->canAccessPath($member->orgUnit->path);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            'national_admin',
            'national_leadership',
            'regional_coordinator',
            'branch_secretary',
        ]);
    }

    public function update(User $user, Member $member): bool
    {
        return $this->view($user, $member);
    }

    public function delete(User $user, Member $member): bool
    {
        return $user->hasAnyRole(['national_admin', 'national_leadership']);
    }

    public function export(User $user): bool
    {
        return $user->hasAnyRole([
            'national_admin',
            'national_leadership',
            'treasurer',
            'regional_coordinator',
        ]);
    }
}

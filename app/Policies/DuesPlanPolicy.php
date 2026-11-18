<?php

namespace App\Policies;

use App\Models\DuesPlan;
use App\Models\User;

class DuesPlanPolicy
{
    public function before(User $user): ?bool
    {
        if ($user->hasRole('platform_support')) {
            return false;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['national_admin', 'national_leadership', 'treasurer', 'regional_coordinator']);
    }

    public function view(User $user, DuesPlan $plan): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('national_admin');
    }

    public function update(User $user, DuesPlan $plan): bool
    {
        return $user->hasRole('national_admin');
    }

    public function delete(User $user, DuesPlan $plan): bool
    {
        return $user->hasRole('national_admin');
    }
}

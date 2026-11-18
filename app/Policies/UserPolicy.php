<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['national_admin', 'national_leadership']);
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasAnyRole(['national_admin', 'national_leadership'])
            || $user->id === $model->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('national_admin');
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasRole('national_admin')
            || $user->id === $model->id;
    }

    public function delete(User $user, User $model): bool
    {
        return $user->hasRole('national_admin')
            && $user->id !== $model->id;
    }

    public function forceEnrollTwoFactor(User $user, User $model): bool
    {
        return $user->hasRole('national_admin');
    }
}

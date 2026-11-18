<?php

namespace App\Policies;

use App\Models\DuesPayment;
use App\Models\User;

class DuesPaymentPolicy
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
        return $user->hasAnyRole(['national_admin', 'treasurer', 'regional_coordinator', 'branch_secretary']);
    }

    public function view(User $user, DuesPayment $payment): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['national_admin', 'treasurer', 'regional_coordinator', 'branch_secretary']);
    }

    public function update(User $user, DuesPayment $payment): bool
    {
        // Confirm/reject is treasurer+ only
        return $user->hasAnyRole(['national_admin', 'treasurer', 'regional_coordinator']);
    }

    public function delete(User $user, DuesPayment $payment): bool
    {
        return $user->hasRole('national_admin');
    }
}

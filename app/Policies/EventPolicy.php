<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;
use App\Services\UnitScope;

class EventPolicy
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
        return $user->hasAnyRole([
            'national_admin', 'national_leadership', 'treasurer',
            'communications_officer', 'regional_coordinator', 'branch_secretary',
        ]);
    }

    public function view(User $user, Event $event): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        return app(UnitScope::class)->canAccessPath($event->orgUnit?->path ?? '');
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            'national_admin', 'communications_officer',
            'regional_coordinator', 'branch_secretary',
        ]);
    }

    public function update(User $user, Event $event): bool
    {
        if (! $this->create($user)) {
            return false;
        }

        return app(UnitScope::class)->canAccessPath($event->orgUnit?->path ?? '');
    }

    public function delete(User $user, Event $event): bool
    {
        return $this->update($user, $event);
    }

    public function manageAttendance(User $user, Event $event): bool
    {
        return $this->update($user, $event);
    }
}

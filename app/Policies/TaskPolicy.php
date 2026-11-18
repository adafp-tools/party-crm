<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use App\Services\UnitScope;

class TaskPolicy
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

    public function view(User $user, Task $task): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        $scope = app(UnitScope::class);

        // User can see tasks they are assigned to, regardless of scope
        if ($task->assignee_id === $user->id) {
            return true;
        }

        return $scope->canAccessPath($task->orgUnit?->path ?? '');
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            'national_admin', 'regional_coordinator', 'branch_secretary',
        ]);
    }

    public function update(User $user, Task $task): bool
    {
        if ($user->hasRole('national_admin')) {
            return true;
        }

        // Assignee can update status of their own task
        if ($task->assignee_id === $user->id) {
            return true;
        }

        if (! $this->create($user)) {
            return false;
        }

        return app(UnitScope::class)->canAccessPath($task->orgUnit?->path ?? '');
    }

    public function delete(User $user, Task $task): bool
    {
        if ($user->hasRole('national_admin')) {
            return true;
        }

        if (! $user->hasAnyRole(['regional_coordinator', 'branch_secretary'])) {
            return false;
        }

        return app(UnitScope::class)->canAccessPath($task->orgUnit?->path ?? '');
    }
}

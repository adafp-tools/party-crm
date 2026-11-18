<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\User;
use App\Services\UnitScope;

class CampaignPolicy
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
            'national_admin', 'national_leadership',
            'communications_officer', 'regional_coordinator',
        ]);
    }

    public function view(User $user, Campaign $campaign): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        $scope = app(UnitScope::class);

        if ($scope->isUnrestricted()) {
            return true;
        }

        $path = $campaign->orgUnit?->path ?? '/';

        return $scope->canAccessPath($path);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            'national_admin', 'national_leadership',
            'communications_officer', 'regional_coordinator',
        ]);
    }

    public function update(User $user, Campaign $campaign): bool
    {
        if ($campaign->status !== 'draft') {
            return false;
        }

        return $this->create($user) && $this->view($user, $campaign);
    }

    public function send(User $user, Campaign $campaign): bool
    {
        if (! in_array($campaign->status, ['draft'])) {
            return false;
        }

        return $user->hasAnyRole([
            'national_admin', 'national_leadership', 'communications_officer',
        ]) || ($user->hasRole('regional_coordinator') && $this->view($user, $campaign));
    }

    public function delete(User $user, Campaign $campaign): bool
    {
        if ($campaign->status === 'sending') {
            return false;
        }

        return $user->hasAnyRole(['national_admin', 'communications_officer'])
            || ($user->hasRole('regional_coordinator') && $this->view($user, $campaign));
    }
}

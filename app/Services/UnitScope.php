<?php

namespace App\Services;

use App\Models\User;

/**
 * Resolves the set of org_unit path prefixes a user may access.
 *
 * Returns null  → unrestricted (national_admin, national_leadership).
 * Returns []    → no access (platform_support, unauthenticated).
 * Returns [...]  → access only where org_unit.path starts with one of these.
 */
class UnitScope
{
    public function __construct(private readonly ?User $user = null) {}

    /** @return list<string>|null */
    public function accessiblePathPrefixes(): ?array
    {
        $user = $this->user ?? auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        if ($user->hasAnyRole(['national_admin', 'national_leadership'])) {
            return null;
        }

        if ($user->hasRole('platform_support')) {
            return [];
        }

        $paths = $user->roleAssignments()
            ->with('orgUnit')
            ->get()
            ->filter(fn ($ra) => $ra->orgUnit !== null)
            ->map(fn ($ra) => $ra->orgUnit->path)
            ->unique()
            ->values()
            ->all();

        return $paths;
    }

    public function isUnrestricted(): bool
    {
        return $this->accessiblePathPrefixes() === null;
    }

    public function isEmpty(): bool
    {
        $prefixes = $this->accessiblePathPrefixes();

        return $prefixes !== null && count($prefixes) === 0;
    }

    /**
     * Whether a given org_unit path falls within this user's scope.
     */
    public function canAccessPath(string $unitPath): bool
    {
        $prefixes = $this->accessiblePathPrefixes();

        if ($prefixes === null) {
            return true;
        }

        foreach ($prefixes as $prefix) {
            if (str_starts_with($unitPath, $prefix)) {
                return true;
            }
        }

        return false;
    }
}

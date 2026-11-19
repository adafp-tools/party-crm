<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\User;

class UserObserver
{
    private const SKIP_FIELDS = ['updated_at', 'remember_token', 'password', 'totp_secret'];

    public function created(User $user): void
    {
        AuditLog::record('user.created', $user);
    }

    public function updated(User $user): void
    {
        $changed = array_diff(array_keys($user->getChanges()), self::SKIP_FIELDS);

        // Always log password and 2FA changes by name, never the value
        if ($user->wasChanged('password')) {
            $changed[] = 'password';
        }
        if ($user->wasChanged('totp_secret') || $user->wasChanged('totp_confirmed_at')) {
            $changed[] = 'totp_setup';
        }

        $changed = array_unique(array_values($changed));

        if (empty($changed)) {
            return;
        }

        AuditLog::record('user.updated', $user, $changed);
    }

    public function deleted(User $user): void
    {
        AuditLog::record('user.deleted', $user);
    }
}

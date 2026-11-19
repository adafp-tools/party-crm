<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Member;

class MemberObserver
{
    private const SKIP_FIELDS = ['updated_at', 'email_hash', 'phone_hash', 'sync_version'];

    public function created(Member $member): void
    {
        AuditLog::record('member.created', $member);
    }

    public function updated(Member $member): void
    {
        $changed = array_diff(array_keys($member->getChanges()), self::SKIP_FIELDS);

        if (empty($changed)) {
            return;
        }

        AuditLog::record('member.updated', $member, array_values($changed));
    }

    public function deleted(Member $member): void
    {
        AuditLog::record('member.deleted', $member);
    }

    public function restored(Member $member): void
    {
        AuditLog::record('member.restored', $member);
    }
}

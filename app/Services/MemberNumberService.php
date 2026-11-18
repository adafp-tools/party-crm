<?php

namespace App\Services;

use App\Models\Member;
use App\Models\MemberSequence;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class MemberNumberService
{
    /**
     * Assign a membership number to the given member inside a transaction.
     * Uses a row-level lock (supported by both SQLite and MariaDB).
     */
    public function assign(Member $member): void
    {
        DB::transaction(function () use ($member) {
            $unit = $member->orgUnit;
            $prefix = Setting::get('membership_prefix', 'MEM');
            $unitCode = $unit->code ?? 'XX';

            $sequence = MemberSequence::where('org_unit_id', $unit->id)->lockForUpdate()->first();

            if (! $sequence) {
                $sequence = MemberSequence::create([
                    'org_unit_id' => $unit->id,
                    'next_seq' => 1,
                ]);
            }

            $memberNo = sprintf('%s-%s-%06d', strtoupper($prefix), strtoupper($unitCode), $sequence->next_seq);

            $member->update(['membership_no' => $memberNo]);

            $sequence->increment('next_seq');
        });
    }
}

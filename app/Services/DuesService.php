<?php

namespace App\Services;

use App\Models\DuesPayment;
use App\Models\DuesPlan;
use App\Models\Member;
use Illuminate\Support\Facades\Cache;

class DuesService
{
    /**
     * Returns 'paid', 'owing', or 'exempt' for the current period.
     * Result is cached for 1 hour per member.
     */
    public function statusFor(Member $member): string
    {
        return Cache::remember("dues_status_{$member->id}", 3600, function () use ($member) {
            return $this->computeStatus($member);
        });
    }

    public function forgetCache(?Member $member): void
    {
        if ($member === null) {
            return;
        }

        Cache::forget("dues_status_{$member->id}");
    }

    /**
     * @return array{paid: int, owing: int, total: int}
     */
    public function arrearsForUnit(string $orgUnitId): array
    {
        $members = Member::where('org_unit_id', $orgUnitId)
            ->where('status', 'active')
            ->get();

        $paid = 0;
        $owing = 0;

        foreach ($members as $member) {
            if ($this->statusFor($member) === 'paid') {
                $paid++;
            } else {
                $owing++;
            }
        }

        return ['paid' => $paid, 'owing' => $owing, 'total' => $members->count()];
    }

    private function computeStatus(Member $member): string
    {
        // No active plans → no dues obligation
        $plans = DuesPlan::active()->get();
        if ($plans->isEmpty()) {
            return 'exempt';
        }

        // Find applicable plan for this member
        $applicablePlan = $plans->first(function (DuesPlan $plan) use ($member) {
            return match ($plan->applies_to) {
                'all' => true,
                'member' => $member->category === 'member',
                'supporter' => $member->category === 'supporter',
                'youth_wing' => $member->is_youth_wing,
                'women_wing' => $member->is_women_wing,
                default => false,
            };
        });

        if (! $applicablePlan) {
            return 'exempt';
        }

        $now = now();

        $periodStart = match ($applicablePlan->period) {
            'monthly' => $now->copy()->startOfMonth(),
            'yearly' => $now->copy()->startOfYear(),
            'one_off' => null,
        };

        // For one_off: ever paid and confirmed/reconciled = paid
        if ($periodStart === null) {
            return DuesPayment::where('member_id', $member->id)
                ->whereIn('status', ['confirmed', 'reconciled'])
                ->exists() ? 'paid' : 'owing';
        }

        $hasPaid = DuesPayment::where('member_id', $member->id)
            ->whereIn('status', ['confirmed', 'reconciled'])
            ->where('period_from', '>=', $periodStart->toDateString())
            ->exists();

        return $hasPaid ? 'paid' : 'owing';
    }
}

<?php

namespace App\Services;

use App\Models\DuesPayment;
use App\Models\Event;
use App\Models\Member;
use App\Models\OrgUnit;
use App\Models\Task;
use Illuminate\Support\Collection;

/**
 * Aggregated statistics scoped to the authenticated user's accessible units.
 *
 * Small-number suppression: any per-subgroup count below the threshold is
 * replaced with the display string "<5" to prevent inferring individual
 * identity from unit-level breakdowns.
 */
class StatsService
{
    private const SUPPRESS_THRESHOLD = 5;

    public function suppress(int $n): string
    {
        return $n < self::SUPPRESS_THRESHOLD ? '<5' : number_format($n);
    }

    /**
     * Whether a count is suppressed (useful to conditionally hide detail links).
     */
    public function isSuppressed(int $n): bool
    {
        return $n < self::SUPPRESS_THRESHOLD;
    }

    /**
     * Counts of members by status, scoped to the user's accessible units.
     * Returns exact totals (no per-individual risk at this level).
     *
     * @return array<string, int>
     */
    public function memberStatusCounts(): array
    {
        $rows = Member::query()
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();

        $statuses = ['active', 'applicant', 'suspended', 'inactive', 'deceased'];

        return array_combine($statuses, array_map(fn ($s) => (int) ($rows[$s] ?? 0), $statuses));
    }

    /**
     * Active member count per org unit, with small-number suppression.
     * Returns Collection of ['id', 'name', 'raw', 'display'].
     */
    public function activeMembersByUnit(): Collection
    {
        return OrgUnit::query()
            ->where('is_active', true)
            ->withCount(['members' => fn ($q) => $q->where('status', 'active')])
            ->orderByDesc('members_count')
            ->limit(30)
            ->get()
            ->map(fn ($unit) => [
                'id' => $unit->id,
                'name' => $unit->name,
                'raw' => $unit->members_count,
                'display' => $this->suppress($unit->members_count),
                'suppressed' => $this->isSuppressed($unit->members_count),
            ]);
    }

    /**
     * Confirmed dues payments for the current calendar month.
     *
     * @return array{count: int, total: float, currency: string}
     */
    public function duesSummaryThisMonth(): array
    {
        $row = DuesPayment::query()
            ->where('status', 'confirmed')
            ->whereNotNull('confirmed_at')
            ->whereMonth('confirmed_at', now()->month)
            ->whereYear('confirmed_at', now()->year)
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(amount), 0) as total')
            ->first();

        return [
            'count' => (int) ($row->cnt ?? 0),
            'total' => (float) ($row->total ?? 0),
        ];
    }

    /**
     * Number of upcoming events (starts_at in the future).
     */
    public function upcomingEventsCount(): int
    {
        return Event::query()->where('starts_at', '>', now())->count();
    }

    /**
     * Number of open overdue tasks visible to this user.
     */
    public function overdueTasksCount(): int
    {
        return Task::query()->overdue()->count();
    }
}

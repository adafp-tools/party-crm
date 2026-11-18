<?php

namespace App\Livewire\Reports;

use App\Models\Member;
use App\Services\StatsService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Index extends Component
{
    public function mount(): void
    {
        Gate::authorize('viewAny', Member::class);
    }

    public function getStatsProperty(): array
    {
        $stats = app(StatsService::class);

        return [
            'memberCounts' => $stats->memberStatusCounts(),
            'byUnit' => $stats->activeMembersByUnit(),
            'dues' => $stats->duesSummaryThisMonth(),
            'upcomingEvents' => $stats->upcomingEventsCount(),
            'overdueTasks' => $stats->overdueTasksCount(),
        ];
    }

    public function render()
    {
        return view('livewire.reports.index', ['stats' => $this->stats]);
    }
}

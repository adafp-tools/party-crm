<?php

namespace App\Http\Controllers;

use App\Services\StatsService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(StatsService $stats): View
    {
        return view('dashboard', [
            'memberCounts' => $stats->memberStatusCounts(),
            'dues' => $stats->duesSummaryThisMonth(),
            'upcomingEvents' => $stats->upcomingEventsCount(),
            'overdueTasks' => $stats->overdueTasksCount(),
            'newMembersThisMonth' => $stats->newMembersThisMonth(),
            'newMembersLastMonth' => $stats->newMembersLastMonth(),
            'sparkline' => $stats->memberGrowthSparkline(),
        ]);
    }
}

<?php

namespace App\Jobs;

use App\Mail\OverdueTasksDigest;
use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendOverdueTasksDigest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Jobs run without an authenticated user, so bypass UnitScopedScope entirely.
        $assigneeIds = Task::withoutGlobalScopes()
            ->overdue()
            ->where('digest_opt_out', false)
            ->whereNotNull('assignee_id')
            ->pluck('assignee_id')
            ->unique();

        $users = User::where('is_active', true)->whereIn('id', $assigneeIds)->get();

        foreach ($users as $user) {
            $tasks = Task::withoutGlobalScopes()
                ->overdue()
                ->where('digest_opt_out', false)
                ->where('assignee_id', $user->id)
                ->with('orgUnit')
                ->get();

            if ($tasks->isEmpty()) {
                continue;
            }

            Mail::to($user->email)->send(new OverdueTasksDigest($user, $tasks));

            Task::withoutGlobalScopes()
                ->whereIn('id', $tasks->pluck('id'))
                ->update(['digest_sent_at' => now()]);
        }
    }
}

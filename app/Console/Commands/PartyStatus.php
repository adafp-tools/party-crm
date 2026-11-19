<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Models\Event;
use App\Models\Member;
use App\Models\OrgUnit;
use App\Models\Setting;
use App\Models\Task;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PartyStatus extends Command
{
    protected $signature = 'party:status';

    protected $description = 'Show installation health and statistics';

    public function handle(): int
    {
        $installedAt = Setting::get('installed_at');

        $this->newLine();
        $this->line('<fg=cyan;options=bold>Party CRM — Installation Status</>');
        $this->newLine();

        if (! $installedAt) {
            $this->error('Not installed. Run: php artisan party:install');

            return self::FAILURE;
        }

        // ── Party settings ──────────────────────────────────────────
        $this->line('<options=bold>Party</>');
        $this->table(['Key', 'Value'], [
            ['Name',       Setting::get('party_name')],
            ['Short name', Setting::get('party_short_name')],
            ['Prefix',     Setting::get('membership_prefix')],
            ['Locales',    Setting::get('locales')],
            ['Currency',   Setting::get('currency')],
            ['Timezone',   Setting::get('timezone')],
            ['Demo mode',  Setting::get('is_demo') === '1' ? 'Yes' : 'No'],
            ['Installed',  $installedAt],
        ]);

        // ── Environment ─────────────────────────────────────────────
        $this->newLine();
        $this->line('<options=bold>Environment</>');

        $appEnv = config('app.env');
        $appDebug = config('app.debug');
        $dbDriver = config('database.default');
        $queue = config('queue.default');
        $mail = config('mail.default');
        $appKey = config('app.key') ? 'set ✓' : '<fg=red>MISSING ✗</>';
        $biKey = config('app.blind_index_key') ? 'set ✓' : '<fg=yellow>not set ⚠ (search won\'t work)</>';

        $debugStatus = $appDebug
            ? ($appEnv === 'local' ? '<fg=yellow>true (local)</>' : '<fg=red>true ✗ SET TO FALSE IN PRODUCTION</>')
            : 'false ✓';

        $this->table(['Setting', 'Value'], [
            ['APP_ENV',         $appEnv],
            ['APP_DEBUG',       $debugStatus],
            ['APP_KEY',         $appKey],
            ['BLIND_INDEX_KEY', $biKey],
            ['DB driver',       $dbDriver],
            ['Queue',           $queue],
            ['Mail',            $mail],
        ]);

        // ── Database connectivity ────────────────────────────────────
        try {
            DB::connection()->getPdo();
            $this->line(' <fg=green>✓ Database connection OK</>');
        } catch (\Exception $e) {
            $this->line(' <fg=red>✗ Database connection FAILED: '.$e->getMessage().'</>');
        }

        // ── Data summary ─────────────────────────────────────────────
        $this->newLine();
        $this->line('<options=bold>Data</>');

        $totalMembers = Member::withoutGlobalScopes()->count();
        $activeMembers = Member::withoutGlobalScopes()->where('status', 'active')->count();

        $this->table(['Model', 'Count'], [
            ['Users',        User::count()],
            ['Org units',    OrgUnit::count()],
            ['Members',      "{$totalMembers} ({$activeMembers} active)"],
            ['Events',       Event::withoutGlobalScopes()->count()],
            ['Tasks',        Task::withoutGlobalScopes()->count()],
            ['Campaigns',    Campaign::withoutGlobalScopes()->count()],
        ]);

        return self::SUCCESS;
    }
}

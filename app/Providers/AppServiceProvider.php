<?php

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Contracts\SmsGateway;
use App\Gateways\ManualGateway;
use App\Gateways\NullSmsGateway;
use App\Models\Campaign;
use App\Models\DuesPayment;
use App\Models\DuesPlan;
use App\Models\Event;
use App\Models\Member;
use App\Models\OrgUnit;
use App\Models\Task;
use App\Models\User;
use App\Policies\CampaignPolicy;
use App\Policies\DuesPaymentPolicy;
use App\Policies\DuesPlanPolicy;
use App\Policies\EventPolicy;
use App\Policies\MemberPolicy;
use App\Policies\OrgUnitPolicy;
use App\Policies\TaskPolicy;
use App\Policies\UserPolicy;
use App\Services\BlindIndexService;
use App\Services\DuesService;
use App\Services\MemberNumberService;
use App\Services\StatsService;
use App\Services\UnitScope;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UnitScope::class, fn () => new UnitScope(auth()->user()));
        $this->app->singleton(BlindIndexService::class);
        $this->app->singleton(MemberNumberService::class);
        $this->app->singleton(DuesService::class);
        $this->app->bind(StatsService::class);
        $this->app->bind(PaymentGateway::class, ManualGateway::class);
        $this->app->bind(SmsGateway::class, NullSmsGateway::class);
    }

    public function boot(): void
    {
        Gate::policy(OrgUnit::class, OrgUnitPolicy::class);
        Gate::policy(Member::class, MemberPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(DuesPlan::class, DuesPlanPolicy::class);
        Gate::policy(DuesPayment::class, DuesPaymentPolicy::class);
        Gate::policy(Event::class, EventPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(Campaign::class, CampaignPolicy::class);
    }
}

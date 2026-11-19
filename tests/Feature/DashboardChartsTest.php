<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTwoFactorAuthenticated;
use App\Models\Member;
use App\Models\OrgLevel;
use App\Models\OrgUnit;
use App\Models\User;
use App\Services\StatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardChartsTest extends TestCase
{
    use RefreshDatabase;

    private OrgUnit $unit;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['national_admin', 'national_leadership', 'treasurer',
            'communications_officer', 'regional_coordinator',
            'branch_secretary', 'platform_support'] as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }

        $level = OrgLevel::factory()->create(['depth' => 0, 'name_key' => 'national']);
        $this->unit = OrgUnit::factory()->create([
            'org_level_id' => $level->id,
            'parent_id' => null,
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('national_admin');
    }

    public function test_new_members_this_month_counts_current_month_records(): void
    {
        $this->actingAs($this->admin);

        Member::factory()->count(3)->create([
            'org_unit_id' => $this->unit->id,
            'created_at' => now(),
        ]);
        Member::factory()->count(2)->create([
            'org_unit_id' => $this->unit->id,
            'created_at' => now()->subMonths(2),
        ]);

        $stats = app(StatsService::class);

        $this->assertSame(3, $stats->newMembersThisMonth());
    }

    public function test_new_members_last_month_counts_previous_month_records(): void
    {
        $this->actingAs($this->admin);

        Member::factory()->count(4)->create([
            'org_unit_id' => $this->unit->id,
            'created_at' => now()->subMonth(),
        ]);
        Member::factory()->count(1)->create([
            'org_unit_id' => $this->unit->id,
            'created_at' => now(),
        ]);

        $stats = app(StatsService::class);

        $this->assertSame(4, $stats->newMembersLastMonth());
    }

    public function test_member_growth_sparkline_returns_correct_number_of_months(): void
    {
        $this->actingAs($this->admin);

        $stats = app(StatsService::class);

        $sparkline = $stats->memberGrowthSparkline(6);

        $this->assertCount(6, $sparkline);
    }

    public function test_member_growth_sparkline_counts_per_month(): void
    {
        $this->actingAs($this->admin);

        Member::factory()->count(2)->create([
            'org_unit_id' => $this->unit->id,
            'created_at' => now(),
        ]);
        Member::factory()->count(5)->create([
            'org_unit_id' => $this->unit->id,
            'created_at' => now()->subMonth(),
        ]);

        $stats = app(StatsService::class);
        $sparkline = $stats->memberGrowthSparkline(6);

        // Last entry = this month
        $this->assertSame(2, $sparkline[5]['count']);
        // Second-to-last = last month
        $this->assertSame(5, $sparkline[4]['count']);
    }

    public function test_dashboard_passes_new_member_vars_to_view(): void
    {
        $this->withoutVite();

        $this->withoutMiddleware(EnsureTwoFactorAuthenticated::class)
            ->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('newMembersThisMonth')
            ->assertViewHas('newMembersLastMonth')
            ->assertViewHas('sparkline');
    }
}

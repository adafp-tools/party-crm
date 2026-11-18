<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTwoFactorAuthenticated;
use App\Models\DuesPayment;
use App\Models\Event;
use App\Models\Member;
use App\Models\OrgUnit;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['national_admin', 'national_leadership', 'treasurer',
            'communications_officer', 'regional_coordinator',
            'branch_secretary', 'platform_support'] as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        return $user;
    }

    public function test_dashboard_requires_auth(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_national_admin_sees_dashboard(): void
    {
        $admin = $this->userWithRole('national_admin');

        $this->withoutMiddleware(EnsureTwoFactorAuthenticated::class)
            ->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewIs('dashboard');
    }

    public function test_dashboard_shows_total_member_count(): void
    {
        $admin = $this->userWithRole('national_admin');
        $unit = OrgUnit::factory()->create();

        Member::factory()->count(3)->create(['org_unit_id' => $unit->id, 'status' => 'active']);
        Member::factory()->count(2)->create(['org_unit_id' => $unit->id, 'status' => 'applicant']);

        $this->withoutMiddleware(EnsureTwoFactorAuthenticated::class)
            ->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('memberCounts', function (array $counts) {
                return $counts['active'] === 3 && $counts['applicant'] === 2;
            });
    }

    public function test_dashboard_shows_upcoming_events_count(): void
    {
        $admin = $this->userWithRole('national_admin');
        $unit = OrgUnit::factory()->create();

        Event::factory()->count(2)->create([
            'org_unit_id' => $unit->id,
            'starts_at' => now()->addDays(7),
        ]);
        // Past event — should not count
        Event::factory()->create([
            'org_unit_id' => $unit->id,
            'starts_at' => now()->subDay(),
        ]);

        $this->withoutMiddleware(EnsureTwoFactorAuthenticated::class)
            ->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('upcomingEvents', 2);
    }

    public function test_dashboard_shows_overdue_tasks_count(): void
    {
        $admin = $this->userWithRole('national_admin');
        $unit = OrgUnit::factory()->create();

        Task::factory()->count(2)->create([
            'org_unit_id' => $unit->id,
            'status' => 'pending',
            'due_at' => now()->subDay(),
        ]);
        // Future task — not overdue
        Task::factory()->create([
            'org_unit_id' => $unit->id,
            'status' => 'pending',
            'due_at' => now()->addDay(),
        ]);

        $this->withoutMiddleware(EnsureTwoFactorAuthenticated::class)
            ->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('overdueTasks', 2);
    }

    public function test_dashboard_shows_dues_this_month(): void
    {
        $admin = $this->userWithRole('national_admin');

        DuesPayment::factory()->confirmed()->count(3)->create([
            'amount' => 1000,
            'confirmed_at' => now(),
        ]);
        // Last month — should not count
        DuesPayment::factory()->confirmed()->create([
            'amount' => 500,
            'confirmed_at' => now()->subMonth(),
        ]);

        $this->withoutMiddleware(EnsureTwoFactorAuthenticated::class)
            ->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('dues', function (array $dues) {
                return $dues['count'] === 3 && (float) $dues['total'] === 3000.0;
            });
    }
}

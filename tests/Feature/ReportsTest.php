<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTwoFactorAuthenticated;
use App\Livewire\Reports\Index;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\OrgUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportsTest extends TestCase
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

    public function test_national_admin_can_view_reports(): void
    {
        $admin = $this->userWithRole('national_admin');

        $this->actingAs($admin);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->assertOk();
    }

    public function test_platform_support_cannot_view_reports(): void
    {
        $support = $this->userWithRole('platform_support');

        $this->actingAs($support);

        Livewire::actingAs($support)
            ->test(Index::class)
            ->assertForbidden();
    }

    public function test_reports_page_shows_member_status_counts(): void
    {
        $admin = $this->userWithRole('national_admin');
        $unit = OrgUnit::factory()->create();

        Member::factory()->count(5)->create(['org_unit_id' => $unit->id, 'status' => 'active']);
        Member::factory()->count(2)->create(['org_unit_id' => $unit->id, 'status' => 'applicant']);

        $this->actingAs($admin);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->assertSee('5') // active count
            ->assertSee('2'); // applicant count
    }

    public function test_active_members_by_unit_suppresses_small_counts(): void
    {
        $admin = $this->userWithRole('national_admin');
        $unit = OrgUnit::factory()->create(['name' => 'Small Branch', 'is_active' => true]);

        // 3 active members — below the threshold of 5
        Member::factory()->count(3)->create(['org_unit_id' => $unit->id, 'status' => 'active']);

        $this->actingAs($admin);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->assertSee('Small Branch')
            ->assertSee('<5');
    }

    public function test_active_members_by_unit_shows_exact_count_when_above_threshold(): void
    {
        $admin = $this->userWithRole('national_admin');
        $unit = OrgUnit::factory()->create(['name' => 'Large Branch', 'is_active' => true]);

        Member::factory()->count(10)->create(['org_unit_id' => $unit->id, 'status' => 'active']);

        $this->actingAs($admin);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->assertSee('Large Branch')
            ->assertSee('10');
    }

    public function test_national_admin_can_download_audit_log(): void
    {
        $admin = $this->userWithRole('national_admin');

        for ($i = 0; $i < 3; $i++) {
            AuditLog::create(['action' => 'test.event', 'created_at' => now()]);
        }

        $response = $this->withoutMiddleware(EnsureTwoFactorAuthenticated::class)
            ->actingAs($admin)
            ->get(route('reports.audit-export'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $response->assertHeader('content-disposition');
    }

    public function test_non_admin_cannot_download_audit_log(): void
    {
        $leader = $this->userWithRole('national_leadership');

        $this->withoutMiddleware(EnsureTwoFactorAuthenticated::class)
            ->actingAs($leader)
            ->get(route('reports.audit-export'))
            ->assertForbidden();
    }

    public function test_audit_log_export_records_itself_in_audit_log(): void
    {
        $admin = $this->userWithRole('national_admin');

        $this->withoutMiddleware(EnsureTwoFactorAuthenticated::class)
            ->actingAs($admin)
            ->get(route('reports.audit-export'))
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'audit_log.exported',
            'user_id' => $admin->id,
        ]);
    }
}

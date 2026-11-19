<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTwoFactorAuthenticated;
use App\Livewire\Members\Show;
use App\Livewire\Reports\AuditViewer;
use App\Models\AuditLog;
use App\Models\Export;
use App\Models\Member;
use App\Models\OrgUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class Phase8Test extends TestCase
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

    // === Audit Viewer ===

    public function test_national_admin_can_view_audit_log(): void
    {
        $admin = $this->userWithRole('national_admin');
        $this->actingAs($admin);

        Livewire::actingAs($admin)
            ->test(AuditViewer::class)
            ->assertOk();
    }

    public function test_non_admin_cannot_view_audit_log(): void
    {
        $leader = $this->userWithRole('national_leadership');
        $this->actingAs($leader);

        Livewire::actingAs($leader)
            ->test(AuditViewer::class)
            ->assertForbidden();
    }

    public function test_platform_support_cannot_view_audit_log(): void
    {
        $support = $this->userWithRole('platform_support');
        $this->actingAs($support);

        Livewire::actingAs($support)
            ->test(AuditViewer::class)
            ->assertForbidden();
    }

    public function test_audit_viewer_displays_log_entries(): void
    {
        $admin = $this->userWithRole('national_admin');
        AuditLog::create(['action' => 'member.created', 'user_id' => $admin->id, 'created_at' => now()]);

        $this->actingAs($admin);

        Livewire::actingAs($admin)
            ->test(AuditViewer::class)
            ->assertSee('member.created');
    }

    public function test_audit_viewer_filters_by_action(): void
    {
        $admin = $this->userWithRole('national_admin');
        AuditLog::create(['action' => 'member.created', 'created_at' => now()]);
        AuditLog::create(['action' => 'dues.recorded', 'created_at' => now()]);

        $this->actingAs($admin);

        Livewire::actingAs($admin)
            ->test(AuditViewer::class)
            ->set('filterAction', 'member')
            ->assertSee('member.created')
            ->assertDontSee('dues.recorded');
    }

    // === Member CSV Export ===

    public function test_national_admin_can_export_members(): void
    {
        $admin = $this->userWithRole('national_admin');
        $unit = OrgUnit::factory()->create();
        Member::factory()->count(3)->create(['org_unit_id' => $unit->id]);

        $response = $this->withoutMiddleware(EnsureTwoFactorAuthenticated::class)
            ->actingAs($admin)
            ->post(route('exports.members'), ['reason' => 'Annual membership audit']);

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_export_requires_reason(): void
    {
        $admin = $this->userWithRole('national_admin');

        $this->withoutMiddleware(EnsureTwoFactorAuthenticated::class)
            ->actingAs($admin)
            ->post(route('exports.members'), ['reason' => 'x'])
            ->assertSessionHasErrors('reason');
    }

    public function test_export_creates_export_record(): void
    {
        $admin = $this->userWithRole('national_admin');
        $unit = OrgUnit::factory()->create();
        Member::factory()->count(2)->create(['org_unit_id' => $unit->id]);

        $this->withoutMiddleware(EnsureTwoFactorAuthenticated::class)
            ->actingAs($admin)
            ->post(route('exports.members'), ['reason' => 'Compliance check for board']);

        $this->assertDatabaseHas('exports', [
            'user_id' => $admin->id,
            'type' => 'members',
            'row_count' => 2,
        ]);
    }

    public function test_export_records_audit_log_entry(): void
    {
        $admin = $this->userWithRole('national_admin');
        $unit = OrgUnit::factory()->create();
        Member::factory()->count(1)->create(['org_unit_id' => $unit->id]);

        $this->withoutMiddleware(EnsureTwoFactorAuthenticated::class)
            ->actingAs($admin)
            ->post(route('exports.members'), ['reason' => 'Regional coordinator review']);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'members.exported',
            'user_id' => $admin->id,
        ]);
    }

    public function test_regional_coordinator_can_export_members(): void
    {
        $coordinator = $this->userWithRole('regional_coordinator');
        $unit = OrgUnit::factory()->create();
        Member::factory()->count(1)->create(['org_unit_id' => $unit->id]);

        $this->withoutMiddleware(EnsureTwoFactorAuthenticated::class)
            ->actingAs($coordinator)
            ->post(route('exports.members'), ['reason' => 'Regional audit of branch lists'])
            ->assertOk();
    }

    public function test_branch_secretary_cannot_export_members(): void
    {
        $secretary = $this->userWithRole('branch_secretary');

        $this->withoutMiddleware(EnsureTwoFactorAuthenticated::class)
            ->actingAs($secretary)
            ->post(route('exports.members'), ['reason' => 'Trying to export members'])
            ->assertForbidden();
    }

    public function test_platform_support_cannot_export_members(): void
    {
        $support = $this->userWithRole('platform_support');

        $this->withoutMiddleware(EnsureTwoFactorAuthenticated::class)
            ->actingAs($support)
            ->post(route('exports.members'), ['reason' => 'System health check'])
            ->assertForbidden();
    }

    // === Data Requests ===

    public function test_national_admin_can_create_data_request(): void
    {
        $admin = $this->userWithRole('national_admin');
        $unit = OrgUnit::factory()->create();
        $member = Member::factory()->create(['org_unit_id' => $unit->id]);

        $this->actingAs($admin);
        $this->withoutVite();

        Livewire::actingAs($admin)
            ->test(Show::class, ['member' => $member])
            ->set('newDataRequestType', 'access')
            ->set('newDataRequestNotes', 'Member requested their data copy')
            ->call('createDataRequest')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('data_requests', [
            'member_id' => $member->id,
            'type' => 'access',
            'status' => 'pending',
        ]);
    }

    public function test_data_request_type_must_be_valid(): void
    {
        $admin = $this->userWithRole('national_admin');
        $unit = OrgUnit::factory()->create();
        $member = Member::factory()->create(['org_unit_id' => $unit->id]);

        $this->actingAs($admin);
        $this->withoutVite();

        Livewire::actingAs($admin)
            ->test(Show::class, ['member' => $member])
            ->set('newDataRequestType', 'invalid_type')
            ->call('createDataRequest')
            ->assertHasErrors(['newDataRequestType']);
    }

    public function test_non_admin_cannot_create_data_request(): void
    {
        $leader = $this->userWithRole('national_leadership');
        $unit = OrgUnit::factory()->create();
        $member = Member::factory()->create(['org_unit_id' => $unit->id]);

        $this->actingAs($leader);
        $this->withoutVite();

        Livewire::actingAs($leader)
            ->test(Show::class, ['member' => $member])
            ->set('newDataRequestType', 'access')
            ->call('createDataRequest')
            ->assertForbidden();
    }

    // === Privacy Page ===

    public function test_privacy_page_is_publicly_accessible(): void
    {
        $this->withoutMiddleware(EnsureTwoFactorAuthenticated::class)
            ->get(route('privacy'))
            ->assertOk();
    }

    // === Route / Middleware Coverage ===

    public function test_audit_log_route_requires_auth(): void
    {
        $this->get(route('reports.audit-log'))
            ->assertRedirect(route('login'));
    }

    public function test_exports_route_requires_auth(): void
    {
        $this->post(route('exports.members'), ['reason' => 'Test'])
            ->assertRedirect(route('login'));
    }
}

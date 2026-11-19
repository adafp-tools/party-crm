<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTwoFactorAuthenticated;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\OrgUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SecurityTest extends TestCase
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

    private function userWithRole(string $role, bool $active = true): User
    {
        $user = User::factory()->create(['is_active' => $active]);
        $user->assignRole($role);

        return $user;
    }

    // ── Authentication ───────────────────────────────────────────────

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'inactive@party.test',
            'password' => Hash::make('SecurePass123!'),
            'is_active' => false,
        ]);

        $this->post(route('login'), [
            'email' => 'inactive@party.test',
            'password' => 'SecurePass123!',
        ])->assertRedirect();

        $this->assertGuest();
    }

    public function test_active_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'active@party.test',
            'password' => Hash::make('SecurePass123!'),
            'is_active' => true,
        ]);
        $user->assignRole('branch_secretary');

        $this->post(route('login'), [
            'email' => 'active@party.test',
            'password' => 'SecurePass123!',
        ])->assertRedirect();

        $this->assertAuthenticated();
    }

    public function test_failed_login_records_audit_log(): void
    {
        User::factory()->create([
            'email' => 'victim@party.test',
            'password' => Hash::make('correct-password'),
            'is_active' => true,
        ]);

        $this->post(route('login'), [
            'email' => 'victim@party.test',
            'password' => 'wrong-password',
        ]);

        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.login_failed']);
    }

    public function test_inactive_user_login_records_blocked_audit_log(): void
    {
        User::factory()->create([
            'email' => 'blocked@party.test',
            'password' => Hash::make('SecurePass123!'),
            'is_active' => false,
        ]);

        $this->post(route('login'), [
            'email' => 'blocked@party.test',
            'password' => 'SecurePass123!',
        ]);

        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.login_blocked_inactive']);
    }

    public function test_successful_login_records_audit_log(): void
    {
        $user = User::factory()->create([
            'email' => 'user@party.test',
            'password' => Hash::make('SecurePass123!'),
            'is_active' => true,
        ]);
        $user->assignRole('branch_secretary');

        $this->post(route('login'), [
            'email' => 'user@party.test',
            'password' => 'SecurePass123!',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.login',
            'user_id' => $user->id,
        ]);
    }

    // ── Audit trail — Members ────────────────────────────────────────

    public function test_creating_member_records_audit_log(): void
    {
        $admin = $this->userWithRole('national_admin');
        $unit = OrgUnit::factory()->create();

        $this->actingAs($admin);

        Member::factory()->create([
            'org_unit_id' => $unit->id,
            'first_name' => 'Fatima',
            'last_name' => 'Diallo',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'member.created',
            'user_id' => $admin->id,
        ]);
    }

    public function test_updating_member_records_audit_log_with_changed_fields(): void
    {
        $admin = $this->userWithRole('national_admin');
        $unit = OrgUnit::factory()->create();

        $this->actingAs($admin);

        $member = Member::factory()->create(['org_unit_id' => $unit->id, 'status' => 'applicant']);
        $member->update(['status' => 'active', 'updated_by' => $admin->id]);

        $log = AuditLog::where('action', 'member.updated')
            ->where('subject_id', $member->id)
            ->latest('created_at')
            ->first();

        $this->assertNotNull($log);
        $this->assertContains('status', $log->changed_fields);
    }

    public function test_deleting_member_records_audit_log(): void
    {
        $admin = $this->userWithRole('national_admin');
        $unit = OrgUnit::factory()->create();

        $this->actingAs($admin);

        $member = Member::factory()->create(['org_unit_id' => $unit->id]);
        $memberId = $member->id;
        $member->delete();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'member.deleted',
            'subject_id' => $memberId,
        ]);
    }

    // ── Authorization boundaries ─────────────────────────────────────

    public function test_platform_support_cannot_access_member_index(): void
    {
        $support = $this->userWithRole('platform_support');

        $this->withoutMiddleware(EnsureTwoFactorAuthenticated::class)
            ->actingAs($support)
            ->get(route('members.index'))
            ->assertForbidden();
    }

    public function test_platform_support_cannot_access_reports(): void
    {
        $support = $this->userWithRole('platform_support');

        $this->withoutMiddleware(EnsureTwoFactorAuthenticated::class)
            ->actingAs($support)
            ->get(route('reports.audit-export'))
            ->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_unauthenticated_user_cannot_access_members(): void
    {
        $this->get(route('members.index'))->assertRedirect(route('login'));
    }
}

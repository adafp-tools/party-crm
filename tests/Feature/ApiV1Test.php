<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Member;
use App\Models\OrgLevel;
use App\Models\OrgUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApiV1Test extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private OrgUnit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['national_admin', 'national_leadership', 'treasurer',
            'communications_officer', 'regional_coordinator',
            'branch_secretary', 'platform_support'] as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }

        $this->user = User::factory()->create(['is_active' => true]);
        $this->user->assignRole('national_admin');

        $level = OrgLevel::factory()->create(['depth' => 0, 'name_key' => 'national']);
        $this->unit = OrgUnit::factory()->create([
            'org_level_id' => $level->id,
            'parent_id' => null,
            'is_active' => true,
        ]);
    }

    // ── GET /api/v1/me ────────────────────────────────────────────────────────

    public function test_me_returns_authenticated_user_data(): void
    {
        $token = $this->user->createToken('test-device')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonStructure(['data' => ['id', 'name', 'email', 'locale', 'roles']])
            ->assertJsonPath('data.id', $this->user->id)
            ->assertJsonPath('data.email', $this->user->email);
    }

    public function test_me_returns_401_without_token(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_me_includes_user_roles(): void
    {
        $token = $this->user->createToken('test-device')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.roles.0', 'national_admin');
    }

    // ── POST /api/v1/sync/pull ─────────────────────────────────────────────────

    public function test_sync_pull_returns_all_members_without_since(): void
    {
        Member::factory()->count(3)->create(['org_unit_id' => $this->unit->id]);

        $token = $this->user->createToken('test-device')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/sync/pull', ['device_name' => 'Test Phone', 'platform' => 'android'])
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data', 'meta' => ['server_time', 'count']]);
    }

    public function test_sync_pull_filters_by_since(): void
    {
        Member::factory()->count(2)->create([
            'org_unit_id' => $this->unit->id,
            'updated_at' => now()->subDays(10),
        ]);
        Member::factory()->count(1)->create([
            'org_unit_id' => $this->unit->id,
            'updated_at' => now(),
        ]);

        $token = $this->user->createToken('test-device')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/sync/pull', [
                'since' => now()->subDays(5)->toIso8601String(),
                'device_name' => 'Test Phone',
                'platform' => 'ios',
            ])
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_sync_pull_creates_device_record(): void
    {
        $token = $this->user->createToken('test-device')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/sync/pull', [
                'device_name' => 'My iPhone',
                'platform' => 'ios',
            ])
            ->assertOk();

        $this->assertDatabaseHas('devices', [
            'user_id' => $this->user->id,
            'name' => 'My iPhone',
            'platform' => 'ios',
        ]);
    }

    public function test_sync_pull_updates_last_synced_at(): void
    {
        $token = $this->user->createToken('test-device')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/sync/pull', [
                'device_name' => 'My Phone',
                'platform' => 'android',
            ])
            ->assertOk();

        $device = Device::where('user_id', $this->user->id)->first();
        $this->assertNotNull($device->last_synced_at);
    }

    public function test_sync_pull_returns_401_without_token(): void
    {
        $this->postJson('/api/v1/sync/pull')->assertUnauthorized();
    }

    public function test_sync_pull_validates_since_is_a_date(): void
    {
        $token = $this->user->createToken('test-device')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/sync/pull', ['since' => 'not-a-date'])
            ->assertUnprocessable();
    }
}

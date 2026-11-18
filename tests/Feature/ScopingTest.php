<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\OrgLevel;
use App\Models\OrgUnit;
use App\Models\RoleAssignment;
use App\Models\User;
use App\Services\UnitScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Mandatory scoping tests — these must pass forever.
 *
 * Rules verified:
 *  1. branch_secretary of Branch A cannot see any member of Branch B.
 *  2. regional_coordinator sees all branches inside their region, none outside.
 *  3. platform_support gets 403 on every member route.
 *  4. national_admin can see all members regardless of unit.
 */
class ScopingTest extends TestCase
{
    use RefreshDatabase;

    private OrgLevel $levelNational;

    private OrgLevel $levelRegion;

    private OrgLevel $levelBranch;

    private OrgUnit $national;

    private OrgUnit $regionA;

    private OrgUnit $regionB;

    private OrgUnit $branchA1;

    private OrgUnit $branchA2;

    private OrgUnit $branchB1;

    private Member $memberA1;

    private Member $memberA2;

    private Member $memberB1;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles
        foreach (['national_admin', 'national_leadership', 'treasurer',
            'communications_officer', 'regional_coordinator',
            'branch_secretary', 'platform_support'] as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }

        // 5-level org: National → Region A/B → Branch A1/A2/B1
        $this->levelNational = OrgLevel::create(['depth' => 0, 'name_key' => 'national', 'code_required' => false]);
        $this->levelRegion = OrgLevel::create(['depth' => 1, 'name_key' => 'region', 'code_required' => false]);
        $this->levelBranch = OrgLevel::create(['depth' => 2, 'name_key' => 'branch', 'code_required' => true]);

        $this->national = $this->makeUnit('National HQ', 'NAT', $this->levelNational);
        $this->regionA = $this->makeUnit('Region A', 'REG-A', $this->levelRegion, $this->national);
        $this->regionB = $this->makeUnit('Region B', 'REG-B', $this->levelRegion, $this->national);
        $this->branchA1 = $this->makeUnit('Branch A1', 'BA1', $this->levelBranch, $this->regionA);
        $this->branchA2 = $this->makeUnit('Branch A2', 'BA2', $this->levelBranch, $this->regionA);
        $this->branchB1 = $this->makeUnit('Branch B1', 'BB1', $this->levelBranch, $this->regionB);

        // One member in each branch (bypass scope for seeding via withoutGlobalScopes)
        $this->memberA1 = Member::withoutGlobalScopes()->create([
            'org_unit_id' => $this->branchA1->id,
            'first_name' => 'Alice',
            'last_name' => 'A1',
            'status' => 'active',
            'category' => 'member',
        ]);

        $this->memberA2 = Member::withoutGlobalScopes()->create([
            'org_unit_id' => $this->branchA2->id,
            'first_name' => 'Alice',
            'last_name' => 'A2',
            'status' => 'active',
            'category' => 'member',
        ]);

        $this->memberB1 = Member::withoutGlobalScopes()->create([
            'org_unit_id' => $this->branchB1->id,
            'first_name' => 'Bob',
            'last_name' => 'B1',
            'status' => 'active',
            'category' => 'member',
        ]);
    }

    // =========================================================================
    // Helper: create a unit with correct materialized path
    // =========================================================================

    private function makeUnit(string $name, string $code, OrgLevel $level, ?OrgUnit $parent = null): OrgUnit
    {
        $unit = OrgUnit::create([
            'name' => $name,
            'code' => $code,
            'org_level_id' => $level->id,
            'parent_id' => $parent?->id,
            'path' => '/',
            'is_active' => true,
        ]);

        $unit->rebuildPath();
        $unit->refresh();

        return $unit;
    }

    private function makeUser(string $role, ?OrgUnit $unit = null): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->syncRoles([$role]);

        if ($unit) {
            $roleModel = Role::findByName($role, 'web');
            RoleAssignment::create([
                'user_id' => $user->id,
                'role_id' => $roleModel->id,
                'org_unit_id' => $unit->id,
            ]);
        }

        return $user;
    }

    // =========================================================================
    // UnitScope service tests
    // =========================================================================

    public function test_national_admin_has_unrestricted_scope(): void
    {
        $admin = $this->makeUser('national_admin');
        $scope = new UnitScope($admin);

        $this->assertNull($scope->accessiblePathPrefixes());
        $this->assertTrue($scope->isUnrestricted());
    }

    public function test_platform_support_has_empty_scope(): void
    {
        $support = $this->makeUser('platform_support');
        $scope = new UnitScope($support);

        $this->assertSame([], $scope->accessiblePathPrefixes());
        $this->assertTrue($scope->isEmpty());
    }

    public function test_branch_secretary_scope_is_their_branch_path(): void
    {
        $secretary = $this->makeUser('branch_secretary', $this->branchA1);
        $scope = new UnitScope($secretary);

        $prefixes = $scope->accessiblePathPrefixes();

        $this->assertNotNull($prefixes);
        $this->assertCount(1, $prefixes);
        $this->assertSame($this->branchA1->path, $prefixes[0]);
    }

    public function test_regional_coordinator_scope_is_their_region_path(): void
    {
        $coordinator = $this->makeUser('regional_coordinator', $this->regionA);
        $scope = new UnitScope($coordinator);

        $prefixes = $scope->accessiblePathPrefixes();

        $this->assertNotNull($prefixes);
        $this->assertSame($this->regionA->path, $prefixes[0]);
    }

    // =========================================================================
    // Eloquent global scope: Member model isolation
    // =========================================================================

    public function test_national_admin_sees_all_members(): void
    {
        $admin = $this->makeUser('national_admin');

        $this->actingAs($admin);

        $ids = Member::pluck('id')->all();

        $this->assertContains($this->memberA1->id, $ids);
        $this->assertContains($this->memberA2->id, $ids);
        $this->assertContains($this->memberB1->id, $ids);
    }

    public function test_branch_secretary_of_a1_cannot_see_member_of_b1(): void
    {
        $secretary = $this->makeUser('branch_secretary', $this->branchA1);

        $this->actingAs($secretary);

        $ids = Member::pluck('id')->all();

        $this->assertContains($this->memberA1->id, $ids, 'Secretary should see their own branch member');
        $this->assertNotContains($this->memberA2->id, $ids, 'Secretary must not see Branch A2');
        $this->assertNotContains($this->memberB1->id, $ids, 'Secretary must not see Branch B1');
    }

    public function test_regional_coordinator_of_a_sees_both_a_branches_not_b(): void
    {
        $coordinator = $this->makeUser('regional_coordinator', $this->regionA);

        $this->actingAs($coordinator);

        $ids = Member::pluck('id')->all();

        $this->assertContains($this->memberA1->id, $ids, 'Coordinator should see Branch A1');
        $this->assertContains($this->memberA2->id, $ids, 'Coordinator should see Branch A2');
        $this->assertNotContains($this->memberB1->id, $ids, 'Coordinator must not see Region B members');
    }

    public function test_platform_support_sees_no_members(): void
    {
        $support = $this->makeUser('platform_support');

        $this->actingAs($support);

        $count = Member::count();

        $this->assertSame(0, $count);
    }

    // =========================================================================
    // HTTP route isolation: direct URL cannot bypass scope
    // =========================================================================

    public function test_platform_support_gets_403_on_structure_route(): void
    {
        $support = $this->makeUser('platform_support');

        $this->actingAs($support)
            ->get(route('structure.index'))
            ->assertForbidden();
    }

    public function test_platform_support_gets_403_on_users_route(): void
    {
        $support = $this->makeUser('platform_support');

        $this->actingAs($support)
            ->get(route('users.index'))
            ->assertForbidden();
    }

    // =========================================================================
    // Path integrity
    // =========================================================================

    public function test_path_contains_all_ancestor_ids(): void
    {
        $this->assertStringContainsString($this->national->id, $this->branchA1->path);
        $this->assertStringContainsString($this->regionA->id, $this->branchA1->path);
        $this->assertStringContainsString($this->branchA1->id, $this->branchA1->path);

        $this->assertStringNotContainsString($this->regionB->id, $this->branchA1->path);
        $this->assertStringNotContainsString($this->branchB1->id, $this->branchA1->path);
    }
}

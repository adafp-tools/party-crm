<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\DuesPayment;
use App\Models\DuesPlan;
use App\Models\Event;
use App\Models\Member;
use App\Models\OrgLevel;
use App\Models\OrgUnit;
use App\Models\RoleAssignment;
use App\Models\Setting;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Populates a fresh installation with realistic fake data for demos.
 * All data is factory-generated — never real member information.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $currency = Setting::get('currency', 'XOF');

        // ── Org levels (created by party:install, but ensure they exist) ──
        $national = OrgLevel::firstOrCreate(['depth' => 0], ['name_key' => 'national', 'code_required' => false]);
        $regional = OrgLevel::firstOrCreate(['depth' => 1], ['name_key' => 'regional', 'code_required' => false]);
        $branch = OrgLevel::firstOrCreate(['depth' => 2], ['name_key' => 'branch',   'code_required' => true]);

        // ── Org structure: 1 national → 3 regional → 3 branches each ──
        $nationalUnit = OrgUnit::create([
            'org_level_id' => $national->id,
            'name' => 'Headquarters',
            'code' => 'HQ',
            'path' => '/',
            'is_active' => true,
        ]);
        $nationalUnit->rebuildPath();

        $regions = [];
        foreach (['North Region', 'Centre Region', 'South Region'] as $i => $name) {
            $r = OrgUnit::create([
                'org_level_id' => $regional->id,
                'parent_id' => $nationalUnit->id,
                'name' => $name,
                'code' => strtoupper(substr(str_replace(' ', '', $name), 0, 4)),
                'path' => '/',
                'is_active' => true,
            ]);
            $r->rebuildPath();
            $regions[] = $r;
        }

        $branches = [];
        $branchNames = [
            ['Libreville Nord', 'LBN'], ['Libreville Sud', 'LBS'], ['Ntoum', 'NTM'],
            ['Oyem Est', 'OYE'], ['Oyem Ouest', 'OYO'], ['Bitam', 'BTM'],
            ['Port-Gentil Centre', 'PGC'], ['Port-Gentil Est', 'PGE'], ['Lambaréné', 'LMB'],
        ];
        foreach ($branchNames as $k => [$name, $code]) {
            $region = $regions[$k % 3];
            $b = OrgUnit::create([
                'org_level_id' => $branch->id,
                'parent_id' => $region->id,
                'name' => $name,
                'code' => $code,
                'path' => '/',
                'is_active' => true,
            ]);
            $b->rebuildPath();
            $branches[] = $b;
        }

        // ── Users with different roles ──
        $roles = Role::pluck('id', 'name');

        $leadership = User::factory()->create(['name' => 'Marie-Claire Obiang', 'locale' => 'fr', 'is_active' => true]);
        $leadership->syncRoles(['national_leadership']);

        $treasurer = User::factory()->create(['name' => 'Jean-Baptiste Mba', 'locale' => 'fr', 'is_active' => true]);
        $treasurer->syncRoles(['treasurer']);

        $commsOfficer = User::factory()->create(['name' => 'Sylvie Nzeng', 'locale' => 'fr', 'is_active' => true]);
        $commsOfficer->syncRoles(['communications_officer']);

        // Regional coordinators — one per region
        foreach ($regions as $region) {
            $coordinator = User::factory()->create(['locale' => 'fr', 'is_active' => true]);
            $coordinator->syncRoles(['regional_coordinator']);
            $roleId = $roles['regional_coordinator'];
            RoleAssignment::create(['user_id' => $coordinator->id, 'role_id' => $roleId, 'org_unit_id' => $region->id]);
        }

        // Branch secretaries — one per branch
        foreach ($branches as $branch_unit) {
            $secretary = User::factory()->create(['locale' => 'fr', 'is_active' => true]);
            $secretary->syncRoles(['branch_secretary']);
            $roleId = $roles['branch_secretary'];
            RoleAssignment::create(['user_id' => $secretary->id, 'role_id' => $roleId, 'org_unit_id' => $branch_unit->id]);
        }

        // ── Members: ~15 per branch, mixed statuses ──
        foreach ($branches as $branch_unit) {
            Member::factory()->count(12)->active()->create(['org_unit_id' => $branch_unit->id]);
            Member::factory()->count(2)->applicant()->create(['org_unit_id' => $branch_unit->id]);
            Member::factory()->count(1)->create(['org_unit_id' => $branch_unit->id, 'status' => 'suspended']);
        }

        // ── Dues plans ──
        $monthly = DuesPlan::create([
            'name' => 'Monthly Basic',
            'amount' => 2500,
            'currency' => $currency,
            'period' => 'monthly',
            'applies_to' => 'all',
            'is_active' => true,
        ]);

        $annual = DuesPlan::create([
            'name' => 'Annual Solidarity',
            'amount' => 25000,
            'currency' => $currency,
            'period' => 'yearly',
            'applies_to' => 'all',
            'is_active' => true,
        ]);

        // ── Dues payments: 3 months of history ──
        $activeMembers = Member::withoutGlobalScopes()->where('status', 'active')->inRandomOrder()->limit(60)->get();
        foreach ($activeMembers as $member) {
            DuesPayment::factory()->confirmed()->forPlan($monthly)->create([
                'member_id' => $member->id,
                'confirmed_at' => now()->subMonths(fake()->numberBetween(0, 2))->startOfMonth(),
            ]);
        }
        // A handful of unconfirmed payments
        $someMembers = Member::withoutGlobalScopes()->where('status', 'active')->inRandomOrder()->limit(10)->get();
        foreach ($someMembers as $member) {
            DuesPayment::factory()->recorded()->forPlan($monthly)->create(['member_id' => $member->id]);
        }

        // ── Events ──
        foreach ($branches as $i => $branch_unit) {
            if ($i < 4) {
                Event::factory()->past()->create(['org_unit_id' => $branch_unit->id, 'created_by' => $leadership->id]);
            }
        }
        Event::factory()->count(3)->upcoming()->create(['org_unit_id' => $nationalUnit->id, 'created_by' => $leadership->id]);
        Event::factory()->upcoming()->create(['org_unit_id' => $regions[0]->id, 'created_by' => $leadership->id]);

        // ── Tasks ──
        Task::factory()->count(5)->create(['org_unit_id' => $nationalUnit->id, 'assignee_id' => $leadership->id]);
        Task::factory()->count(3)->overdue()->create(['org_unit_id' => $nationalUnit->id, 'assignee_id' => $treasurer->id]);
        Task::factory()->count(3)->done()->create(['org_unit_id' => $nationalUnit->id, 'assignee_id' => $leadership->id]);
        foreach ($regions as $region) {
            Task::factory()->count(2)->create(['org_unit_id' => $region->id]);
        }

        // ── Campaigns ──
        Campaign::factory()->sms()->sent()->create([
            'org_unit_id' => null,
            'title' => 'Welcome message to all members',
            'body' => 'Bienvenue dans notre parti. Ensemble, nous bâtirons un avenir meilleur.',
            'recipient_count' => Member::withoutGlobalScopes()->where('status', 'active')->count(),
            'created_by' => $commsOfficer->id,
        ]);
        Campaign::factory()->email()->create([
            'org_unit_id' => null,
            'title' => 'Monthly newsletter',
            'status' => 'draft',
            'created_by' => $commsOfficer->id,
        ]);
    }
}

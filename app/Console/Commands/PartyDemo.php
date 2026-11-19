<?php

namespace App\Console\Commands;

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
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

/**
 * Seeds ~10,000 fake members across a 5-level party structure with 12 months of activity.
 * Names use French West African conventions (Faker fr_FR locale).
 * Refuses to run if non-demo members already exist.
 */
class PartyDemo extends Command
{
    protected $signature = 'party:demo {--force : Wipe and re-seed even if demo data exists}';

    protected $description = 'Seed a large realistic demo dataset (~10 000 members, 12 months of activity)';

    private const MEMBER_TARGET = 10_000;

    public function handle(): int
    {
        if (! Setting::get('installed_at')) {
            $this->error('Run party:install first before seeding demo data.');

            return self::FAILURE;
        }

        $existingCount = Member::withoutGlobalScopes()->count();

        if ($existingCount > 0 && ! $this->option('force')) {
            $this->error("Refused: {$existingCount} member record(s) already exist.");
            $this->line('Use --force to wipe existing demo data and re-seed.');

            return self::FAILURE;
        }

        if ($existingCount > 0 && $this->option('force')) {
            $this->warn('Wiping existing data…');
            $this->wipeDemoData();
        }

        Setting::set('is_demo', '1');

        $this->info('Seeding Party CRM demo — ~'.number_format(self::MEMBER_TARGET).' members');
        $this->newLine();

        $bar = $this->output->createProgressBar(8);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %message%');
        $bar->start();

        $bar->setMessage('Building org structure…');
        [$units, $leaves] = $this->buildStructure();
        $bar->advance();

        $bar->setMessage('Creating users & roles…');
        [$leadership, $commsOfficer, $treasurer] = $this->createUsers($units, $leaves);
        $bar->advance();

        $bar->setMessage('Creating members…');
        $this->createMembers($leaves);
        $bar->advance();

        $bar->setMessage('Creating dues plans & payments…');
        $monthly = $this->createDuesPlans($units['national']);
        $this->createDuesPayments($monthly);
        $bar->advance();

        $bar->setMessage('Creating events…');
        $this->createEvents($units, $leaves, $leadership);
        $bar->advance();

        $bar->setMessage('Creating tasks…');
        $this->createTasks($units, $leadership, $treasurer);
        $bar->advance();

        $bar->setMessage('Creating campaigns…');
        $this->createCampaigns($units['national'], $commsOfficer);
        $bar->advance();

        $bar->setMessage('Done.');
        $bar->finish();
        $this->newLine(2);

        $total = Member::withoutGlobalScopes()->count();
        $this->info("Demo seeded: {$total} members across ".OrgUnit::count().' org units.');

        return self::SUCCESS;
    }

    /** @return array{array{national: OrgUnit, regions: Collection, departments: Collection, branches: Collection, sections: Collection}, Collection} */
    private function buildStructure(): array
    {
        // 5 levels: National → Regional → Departmental → Communal → Section
        $levelNational = OrgLevel::firstOrCreate(['depth' => 0], ['name_key' => 'national', 'code_required' => false]);
        $levelRegional = OrgLevel::firstOrCreate(['depth' => 1], ['name_key' => 'regional', 'code_required' => false]);
        $levelDept = OrgLevel::firstOrCreate(['depth' => 2], ['name_key' => 'departmental', 'code_required' => false]);
        $levelCommunal = OrgLevel::firstOrCreate(['depth' => 3], ['name_key' => 'communal', 'code_required' => true]);
        $levelSection = OrgLevel::firstOrCreate(['depth' => 4], ['name_key' => 'section', 'code_required' => true]);

        $national = OrgUnit::create([
            'org_level_id' => $levelNational->id, 'name' => 'Direction Nationale',
            'code' => 'DN', 'path' => '/', 'is_active' => true,
        ]);
        $national->rebuildPath();

        $regionData = [
            ['Région du Centre', 'RC'], ['Région du Nord', 'RN'], ['Région du Sud', 'RS'],
            ['Région de l\'Est', 'RE'], ['Région de l\'Ouest', 'RO'],
        ];
        $regions = collect();
        foreach ($regionData as [$name, $code]) {
            $r = OrgUnit::create([
                'org_level_id' => $levelRegional->id, 'parent_id' => $national->id,
                'name' => $name, 'code' => $code, 'path' => '/', 'is_active' => true,
            ]);
            $r->rebuildPath();
            $regions->push($r);
        }

        $deptNames = ['Département Alfa', 'Département Bravo', 'Département Charlie', 'Département Delta'];
        $departments = collect();
        foreach ($regions as $ri => $region) {
            foreach ($deptNames as $di => $deptBase) {
                $code = strtoupper(substr(str_replace(['Région ', 'de l\'', 'du ', 'de '], '', $region->name), 0, 2)).sprintf('%02d', $di + 1);
                $d = OrgUnit::create([
                    'org_level_id' => $levelDept->id, 'parent_id' => $region->id,
                    'name' => $deptBase.' ('.$region->code.')', 'code' => $code,
                    'path' => '/', 'is_active' => true,
                ]);
                $d->rebuildPath();
                $departments->push($d);
            }
        }

        $communeNames = ['Commune 1', 'Commune 2', 'Commune 3', 'Commune 4', 'Commune 5'];
        $branches = collect();
        foreach ($departments as $dept) {
            foreach ($communeNames as $ci => $communeBase) {
                $code = $dept->code.'C'.($ci + 1);
                $c = OrgUnit::create([
                    'org_level_id' => $levelCommunal->id, 'parent_id' => $dept->id,
                    'name' => $communeBase.' — '.$dept->code, 'code' => $code,
                    'path' => '/', 'is_active' => true,
                ]);
                $c->rebuildPath();
                $branches->push($c);
            }
        }

        $sections = collect();
        foreach ($branches as $commune) {
            foreach (['S1', 'S2'] as $si) {
                $code = $commune->code.$si;
                $s = OrgUnit::create([
                    'org_level_id' => $levelSection->id, 'parent_id' => $commune->id,
                    'name' => 'Section '.$si.' — '.$commune->code, 'code' => $code,
                    'path' => '/', 'is_active' => true,
                ]);
                $s->rebuildPath();
                $sections->push($s);
            }
        }

        $units = compact('national', 'regions', 'departments', 'branches');

        return [$units, $sections];
    }

    /** @return array{User, User, User} */
    private function createUsers(array $units, Collection $sections): array
    {
        $roles = Role::pluck('id', 'name');

        $leadership = User::factory()->create(['name' => 'Aminata Diallo', 'locale' => 'fr', 'is_active' => true]);
        $leadership->syncRoles(['national_leadership']);

        $treasurer = User::factory()->create(['name' => 'Ibrahima Sow', 'locale' => 'fr', 'is_active' => true]);
        $treasurer->syncRoles(['treasurer']);

        $commsOfficer = User::factory()->create(['name' => 'Fatou Ndiaye', 'locale' => 'fr', 'is_active' => true]);
        $commsOfficer->syncRoles(['communications_officer']);

        foreach ($units['regions'] as $region) {
            $u = User::factory()->create(['locale' => 'fr', 'is_active' => true]);
            $u->syncRoles(['regional_coordinator']);
            RoleAssignment::create(['user_id' => $u->id, 'role_id' => $roles['regional_coordinator'], 'org_unit_id' => $region->id]);
        }

        foreach ($units['branches'] as $commune) {
            $u = User::factory()->create(['locale' => 'fr', 'is_active' => true]);
            $u->syncRoles(['branch_secretary']);
            RoleAssignment::create(['user_id' => $u->id, 'role_id' => $roles['branch_secretary'], 'org_unit_id' => $commune->id]);
        }

        return [$leadership, $commsOfficer, $treasurer];
    }

    private function createMembers(Collection $sections): void
    {
        // Target: ~10,000 members; each section gets ~50
        $perSection = (int) ceil(self::MEMBER_TARGET / $sections->count());

        foreach ($sections as $section) {
            $active = (int) round($perSection * 0.80);
            $applicant = (int) round($perSection * 0.10);
            $other = $perSection - $active - $applicant;

            Member::factory()->count($active)->active()->create(['org_unit_id' => $section->id]);
            Member::factory()->count($applicant)->applicant()->create(['org_unit_id' => $section->id]);
            Member::factory()->count($other)->create(['org_unit_id' => $section->id, 'status' => fake()->randomElement(['suspended', 'lapsed', 'resigned'])]);
        }
    }

    private function createDuesPlans(OrgUnit $national): DuesPlan
    {
        $currency = Setting::get('currency', 'XOF');

        $monthly = DuesPlan::create([
            'name' => 'Cotisation mensuelle', 'amount' => 2500, 'currency' => $currency,
            'period' => 'monthly', 'applies_to' => 'all', 'is_active' => true,
        ]);

        DuesPlan::create([
            'name' => 'Cotisation annuelle solidarité', 'amount' => 25000, 'currency' => $currency,
            'period' => 'yearly', 'applies_to' => 'all', 'is_active' => true,
        ]);

        return $monthly;
    }

    private function createDuesPayments(DuesPlan $monthly): void
    {
        // Seed 12 months of confirmed payments for a sample of active members
        $members = Member::withoutGlobalScopes()->where('status', 'active')->inRandomOrder()->limit(3000)->get();

        foreach ($members as $member) {
            $monthsBack = fake()->numberBetween(0, 11);
            DuesPayment::factory()->confirmed()->forPlan($monthly)->create([
                'member_id' => $member->id,
                'confirmed_at' => now()->subMonths($monthsBack)->startOfMonth(),
            ]);
        }

        // Some unconfirmed (recorded only)
        $recent = Member::withoutGlobalScopes()->where('status', 'active')->inRandomOrder()->limit(200)->get();
        foreach ($recent as $member) {
            DuesPayment::factory()->recorded()->forPlan($monthly)->create(['member_id' => $member->id]);
        }
    }

    private function createEvents(array $units, Collection $sections, User $leadership): void
    {
        // National congress (past)
        Event::factory()->past()->create(['org_unit_id' => $units['national']->id, 'created_by' => $leadership->id, 'title' => 'Congrès National']);

        // Regional events
        foreach ($units['regions'] as $region) {
            Event::factory()->past()->create(['org_unit_id' => $region->id, 'created_by' => $leadership->id]);
            Event::factory()->upcoming()->create(['org_unit_id' => $region->id, 'created_by' => $leadership->id]);
        }

        // Upcoming national events
        Event::factory()->count(3)->upcoming()->create(['org_unit_id' => $units['national']->id, 'created_by' => $leadership->id]);

        // Branch events (sample)
        foreach ($units['branches']->random(20) as $commune) {
            Event::factory()->past()->create(['org_unit_id' => $commune->id, 'created_by' => $leadership->id]);
        }
    }

    private function createTasks(array $units, User $leadership, User $treasurer): void
    {
        Task::factory()->count(10)->create(['org_unit_id' => $units['national']->id, 'assignee_id' => $leadership->id]);
        Task::factory()->count(5)->overdue()->create(['org_unit_id' => $units['national']->id, 'assignee_id' => $treasurer->id]);
        Task::factory()->count(8)->done()->create(['org_unit_id' => $units['national']->id, 'assignee_id' => $leadership->id]);

        foreach ($units['regions'] as $region) {
            Task::factory()->count(3)->create(['org_unit_id' => $region->id]);
        }
    }

    private function createCampaigns(OrgUnit $national, User $commsOfficer): void
    {
        $memberCount = Member::withoutGlobalScopes()->where('status', 'active')->count();

        Campaign::factory()->sms()->sent()->create([
            'org_unit_id' => null,
            'title' => 'Message de bienvenue aux adhérents',
            'body' => 'Chers membres, bienvenue dans notre parti. Ensemble nous bâtirons un avenir meilleur pour notre pays.',
            'recipient_count' => $memberCount,
            'created_by' => $commsOfficer->id,
        ]);

        for ($m = 11; $m >= 0; $m--) {
            Campaign::factory()->email()->create([
                'org_unit_id' => null,
                'title' => 'Bulletin mensuel — '.now()->subMonths($m)->translatedFormat('F Y'),
                'status' => $m > 0 ? 'sent' : 'draft',
                'recipient_count' => $m > 0 ? (int) ($memberCount * 0.7) : 0,
                'created_by' => $commsOfficer->id,
            ]);
        }
    }

    private function wipeDemoData(): void
    {
        Member::withoutGlobalScopes()->delete();
        DuesPayment::query()->delete();
        DuesPlan::query()->delete();
        Event::query()->delete();
        Task::query()->delete();
        Campaign::query()->delete();
        OrgUnit::query()->delete();
        User::whereDoesntHave('roles', fn ($q) => $q->where('name', 'national_admin'))->delete();
    }
}

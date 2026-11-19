<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\OrgUnit;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PartyCommandsTest extends TestCase
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

    // ── party:status ─────────────────────────────────────────────────

    public function test_party_status_reports_not_installed_when_missing_settings(): void
    {
        $this->artisan('party:status')
            ->assertFailed()
            ->expectsOutputToContain('Not installed');
    }

    public function test_party_status_shows_party_name_when_installed(): void
    {
        Setting::set('installed_at', now()->toIso8601String());
        Setting::set('party_name', 'Demo Party');
        Setting::set('party_short_name', 'DP');
        Setting::set('membership_prefix', 'DPT');
        Setting::set('locales', 'en');
        Setting::set('currency', 'USD');
        Setting::set('timezone', 'UTC');
        Setting::set('is_demo', '0');

        $this->artisan('party:status')
            ->assertSuccessful()
            ->expectsOutputToContain('Demo Party');
    }

    public function test_party_status_shows_data_counts(): void
    {
        Setting::set('installed_at', now()->toIso8601String());
        Setting::set('party_name', 'Test Party');
        Setting::set('party_short_name', 'TP');
        Setting::set('membership_prefix', 'TPT');
        Setting::set('locales', 'en');
        Setting::set('currency', 'USD');
        Setting::set('timezone', 'UTC');
        Setting::set('is_demo', '0');

        $unit = OrgUnit::factory()->create();
        Member::factory()->count(3)->active()->create(['org_unit_id' => $unit->id]);
        Member::factory()->count(1)->applicant()->create(['org_unit_id' => $unit->id]);

        $this->artisan('party:status')
            ->assertSuccessful()
            ->expectsOutputToContain('Members');
    }

    // ── party:install ─────────────────────────────────────────────────

    public function test_party_install_creates_admin_user_and_settings(): void
    {
        $this->artisan('party:install', [
            '--party-name' => 'Alliance Demo',
            '--short-name' => 'AD',
            '--membership-prefix' => 'ADM',
            '--locales' => 'en,fr',
            '--default-locale' => 'fr',
            '--currency' => 'XOF',
            '--timezone' => 'Africa/Dakar',
            '--admin-name' => 'Admin User',
            '--admin-email' => 'admin@alliance.test',
            '--admin-password' => 'SecurePass2026!',
            '--no-interaction' => true,
        ])->assertSuccessful();

        $this->assertDatabaseHas('settings', ['key' => 'party_name', 'value' => 'Alliance Demo']);
        $this->assertDatabaseHas('users', ['email' => 'admin@alliance.test']);
        $this->assertDatabaseHas('settings', ['key' => 'installed_at']);
    }

    public function test_party_install_refuses_to_run_twice_without_force(): void
    {
        Setting::set('installed_at', now()->toIso8601String());

        $this->artisan('party:install', [
            '--party-name' => 'Second Run',
            '--short-name' => 'SR',
            '--membership-prefix' => 'SRX',
            '--locales' => 'en',
            '--default-locale' => 'en',
            '--currency' => 'USD',
            '--timezone' => 'UTC',
            '--admin-name' => 'Admin',
            '--admin-email' => 'admin@sr.test',
            '--admin-password' => 'SecurePass2026!',
            '--no-interaction' => true,
        ])->assertFailed()
            ->expectsOutputToContain('already been configured');
    }

    public function test_party_install_force_allows_reconfiguration(): void
    {
        Setting::set('installed_at', now()->toIso8601String());

        $this->artisan('party:install', [
            '--party-name' => 'Reconfigured Party',
            '--short-name' => 'RP',
            '--membership-prefix' => 'RPX',
            '--locales' => 'en',
            '--default-locale' => 'en',
            '--currency' => 'EUR',
            '--timezone' => 'Europe/Paris',
            '--admin-name' => 'Admin',
            '--admin-email' => 'admin@rp.test',
            '--admin-password' => 'SecurePass2026!',
            '--force' => true,
            '--no-interaction' => true,
        ])->assertSuccessful();

        $this->assertDatabaseHas('settings', ['key' => 'party_name', 'value' => 'Reconfigured Party']);
    }

    public function test_party_install_with_demo_flag_seeds_demo_data(): void
    {
        $this->artisan('party:install', [
            '--party-name' => 'Demo Alliance',
            '--short-name' => 'DA',
            '--membership-prefix' => 'DAX',
            '--locales' => 'fr',
            '--default-locale' => 'fr',
            '--currency' => 'XOF',
            '--timezone' => 'Africa/Dakar',
            '--admin-name' => 'Admin',
            '--admin-email' => 'admin@da.test',
            '--admin-password' => 'SecurePass2026!',
            '--demo' => true,
            '--no-interaction' => true,
        ])->assertSuccessful();

        // DemoSeeder creates 9 branches × 15 members = 135 members (plus admin)
        $this->assertGreaterThan(50, Member::withoutGlobalScopes()->count());
        $this->assertGreaterThan(5, OrgUnit::count());
    }
}

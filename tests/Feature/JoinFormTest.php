<?php

namespace Tests\Feature;

use App\Livewire\Public\JoinForm;
use App\Models\Member;
use App\Models\OrgLevel;
use App\Models\OrgUnit;
use App\Services\BlindIndexService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class JoinFormTest extends TestCase
{
    use RefreshDatabase;

    private OrgUnit $region;

    private OrgUnit $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $nationalLevel = OrgLevel::factory()->create(['depth' => 0, 'name_key' => 'national']);
        $regionalLevel = OrgLevel::factory()->create(['depth' => 1, 'name_key' => 'regional']);
        $branchLevel = OrgLevel::factory()->create(['depth' => 2, 'name_key' => 'branch']);

        $national = OrgUnit::factory()->create([
            'org_level_id' => $nationalLevel->id,
            'parent_id' => null,
            'name' => 'National',
            'path' => '/',
            'is_active' => true,
        ]);

        $this->region = OrgUnit::factory()->create([
            'org_level_id' => $regionalLevel->id,
            'parent_id' => $national->id,
            'name' => 'North Region',
            'path' => '/north/',
            'is_active' => true,
        ]);

        $this->branch = OrgUnit::factory()->create([
            'org_level_id' => $branchLevel->id,
            'parent_id' => $this->region->id,
            'name' => 'City Branch',
            'path' => '/north/city/',
            'is_active' => true,
        ]);
    }

    public function test_join_page_renders_for_supported_locale(): void
    {
        $this->withoutVite();

        $this->get(route('join', ['locale' => 'en']))
            ->assertOk()
            ->assertSeeLivewire(JoinForm::class);
    }

    public function test_join_page_returns_404_for_unsupported_locale_format(): void
    {
        $this->withoutVite();

        $this->get('/xyz123/join')->assertNotFound();
    }

    public function test_regions_appear_in_dropdown(): void
    {
        $this->withoutVite();

        Livewire::test(JoinForm::class, ['locale' => 'en'])
            ->assertSee($this->region->name);
    }

    public function test_branches_load_after_region_selected(): void
    {
        $this->withoutVite();

        Livewire::test(JoinForm::class, ['locale' => 'en'])
            ->set('regionId', $this->region->id)
            ->assertSee($this->branch->name);
    }

    public function test_branches_reset_when_region_changes(): void
    {
        $this->withoutVite();

        $region2 = OrgUnit::factory()->create([
            'org_level_id' => $this->region->org_level_id,
            'parent_id' => null,
            'name' => 'South Region',
            'path' => '/south/',
            'is_active' => true,
        ]);

        Livewire::test(JoinForm::class, ['locale' => 'en'])
            ->set('regionId', $this->region->id)
            ->set('branchId', $this->branch->id)
            ->set('regionId', $region2->id)
            ->assertSet('branchId', '');
    }

    public function test_valid_submission_creates_applicant(): void
    {
        $this->withoutVite();

        Livewire::test(JoinForm::class, ['locale' => 'en'])
            ->set('firstName', 'Alice')
            ->set('lastName', 'Smith')
            ->set('email', 'alice@example.com')
            ->set('regionId', $this->region->id)
            ->set('branchId', $this->branch->id)
            ->set('consentGiven', true)
            ->call('submit')
            ->assertSet('submitted', true)
            ->assertSet('isDuplicate', false);

        $this->assertDatabaseHas('members', [
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'org_unit_id' => $this->branch->id,
            'status' => 'applicant',
            'consent_channel' => 'web',
        ]);
    }

    public function test_submission_without_branch_uses_region(): void
    {
        $this->withoutVite();

        Livewire::test(JoinForm::class, ['locale' => 'en'])
            ->set('firstName', 'Bob')
            ->set('lastName', 'Jones')
            ->set('regionId', $this->region->id)
            ->set('consentGiven', true)
            ->call('submit')
            ->assertSet('submitted', true);

        $this->assertDatabaseHas('members', [
            'first_name' => 'Bob',
            'org_unit_id' => $this->region->id,
        ]);
    }

    public function test_honeypot_filled_shows_success_without_saving(): void
    {
        $this->withoutVite();

        Livewire::test(JoinForm::class, ['locale' => 'en'])
            ->set('firstName', 'Bot')
            ->set('lastName', 'Spam')
            ->set('website', 'http://spam.example.com')
            ->set('regionId', $this->region->id)
            ->set('consentGiven', true)
            ->call('submit')
            ->assertSet('submitted', true);

        $this->assertDatabaseCount('members', 0);
    }

    public function test_duplicate_email_shows_duplicate_state(): void
    {
        $this->withoutVite();

        $email = 'duplicate@example.com';
        $blindIndex = app(BlindIndexService::class);

        Member::factory()->create([
            'org_unit_id' => $this->branch->id,
            'email' => $email,
            'email_hash' => $blindIndex->compute($email),
        ]);

        Livewire::test(JoinForm::class, ['locale' => 'en'])
            ->set('firstName', 'Alice')
            ->set('lastName', 'Again')
            ->set('email', $email)
            ->set('regionId', $this->region->id)
            ->set('consentGiven', true)
            ->call('submit')
            ->assertSet('submitted', true)
            ->assertSet('isDuplicate', true);

        $this->assertDatabaseCount('members', 1);
    }

    public function test_rate_limiting_blocks_excessive_attempts(): void
    {
        $this->withoutVite();

        RateLimiter::clear('join:127.0.0.1');

        for ($i = 0; $i < 5; $i++) {
            RateLimiter::hit('join:127.0.0.1', 900);
        }

        Livewire::test(JoinForm::class, ['locale' => 'en'])
            ->set('firstName', 'Eve')
            ->set('lastName', 'Hacker')
            ->set('regionId', $this->region->id)
            ->set('consentGiven', true)
            ->call('submit')
            ->assertHasErrors(['rateLimit']);

        RateLimiter::clear('join:127.0.0.1');
    }

    public function test_missing_required_fields_show_validation_errors(): void
    {
        $this->withoutVite();

        Livewire::test(JoinForm::class, ['locale' => 'en'])
            ->call('submit')
            ->assertHasErrors(['firstName', 'lastName', 'regionId', 'consentGiven']);
    }

    public function test_consent_must_be_accepted(): void
    {
        $this->withoutVite();

        Livewire::test(JoinForm::class, ['locale' => 'en'])
            ->set('firstName', 'Alice')
            ->set('lastName', 'Smith')
            ->set('regionId', $this->region->id)
            ->set('consentGiven', false)
            ->call('submit')
            ->assertHasErrors(['consentGiven']);
    }
}

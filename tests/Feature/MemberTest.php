<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTwoFactorAuthenticated;
use App\Jobs\ImportMembersJob;
use App\Models\Member;
use App\Models\MemberSequence;
use App\Models\OrgUnit;
use App\Models\User;
use App\Services\BlindIndexService;
use App\Services\MemberNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MemberTest extends TestCase
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

    private function nationalAdmin(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('national_admin');

        return $user;
    }

    public function test_member_created_as_applicant(): void
    {
        $admin = $this->nationalAdmin();
        $unit = OrgUnit::factory()->create();

        $this->actingAs($admin);

        $member = Member::create([
            'org_unit_id' => $unit->id,
            'first_name' => 'Alice',
            'last_name' => 'Dupont',
            'gender' => 'female',
            'date_of_birth' => '1990-05-15',
            'email' => 'alice@example.com',
            'category' => 'member',
            'status' => 'applicant',
            'created_by' => $admin->id,
        ]);

        $this->assertEquals('applicant', $member->status);
        $this->assertNull($member->membership_no);
    }

    public function test_approve_member_assigns_number_transactionally(): void
    {
        $admin = $this->nationalAdmin();
        $unit = OrgUnit::factory()->create(['code' => 'TU']);

        $this->actingAs($admin);

        $member = Member::create([
            'org_unit_id' => $unit->id,
            'first_name' => 'Bob',
            'last_name' => 'Martin',
            'gender' => 'male',
            'date_of_birth' => '1985-03-20',
            'category' => 'member',
            'status' => 'applicant',
            'created_by' => $admin->id,
        ]);

        $this->assertNull($member->membership_no);

        $member->recordStatusChange('active', 'Approved in test', $admin->id);
        app(MemberNumberService::class)->assign($member);
        $member->refresh();

        $this->assertEquals('active', $member->status);
        $this->assertNotNull($member->membership_no);
        $this->assertStringContainsString('-TU-', $member->membership_no);

        // Sequence is incremented
        $seq = MemberSequence::where('org_unit_id', $unit->id)->first();
        $this->assertEquals(2, $seq->next_seq);
    }

    public function test_three_members_in_two_units_scoping(): void
    {
        $admin = $this->nationalAdmin();
        $unitA = OrgUnit::factory()->create(['code' => 'UA']);
        $unitB = OrgUnit::factory()->create(['code' => 'UB']);

        $this->actingAs($admin);

        $m1 = Member::factory()->create(['org_unit_id' => $unitA->id, 'status' => 'active']);
        $m2 = Member::factory()->create(['org_unit_id' => $unitA->id, 'status' => 'active']);
        $m3 = Member::factory()->create(['org_unit_id' => $unitB->id, 'status' => 'active']);

        // national_admin sees all 3
        $this->assertEquals(3, Member::count());

        // branch_secretary of unitA sees only their 2
        $secretary = User::factory()->create(['is_active' => true]);
        $secretary->assignRole('branch_secretary');
        $role = $secretary->roles->first();
        $secretary->roleAssignments()->create(['role_id' => $role->id, 'org_unit_id' => $unitA->id]);
        $unitA->update(['path' => '/'.$unitA->id.'/']);

        $this->actingAs($secretary);
        $visible = Member::count();
        $this->assertEquals(2, $visible);
    }

    public function test_blind_index_computed_on_save(): void
    {
        $admin = $this->nationalAdmin();
        $unit = OrgUnit::factory()->create();
        $this->actingAs($admin);

        $email = 'test@example.com';
        $member = Member::create([
            'org_unit_id' => $unit->id,
            'first_name' => 'Test',
            'last_name' => 'User',
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'email' => $email,
            'category' => 'member',
            'status' => 'applicant',
            'created_by' => $admin->id,
        ]);

        $bi = app(BlindIndexService::class);
        $this->assertEquals($bi->compute($email), $member->email_hash);
    }

    public function test_generate_pdf_card_redirects_or_returns_pdf(): void
    {
        $admin = $this->nationalAdmin();
        $unit = OrgUnit::factory()->create(['code' => 'CC']);
        $this->actingAs($admin);

        $member = Member::factory()->create([
            'org_unit_id' => $unit->id,
            'membership_no' => 'TEST-CC-000001',
            'status' => 'active',
        ]);

        $response = $this->withoutMiddleware(EnsureTwoFactorAuthenticated::class)
            ->get(route('members.card', $member));
        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_verify_endpoint_returns_valid_for_active_member(): void
    {
        $unit = OrgUnit::factory()->create();
        $member = Member::factory()->create([
            'org_unit_id' => $unit->id,
            'membership_no' => 'VRF-XX-000001',
            'status' => 'active',
        ]);

        $url = URL::signedRoute('members.verify', [
            'locale' => 'en',
            'membership_no' => $member->membership_no,
        ]);

        $response = $this->get($url);
        $response->assertStatus(200);
        $response->assertSee('Valid Member');
        $response->assertSee($member->first_name);
    }

    public function test_verify_endpoint_returns_not_valid_for_applicant(): void
    {
        $unit = OrgUnit::factory()->create();
        $member = Member::factory()->create([
            'org_unit_id' => $unit->id,
            'membership_no' => 'VRF-XX-000002',
            'status' => 'applicant',
        ]);

        $url = URL::signedRoute('members.verify', [
            'locale' => 'en',
            'membership_no' => $member->membership_no,
        ]);

        $response = $this->get($url);
        $response->assertStatus(200);
        $response->assertSee('Not Valid');
    }

    public function test_import_dispatches_job(): void
    {
        Queue::fake();
        Storage::fake('local');

        $admin = $this->nationalAdmin();
        $unit = OrgUnit::factory()->create();
        $this->actingAs($admin);

        $csv = "first_name,last_name,email\nCarlos,Silva,carlos@example.com\n";
        $file = UploadedFile::fake()->createWithContent('members.csv', $csv);
        $path = $file->store('imports', 'local');

        ImportMembersJob::dispatch($path, ['first_name' => 'first_name', 'last_name' => 'last_name', 'email' => 'email'], $unit->id, 'test_job_1', $admin->id);

        Queue::assertPushed(ImportMembersJob::class);
    }

    public function test_import_job_processes_csv_and_skips_duplicates(): void
    {
        Storage::fake('local');

        $admin = $this->nationalAdmin();
        $unit = OrgUnit::factory()->create();
        $this->actingAs($admin);

        // Pre-create a member with the same email to test duplicate skipping
        Member::factory()->create([
            'org_unit_id' => $unit->id,
            'email' => 'dup@example.com',
            'status' => 'active',
        ]);

        $rows = ["first_name,last_name,email\n"];
        for ($i = 1; $i <= 500; $i++) {
            $rows[] = "Member{$i},Last{$i},member{$i}@example.com\n";
        }
        $rows[] = "Dup,User,dup@example.com\n"; // duplicate

        $csv = implode('', $rows);
        $file = UploadedFile::fake()->createWithContent('import.csv', $csv);
        $path = $file->store('imports', 'local');

        $job = new ImportMembersJob(
            $path,
            ['first_name' => 'first_name', 'last_name' => 'last_name', 'email' => 'email'],
            $unit->id,
            'test_job_2',
            $admin->id,
        );

        $job->handle(app(BlindIndexService::class));

        // 500 new + 1 pre-existing = 501 total; duplicate skipped
        $this->assertEquals(501, Member::withoutGlobalScopes()->count());
    }
}

<?php

namespace Tests\Feature;

use App\Livewire\Events\Attendance;
use App\Livewire\Events\Index;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\Member;
use App\Models\OrgUnit;
use App\Models\RoleAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EventTest extends TestCase
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

    private function secretaryForUnit(OrgUnit $unit): User
    {
        $secretary = $this->userWithRole('branch_secretary');
        $role = Role::where('name', 'branch_secretary')->first();
        RoleAssignment::create([
            'user_id' => $secretary->id,
            'role_id' => $role->id,
            'org_unit_id' => $unit->id,
        ]);

        return $secretary;
    }

    public function test_national_admin_can_view_events(): void
    {
        $admin = $this->userWithRole('national_admin');
        $unit = OrgUnit::factory()->create();
        $event = Event::factory()->create(['org_unit_id' => $unit->id, 'title' => 'Annual Congress']);

        $this->actingAs($admin);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->assertSee('Annual Congress');
    }

    public function test_national_admin_can_create_event(): void
    {
        $admin = $this->userWithRole('national_admin');
        $unit = OrgUnit::factory()->create();

        $this->actingAs($admin);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('openCreate')
            ->set('title', 'Branch Meeting')
            ->set('type', 'meeting')
            ->set('orgUnitId', $unit->id)
            ->set('startsAt', now()->addDay()->format('Y-m-d\TH:i'))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('events', ['title' => 'Branch Meeting']);
    }

    public function test_platform_support_cannot_view_events(): void
    {
        $support = $this->userWithRole('platform_support');

        $this->actingAs($support);

        Livewire::actingAs($support)
            ->test(Index::class)
            ->assertForbidden();
    }

    public function test_branch_secretary_can_create_event(): void
    {
        $secretary = $this->userWithRole('branch_secretary');
        $unit = OrgUnit::factory()->create();

        $this->actingAs($secretary);

        Livewire::actingAs($secretary)
            ->test(Index::class)
            ->call('openCreate')
            ->set('title', 'Branch Training')
            ->set('type', 'training')
            ->set('orgUnitId', $unit->id)
            ->set('startsAt', now()->addDay()->format('Y-m-d\TH:i'))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('events', ['title' => 'Branch Training']);
    }

    public function test_event_creation_validates_required_fields(): void
    {
        $admin = $this->userWithRole('national_admin');

        $this->actingAs($admin);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('openCreate')
            ->set('title', '')
            ->set('orgUnitId', '')
            ->set('startsAt', '')
            ->call('save')
            ->assertHasErrors(['title', 'orgUnitId', 'startsAt']);
    }

    // ── Attendance ──────────────────────────────────────────────────

    public function test_secretary_can_mark_member_attended(): void
    {
        $unit = OrgUnit::factory()->create();
        $secretary = $this->secretaryForUnit($unit);
        $event = Event::factory()->create(['org_unit_id' => $unit->id]);
        $member = Member::factory()->create(['org_unit_id' => $unit->id, 'status' => 'active']);
        EventAttendance::factory()->create([
            'event_id' => $event->id,
            'member_id' => $member->id,
            'status' => 'invited',
        ]);

        $this->actingAs($secretary);

        Livewire::actingAs($secretary)
            ->test(Attendance::class, ['event' => $event])
            ->call('markAttended', $member->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('event_attendance', [
            'event_id' => $event->id,
            'member_id' => $member->id,
            'status' => 'attended',
        ]);
    }

    public function test_qr_scan_marks_member_attended(): void
    {
        $unit = OrgUnit::factory()->create();
        $secretary = $this->secretaryForUnit($unit);
        $event = Event::factory()->create(['org_unit_id' => $unit->id]);
        $member = Member::factory()->create([
            'org_unit_id' => $unit->id,
            'status' => 'active',
            'membership_no' => 'ADA-CC-000001',
        ]);

        $this->actingAs($secretary);

        Livewire::actingAs($secretary)
            ->test(Attendance::class, ['event' => $event])
            ->call('handleQrScan', 'ADA-CC-000001')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('event_attendance', [
            'event_id' => $event->id,
            'member_id' => $member->id,
            'status' => 'attended',
        ]);
    }

    public function test_qr_scan_handles_full_verify_url(): void
    {
        $unit = OrgUnit::factory()->create();
        $secretary = $this->secretaryForUnit($unit);
        $event = Event::factory()->create(['org_unit_id' => $unit->id]);
        $member = Member::factory()->create([
            'org_unit_id' => $unit->id,
            'status' => 'active',
            'membership_no' => 'ADA-CC-000002',
        ]);

        $this->actingAs($secretary);

        Livewire::actingAs($secretary)
            ->test(Attendance::class, ['event' => $event])
            ->call('handleQrScan', 'https://example.com/en/verify/ADA-CC-000002')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('event_attendance', [
            'event_id' => $event->id,
            'member_id' => $member->id,
            'status' => 'attended',
        ]);
    }

    public function test_attendance_rate_computed_correctly(): void
    {
        $admin = $this->userWithRole('national_admin');
        $unit = OrgUnit::factory()->create();
        $event = Event::factory()->create(['org_unit_id' => $unit->id]);
        $members = Member::factory()->count(4)->create(['org_unit_id' => $unit->id, 'status' => 'active']);

        EventAttendance::factory()->attended()->create(['event_id' => $event->id, 'member_id' => $members[0]->id]);
        EventAttendance::factory()->attended()->create(['event_id' => $event->id, 'member_id' => $members[1]->id]);
        EventAttendance::factory()->create(['event_id' => $event->id, 'member_id' => $members[2]->id, 'status' => 'absent']);
        EventAttendance::factory()->create(['event_id' => $event->id, 'member_id' => $members[3]->id, 'status' => 'absent']);

        $this->actingAs($admin);
        $this->assertEquals(50.0, $event->fresh()->attendanceRate());
    }
}

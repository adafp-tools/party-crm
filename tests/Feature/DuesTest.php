<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTwoFactorAuthenticated;
use App\Livewire\Dues\Arrears;
use App\Livewire\Dues\ConfirmQueue;
use App\Livewire\Dues\Plans;
use App\Livewire\Dues\RecordPayment;
use App\Models\DuesPayment;
use App\Models\DuesPlan;
use App\Models\Member;
use App\Models\OrgUnit;
use App\Models\User;
use App\Services\DuesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DuesTest extends TestCase
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

    // ── DuesPlan CRUD ───────────────────────────────────────────────

    public function test_national_admin_can_view_dues_plans(): void
    {
        $admin = $this->userWithRole('national_admin');
        $plan = DuesPlan::factory()->create(['name' => 'Monthly Basic']);

        $this->actingAs($admin)
            ->withoutMiddleware(EnsureTwoFactorAuthenticated::class);

        Livewire::actingAs($admin)
            ->test(Plans::class)
            ->assertSee('Monthly Basic');
    }

    public function test_national_admin_can_create_dues_plan(): void
    {
        $admin = $this->userWithRole('national_admin');

        Livewire::actingAs($admin)
            ->test(Plans::class)
            ->call('openCreate')
            ->set('name', 'Annual Membership')
            ->set('amount', '5000')
            ->set('currency', 'ETB')
            ->set('period', 'yearly')
            ->set('appliesTo', 'all')
            ->set('isActive', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('dues_plans', ['name' => 'Annual Membership', 'currency' => 'ETB']);
    }

    public function test_plan_creation_validates_required_fields(): void
    {
        $admin = $this->userWithRole('national_admin');

        Livewire::actingAs($admin)
            ->test(Plans::class)
            ->call('openCreate')
            ->set('name', '')
            ->set('amount', '')
            ->call('save')
            ->assertHasErrors(['name', 'amount']);
    }

    public function test_national_admin_can_edit_dues_plan(): void
    {
        $admin = $this->userWithRole('national_admin');
        $plan = DuesPlan::factory()->create();

        Livewire::actingAs($admin)
            ->test(Plans::class)
            ->call('openEdit', $plan->id)
            ->set('name', 'Updated Plan')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('dues_plans', ['id' => $plan->id, 'name' => 'Updated Plan']);
    }

    public function test_national_admin_can_toggle_plan_active(): void
    {
        $admin = $this->userWithRole('national_admin');
        $plan = DuesPlan::factory()->create(['is_active' => true]);

        Livewire::actingAs($admin)
            ->test(Plans::class)
            ->call('toggleActive', $plan->id);

        $this->assertFalse($plan->fresh()->is_active);
    }

    public function test_non_admin_cannot_create_dues_plan(): void
    {
        // treasurer can view plans but cannot create them
        $treasurer = $this->userWithRole('treasurer');

        Livewire::actingAs($treasurer)
            ->test(Plans::class)
            ->call('openCreate')
            ->assertForbidden();
    }

    // ── RecordPayment ───────────────────────────────────────────────

    public function test_branch_secretary_can_record_cash_payment(): void
    {
        $secretary = $this->userWithRole('branch_secretary');
        $unit = OrgUnit::factory()->create();
        $member = Member::factory()->create([
            'org_unit_id' => $unit->id,
            'status' => 'active',
        ]);

        $this->actingAs($secretary);

        Livewire::actingAs($secretary)
            ->test(RecordPayment::class, ['member' => $member])
            ->set('amount', '500')
            ->set('currency', 'ETB')
            ->set('method', 'cash')
            ->set('receiptNo', 'RCT-001')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('dues_payments', [
            'member_id' => $member->id,
            'amount' => 500,
            'method' => 'cash',
            'receipt_no' => 'RCT-001',
            'status' => 'recorded',
        ]);
    }

    public function test_mobile_money_payment_requires_reference(): void
    {
        $secretary = $this->userWithRole('branch_secretary');
        $unit = OrgUnit::factory()->create();
        $member = Member::factory()->create([
            'org_unit_id' => $unit->id,
            'status' => 'active',
        ]);

        $this->actingAs($secretary);

        Livewire::actingAs($secretary)
            ->test(RecordPayment::class, ['member' => $member])
            ->set('amount', '500')
            ->set('currency', 'ETB')
            ->set('method', 'telebirr')
            ->set('reference', '')
            ->call('save')
            ->assertHasErrors(['reference']);
    }

    public function test_plan_amount_auto_fills_on_selection(): void
    {
        $secretary = $this->userWithRole('branch_secretary');
        $plan = DuesPlan::factory()->create(['amount' => 2500, 'currency' => 'ETB']);
        $unit = OrgUnit::factory()->create();
        $member = Member::factory()->create([
            'org_unit_id' => $unit->id,
            'status' => 'active',
        ]);

        $this->actingAs($secretary);

        Livewire::actingAs($secretary)
            ->test(RecordPayment::class, ['member' => $member])
            ->set('duesPlanId', $plan->id)
            ->assertSet('amount', '2500.00')
            ->assertSet('currency', 'ETB');
    }

    // ── ConfirmQueue ────────────────────────────────────────────────

    public function test_treasurer_can_confirm_payment(): void
    {
        $treasurer = $this->userWithRole('treasurer');
        $unit = OrgUnit::factory()->create();
        $member = Member::factory()->create(['org_unit_id' => $unit->id, 'status' => 'active']);
        $payment = DuesPayment::factory()->recorded()->create(['member_id' => $member->id]);

        $this->actingAs($treasurer);

        Livewire::actingAs($treasurer)
            ->test(ConfirmQueue::class)
            ->call('confirm', $payment->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('dues_payments', [
            'id' => $payment->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_treasurer_can_reject_payment(): void
    {
        $treasurer = $this->userWithRole('treasurer');
        $unit = OrgUnit::factory()->create();
        $member = Member::factory()->create(['org_unit_id' => $unit->id, 'status' => 'active']);
        $payment = DuesPayment::factory()->recorded()->create(['member_id' => $member->id]);

        $this->actingAs($treasurer);

        Livewire::actingAs($treasurer)
            ->test(ConfirmQueue::class)
            ->call('reject', $payment->id, 'Duplicate entry')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('dues_payments', [
            'id' => $payment->id,
            'status' => 'rejected',
            'rejection_reason' => 'Duplicate entry',
        ]);
    }

    public function test_branch_secretary_cannot_confirm_payment(): void
    {
        $secretary = $this->userWithRole('branch_secretary');
        $unit = OrgUnit::factory()->create();
        $member = Member::factory()->create(['org_unit_id' => $unit->id, 'status' => 'active']);
        $payment = DuesPayment::factory()->recorded()->create(['member_id' => $member->id]);

        $this->actingAs($secretary);

        Livewire::actingAs($secretary)
            ->test(ConfirmQueue::class)
            ->call('confirm', $payment->id)
            ->assertForbidden();
    }

    public function test_treasurer_can_bulk_confirm_payments(): void
    {
        $treasurer = $this->userWithRole('treasurer');
        $unit = OrgUnit::factory()->create();
        $members = Member::factory()->count(3)->create(['org_unit_id' => $unit->id, 'status' => 'active']);
        $payments = $members->map(fn ($m) => DuesPayment::factory()->recorded()->create(['member_id' => $m->id]));

        $this->actingAs($treasurer);

        Livewire::actingAs($treasurer)
            ->test(ConfirmQueue::class)
            ->set('selected', $payments->pluck('id')->toArray())
            ->call('bulkConfirm')
            ->assertHasNoErrors();

        foreach ($payments as $payment) {
            $this->assertDatabaseHas('dues_payments', ['id' => $payment->id, 'status' => 'confirmed']);
        }
    }

    // ── DuesService ─────────────────────────────────────────────────

    public function test_dues_service_returns_paid_when_confirmed_this_period(): void
    {
        $admin = $this->userWithRole('national_admin');
        $unit = OrgUnit::factory()->create();
        $member = Member::factory()->create(['org_unit_id' => $unit->id, 'status' => 'active']);
        $plan = DuesPlan::factory()->monthly()->create(['applies_to' => 'all']);
        DuesPayment::factory()->confirmed()->forPlan($plan)->create([
            'member_id' => $member->id,
            'period_from' => now()->startOfMonth(),
            'period_to' => now()->endOfMonth(),
        ]);

        $this->actingAs($admin);

        $service = app(DuesService::class);
        $this->assertEquals('paid', $service->statusFor($member));
    }

    public function test_dues_service_returns_owing_when_no_payments(): void
    {
        $admin = $this->userWithRole('national_admin');
        $unit = OrgUnit::factory()->create();
        $member = Member::factory()->create(['org_unit_id' => $unit->id, 'status' => 'active']);
        DuesPlan::factory()->monthly()->create(['applies_to' => 'all']);

        $this->actingAs($admin);

        $service = app(DuesService::class);
        $this->assertEquals('owing', $service->statusFor($member));
    }

    public function test_dues_service_cache_invalidates_on_forget(): void
    {
        $admin = $this->userWithRole('national_admin');
        $unit = OrgUnit::factory()->create();
        $member = Member::factory()->create(['org_unit_id' => $unit->id, 'status' => 'active']);

        $this->actingAs($admin);

        $service = app(DuesService::class);

        // No plans → exempt, primes cache
        $this->assertEquals('exempt', $service->statusFor($member));

        // Add a plan — without clearing cache the status would still be cached
        DuesPlan::factory()->monthly()->create(['applies_to' => 'all']);

        // Clear cache
        $service->forgetCache($member);

        // Now status re-computes and returns owing
        $this->assertEquals('owing', $service->statusFor($member));
    }

    // ── Arrears ─────────────────────────────────────────────────────

    public function test_arrears_component_renders_for_national_admin(): void
    {
        $admin = $this->userWithRole('national_admin');
        $unit = OrgUnit::factory()->create(['name' => 'Central Branch']);

        $this->actingAs($admin);

        Livewire::actingAs($admin)
            ->test(Arrears::class)
            ->assertOk();
    }

    public function test_platform_support_cannot_view_arrears(): void
    {
        $support = $this->userWithRole('platform_support');

        $this->actingAs($support);

        Livewire::actingAs($support)
            ->test(Arrears::class)
            ->assertForbidden();
    }
}

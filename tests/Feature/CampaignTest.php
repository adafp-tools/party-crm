<?php

namespace Tests\Feature;

use App\Contracts\SmsGateway;
use App\Jobs\DispatchCampaign;
use App\Livewire\Campaigns\Form;
use App\Livewire\Campaigns\Index;
use App\Mail\CampaignMailer;
use App\Models\Campaign;
use App\Models\Member;
use App\Models\OrgUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CampaignTest extends TestCase
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

    public function test_national_leadership_can_view_campaigns(): void
    {
        $leader = $this->userWithRole('national_leadership');
        Campaign::factory()->create(['title' => 'November Rally SMS']);

        $this->actingAs($leader);

        Livewire::actingAs($leader)
            ->test(Index::class)
            ->assertSee('November Rally SMS');
    }

    public function test_platform_support_cannot_view_campaigns(): void
    {
        $support = $this->userWithRole('platform_support');

        $this->actingAs($support);

        Livewire::actingAs($support)
            ->test(Index::class)
            ->assertForbidden();
    }

    public function test_branch_secretary_cannot_view_campaigns(): void
    {
        $secretary = $this->userWithRole('branch_secretary');

        $this->actingAs($secretary);

        Livewire::actingAs($secretary)
            ->test(Index::class)
            ->assertForbidden();
    }

    public function test_officer_can_create_draft_campaign(): void
    {
        $officer = $this->userWithRole('communications_officer');

        $this->actingAs($officer);

        Livewire::actingAs($officer)
            ->test(Form::class)
            ->set('type', 'sms')
            ->set('title', 'Membership drive reminder')
            ->set('body', 'Your membership renewal is due.')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('campaigns', [
            'title' => 'Membership drive reminder',
            'status' => 'draft',
        ]);
    }

    public function test_campaign_validation_requires_type_and_body(): void
    {
        $officer = $this->userWithRole('communications_officer');

        $this->actingAs($officer);

        Livewire::actingAs($officer)
            ->test(Form::class)
            ->set('title', '')
            ->set('body', '')
            ->call('save')
            ->assertHasErrors(['title', 'body']);
    }

    public function test_email_campaign_requires_subject(): void
    {
        $officer = $this->userWithRole('communications_officer');

        $this->actingAs($officer);

        Livewire::actingAs($officer)
            ->test(Form::class)
            ->set('type', 'email')
            ->set('title', 'Newsletter')
            ->set('subject', '')
            ->set('body', 'Hello members.')
            ->call('save')
            ->assertHasErrors(['subject']);
    }

    public function test_dispatch_campaign_job_sends_sms_to_active_members(): void
    {
        $smsMock = $this->mock(SmsGateway::class);
        $smsMock->shouldReceive('send')->twice()->andReturn(true);

        $unit = OrgUnit::factory()->create();
        $campaign = Campaign::factory()->sms()->create([
            'org_unit_id' => $unit->id,
            'body' => 'Join our rally this weekend!',
            'status' => 'sending',
        ]);

        Member::factory()->count(2)->create([
            'org_unit_id' => $unit->id,
            'status' => 'active',
            'phone' => '+2250101010101',
            'sms_opt_out' => false,
        ]);
        // Inactive member — should not receive SMS
        Member::factory()->create(['org_unit_id' => $unit->id, 'status' => 'applicant', 'sms_opt_out' => false]);

        (new DispatchCampaign($campaign))->handle($smsMock);

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'status' => 'sent',
            'recipient_count' => 2,
        ]);
    }

    public function test_dispatch_campaign_respects_sms_opt_out(): void
    {
        $smsMock = $this->mock(SmsGateway::class);
        $smsMock->shouldReceive('send')->once()->andReturn(true);

        $unit = OrgUnit::factory()->create();
        $campaign = Campaign::factory()->sms()->create([
            'org_unit_id' => $unit->id,
            'status' => 'sending',
        ]);

        Member::factory()->create(['org_unit_id' => $unit->id, 'status' => 'active', 'sms_opt_out' => false, 'phone' => '+2250101010101']);
        Member::factory()->create(['org_unit_id' => $unit->id, 'status' => 'active', 'sms_opt_out' => true, 'phone' => '+2250202020202']);

        (new DispatchCampaign($campaign))->handle($smsMock);

        $this->assertDatabaseHas('campaigns', ['id' => $campaign->id, 'recipient_count' => 1]);
    }

    public function test_dispatch_campaign_job_sends_email_to_active_members(): void
    {
        Mail::fake();

        $unit = OrgUnit::factory()->create();
        $campaign = Campaign::factory()->email()->create([
            'org_unit_id' => $unit->id,
            'status' => 'sending',
        ]);

        $member = Member::factory()->create([
            'org_unit_id' => $unit->id,
            'status' => 'active',
            'email' => 'test@example.com',
            'email_opt_out' => false,
        ]);

        $smsMock = $this->mock(SmsGateway::class);

        (new DispatchCampaign($campaign))->handle($smsMock);

        Mail::assertSent(CampaignMailer::class, function ($mail) use ($member) {
            return $mail->hasTo($member->email);
        });
    }

    public function test_dispatch_campaign_respects_email_opt_out(): void
    {
        Mail::fake();

        $unit = OrgUnit::factory()->create();
        $campaign = Campaign::factory()->email()->create([
            'org_unit_id' => $unit->id,
            'status' => 'sending',
        ]);

        Member::factory()->create(['org_unit_id' => $unit->id, 'status' => 'active', 'email_opt_out' => true, 'email' => 'optout@example.com']);

        $smsMock = $this->mock(SmsGateway::class);
        (new DispatchCampaign($campaign))->handle($smsMock);

        Mail::assertNothingSent();
        $this->assertDatabaseHas('campaigns', ['id' => $campaign->id, 'recipient_count' => 0]);
    }

    public function test_send_campaign_queues_job(): void
    {
        Queue::fake();

        $officer = $this->userWithRole('communications_officer');

        $this->actingAs($officer);

        Livewire::actingAs($officer)
            ->test(Form::class)
            ->set('type', 'sms')
            ->set('title', 'Urgent notice')
            ->set('body', 'Emergency meeting tonight.')
            ->call('send')
            ->assertHasNoErrors();

        Queue::assertPushed(DispatchCampaign::class);
    }
}

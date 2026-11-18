<?php

namespace Tests\Feature;

use App\Jobs\SendOverdueTasksDigest;
use App\Livewire\Tasks\Form;
use App\Livewire\Tasks\Index;
use App\Mail\OverdueTasksDigest;
use App\Models\OrgUnit;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TaskTest extends TestCase
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

    public function test_national_admin_can_view_tasks(): void
    {
        $admin = $this->userWithRole('national_admin');
        $unit = OrgUnit::factory()->create();
        Task::factory()->create(['org_unit_id' => $unit->id, 'title' => 'Review membership list']);

        $this->actingAs($admin);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->assertSee('Review membership list');
    }

    public function test_platform_support_cannot_view_tasks(): void
    {
        $support = $this->userWithRole('platform_support');

        $this->actingAs($support);

        Livewire::actingAs($support)
            ->test(Index::class)
            ->assertForbidden();
    }

    public function test_branch_secretary_can_create_task(): void
    {
        $secretary = $this->userWithRole('branch_secretary');
        $assignee = $this->userWithRole('branch_secretary');
        $unit = OrgUnit::factory()->create();

        $this->actingAs($secretary);

        Livewire::actingAs($secretary)
            ->test(Form::class)
            ->set('orgUnitId', $unit->id)
            ->set('title', 'Follow up with applicant')
            ->set('assigneeId', $assignee->id)
            ->set('dueAt', now()->addDays(3)->format('Y-m-d\TH:i'))
            ->set('status', 'pending')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tasks', ['title' => 'Follow up with applicant']);
    }

    public function test_task_validation_requires_title_and_unit(): void
    {
        $admin = $this->userWithRole('national_admin');

        $this->actingAs($admin);

        Livewire::actingAs($admin)
            ->test(Form::class)
            ->set('title', '')
            ->set('orgUnitId', '')
            ->call('save')
            ->assertHasErrors(['title', 'orgUnitId']);
    }

    public function test_assignee_can_mark_task_done(): void
    {
        $assignee = $this->userWithRole('branch_secretary');
        $unit = OrgUnit::factory()->create();
        $task = Task::factory()->pending()->create([
            'org_unit_id' => $unit->id,
            'assignee_id' => $assignee->id,
        ]);

        $this->actingAs($assignee);

        Livewire::actingAs($assignee)
            ->test(Index::class)
            ->call('updateStatus', $task->id, 'done')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => 'done']);
    }

    public function test_overdue_tasks_are_highlighted(): void
    {
        $admin = $this->userWithRole('national_admin');
        $unit = OrgUnit::factory()->create();
        Task::factory()->overdue()->create(['org_unit_id' => $unit->id, 'title' => 'Past due task']);

        $this->actingAs($admin);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->assertSee('Past due task')
            ->assertSee('OVERDUE');
    }

    public function test_task_is_overdue_returns_false_when_done(): void
    {
        $unit = OrgUnit::factory()->create();
        $task = Task::factory()->done()->create([
            'org_unit_id' => $unit->id,
            'due_at' => now()->subDays(3),
        ]);

        $this->assertFalse($task->isOverdue());
    }

    public function test_overdue_digest_job_dispatches(): void
    {
        Queue::fake();

        SendOverdueTasksDigest::dispatch();

        Queue::assertPushed(SendOverdueTasksDigest::class);
    }

    public function test_overdue_digest_sends_email_to_assignee(): void
    {
        Mail::fake();

        $assignee = $this->userWithRole('branch_secretary');
        $unit = OrgUnit::factory()->create();
        Task::factory()->overdue()->create([
            'org_unit_id' => $unit->id,
            'assignee_id' => $assignee->id,
            'digest_opt_out' => false,
        ]);

        (new SendOverdueTasksDigest)->handle();

        Mail::assertSent(OverdueTasksDigest::class, function ($mail) use ($assignee) {
            return $mail->hasTo($assignee->email);
        });
    }

    public function test_digest_skips_opted_out_tasks(): void
    {
        Mail::fake();

        $assignee = $this->userWithRole('branch_secretary');
        $unit = OrgUnit::factory()->create();
        Task::factory()->overdue()->create([
            'org_unit_id' => $unit->id,
            'assignee_id' => $assignee->id,
            'digest_opt_out' => true,
        ]);

        (new SendOverdueTasksDigest)->handle();

        Mail::assertNothingSent();
    }
}

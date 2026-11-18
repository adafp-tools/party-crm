<?php

namespace App\Livewire\Tasks;

use App\Models\OrgUnit;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Form extends Component
{
    public ?Task $task = null;

    public string $orgUnitId = '';

    public string $title = '';

    public string $details = '';

    public string $assigneeId = '';

    public string $dueAt = '';

    public string $status = 'pending';

    public function mount(?Task $task = null): void
    {
        if ($task && $task->exists) {
            Gate::authorize('update', $task);
            $this->task = $task;
            $this->orgUnitId = $task->org_unit_id;
            $this->title = $task->title;
            $this->details = $task->details ?? '';
            $this->assigneeId = $task->assignee_id ?? '';
            $this->dueAt = $task->due_at ? $task->due_at->format('Y-m-d\TH:i') : '';
            $this->status = $task->status;
        } else {
            Gate::authorize('create', Task::class);
        }
    }

    public function getOrgUnitsProperty()
    {
        return OrgUnit::where('is_active', true)->orderBy('name')->get();
    }

    public function getUsersProperty()
    {
        return User::where('is_active', true)->orderBy('name')->get();
    }

    public function save(): void
    {
        $this->validate([
            'orgUnitId' => 'required|exists:org_units,id',
            'title' => 'required|string|max:300',
            'details' => 'nullable|string|max:5000',
            'assigneeId' => 'nullable|exists:users,id',
            'dueAt' => 'nullable|date',
            'status' => 'required|in:pending,in_progress,done,cancelled',
        ]);

        $data = [
            'org_unit_id' => $this->orgUnitId,
            'title' => $this->title,
            'details' => $this->details ?: null,
            'assignee_id' => $this->assigneeId ?: null,
            'due_at' => $this->dueAt ?: null,
            'status' => $this->status,
        ];

        if ($this->task) {
            $this->task->update(array_merge($data, ['updated_by' => auth()->id()]));
            session()->flash('success', __('Task updated.'));
        } else {
            Task::create(array_merge($data, ['created_by' => auth()->id()]));
            session()->flash('success', __('Task created.'));
        }

        $this->redirectRoute('tasks.index');
    }

    public function render()
    {
        return view('livewire.tasks.form', [
            'orgUnits' => $this->orgUnits,
            'users' => $this->users,
        ]);
    }
}

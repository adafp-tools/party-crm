<?php

namespace App\Livewire\Tasks;

use App\Models\OrgUnit;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $filterStatus = '';

    public string $filterUnit = '';

    public bool $myTasksOnly = false;

    public function mount(): void
    {
        Gate::authorize('viewAny', Task::class);
    }

    public function getTasksProperty()
    {
        return Task::with(['orgUnit', 'assignee', 'relatedMember'])
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterUnit, fn ($q) => $q->where('org_unit_id', $this->filterUnit))
            ->when($this->myTasksOnly, fn ($q) => $q->where('assignee_id', auth()->id()))
            ->orderByRaw("CASE WHEN due_at IS NOT NULL AND due_at < datetime('now') AND status NOT IN ('done','cancelled') THEN 0 ELSE 1 END")
            ->orderBy('due_at')
            ->paginate(30);
    }

    public function getOrgUnitsProperty()
    {
        return OrgUnit::where('is_active', true)->orderBy('name')->get();
    }

    public function getUsersProperty()
    {
        return User::where('is_active', true)->orderBy('name')->get();
    }

    public function updateStatus(string $id, string $status): void
    {
        $task = Task::withoutGlobalScopes()->findOrFail($id);
        Gate::authorize('update', $task);
        $task->update(['status' => $status, 'updated_by' => auth()->id()]);
    }

    public function delete(string $id): void
    {
        $task = Task::withoutGlobalScopes()->findOrFail($id);
        Gate::authorize('delete', $task);
        $task->delete();
        session()->flash('success', __('Task deleted.'));
    }

    public function render()
    {
        return view('livewire.tasks.index', [
            'tasks' => $this->tasks,
            'orgUnits' => $this->orgUnits,
            'users' => $this->users,
        ]);
    }
}

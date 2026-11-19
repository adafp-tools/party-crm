<?php

namespace App\Livewire\Reports;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class AuditViewer extends Component
{
    use WithPagination;

    public string $filterUser = '';

    public string $filterAction = '';

    public string $filterDateFrom = '';

    public string $filterDateTo = '';

    public function mount(): void
    {
        Gate::authorize('viewAuditLog');
    }

    public function updatingFilterUser(): void
    {
        $this->resetPage();
    }

    public function updatingFilterAction(): void
    {
        $this->resetPage();
    }

    public function updatingFilterDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingFilterDateTo(): void
    {
        $this->resetPage();
    }

    /** @return LengthAwarePaginator */
    public function getLogsProperty()
    {
        $query = AuditLog::query()
            ->with('user:id,name')
            ->orderByDesc('created_at');

        if ($this->filterUser !== '') {
            $query->where('user_id', $this->filterUser);
        }

        if ($this->filterAction !== '') {
            $query->where('action', 'like', '%'.$this->filterAction.'%');
        }

        if ($this->filterDateFrom !== '') {
            $query->whereDate('created_at', '>=', $this->filterDateFrom);
        }

        if ($this->filterDateTo !== '') {
            $query->whereDate('created_at', '<=', $this->filterDateTo);
        }

        return $query->paginate(50);
    }

    /** @return Collection */
    public function getUsersProperty()
    {
        return User::query()->orderBy('name')->get(['id', 'name']);
    }

    public function render(): View
    {
        return view('livewire.reports.audit-viewer', [
            'logs' => $this->logs,
            'users' => $this->users,
        ]);
    }
}

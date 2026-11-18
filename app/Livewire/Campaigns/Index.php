<?php

namespace App\Livewire\Campaigns;

use App\Models\Campaign;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $filterType = '';

    public string $filterStatus = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Campaign::class);
    }

    public function getCampaignsProperty()
    {
        return Campaign::with(['orgUnit', 'createdBy'])
            ->when($this->filterType, fn ($q) => $q->where('type', $this->filterType))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->orderByDesc('created_at')
            ->paginate(25);
    }

    public function delete(string $id): void
    {
        $campaign = Campaign::findOrFail($id);
        Gate::authorize('delete', $campaign);
        $campaign->delete();
        session()->flash('success', __('Campaign deleted.'));
    }

    public function render()
    {
        return view('livewire.campaigns.index', [
            'campaigns' => $this->campaigns,
        ]);
    }
}

<?php

namespace App\Livewire\Members;

use App\Models\DataRequest;
use App\Models\Member;
use App\Services\MemberNumberService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Show extends Component
{
    public Member $member;

    public string $activeTab = 'profile';

    public string $newInteractionType = 'call';

    public string $newInteractionSummary = '';

    public string $newInteractionDate = '';

    public string $approvalReason = '';

    public string $newDataRequestType = 'access';

    public string $newDataRequestNotes = '';

    public function mount(Member $member): void
    {
        $this->authorize('view', $member);
        $this->member = $member;
        $this->newInteractionDate = now()->format('Y-m-d\TH:i');
    }

    public function approve(): void
    {
        $this->authorize('update', $this->member);

        if ($this->member->status !== 'applicant') {
            session()->flash('error', __('Only applicants can be approved.'));

            return;
        }

        $this->member->recordStatusChange('active', $this->approvalReason ?: null);
        app(MemberNumberService::class)->assign($this->member);
        $this->member->refresh();
        $this->approvalReason = '';
        session()->flash('success', __('Member approved and membership number assigned.'));
    }

    public function changeStatus(string $newStatus): void
    {
        $this->authorize('update', $this->member);
        $this->member->recordStatusChange($newStatus);
        $this->member->refresh();
    }

    public function logInteraction(): void
    {
        $this->authorize('update', $this->member);
        $this->validate([
            'newInteractionType' => 'required|in:call,meeting,visit,sms,email,other',
            'newInteractionSummary' => 'nullable|string|max:1000',
            'newInteractionDate' => 'required|date',
        ]);

        $this->member->interactions()->create([
            'type' => $this->newInteractionType,
            'summary' => $this->newInteractionSummary ?: null,
            'occurred_at' => $this->newInteractionDate,
            'by_user_id' => auth()->id(),
        ]);

        $this->newInteractionSummary = '';
        $this->newInteractionDate = now()->format('Y-m-d\TH:i');
        $this->member->refresh();
        session()->flash('success', __('Interaction logged.'));
    }

    public function createDataRequest(): void
    {
        Gate::authorize('manageDataRequest');

        $this->validate([
            'newDataRequestType' => ['required', 'in:access,correction,deletion'],
        ]);

        DataRequest::create([
            'member_id' => $this->member->id,
            'type' => $this->newDataRequestType,
            'notes' => $this->newDataRequestNotes ?: null,
        ]);

        $this->newDataRequestNotes = '';
        session()->flash('success', __('Data request created.'));
    }

    /** @return Collection<int, DataRequest> */
    public function getDataRequestsProperty(): Collection
    {
        return DataRequest::where('member_id', $this->member->id)
            ->with('handler:id,name')
            ->orderByDesc('created_at')
            ->get();
    }

    public function render()
    {
        return view('livewire.members.show', [
            'dataRequests' => $this->dataRequests,
        ]);
    }
}

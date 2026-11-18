<?php

namespace App\Livewire\Members;

use App\Models\Member;
use App\Services\MemberNumberService;
use Livewire\Component;

class Show extends Component
{
    public Member $member;

    public string $activeTab = 'profile';

    public string $newInteractionType = 'call';

    public string $newInteractionSummary = '';

    public string $newInteractionDate = '';

    public string $approvalReason = '';

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

    public function render()
    {
        return view('livewire.members.show');
    }
}

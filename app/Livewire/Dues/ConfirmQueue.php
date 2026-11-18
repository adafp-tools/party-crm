<?php

namespace App\Livewire\Dues;

use App\Models\DuesPayment;
use App\Models\Member;
use App\Services\DuesService;
use Livewire\Component;
use Livewire\WithPagination;

class ConfirmQueue extends Component
{
    use WithPagination;

    public string $filterMethod = '';

    public array $selected = [];

    public string $bulkReason = '';

    public bool $showBulkConfirm = false;

    public function mount(): void
    {
        $this->authorize('viewAny', DuesPayment::class);
    }

    public function getPaymentsProperty()
    {
        return DuesPayment::with(['member', 'duesPlan', 'recordedBy'])
            ->recorded()
            ->when($this->filterMethod, fn ($q) => $q->where('method', $this->filterMethod))
            ->orderBy('created_at')
            ->paginate(50);
    }

    public function confirm(string $id): void
    {
        $payment = DuesPayment::findOrFail($id);
        $this->authorize('update', $payment);
        $payment->update([
            'status' => 'confirmed',
            'confirmed_by' => auth()->id(),
            'confirmed_at' => now(),
        ]);
        app(DuesService::class)->forgetCache(Member::withoutGlobalScopes()->find($payment->member_id));
        session()->flash('success', __('Payment confirmed.'));
    }

    public function reject(string $id, string $reason = ''): void
    {
        $payment = DuesPayment::findOrFail($id);
        $this->authorize('update', $payment);
        $payment->update([
            'status' => 'rejected',
            'rejection_reason' => $reason ?: null,
        ]);
        app(DuesService::class)->forgetCache(Member::withoutGlobalScopes()->find($payment->member_id));
        session()->flash('success', __('Payment rejected.'));
    }

    public function bulkConfirm(): void
    {
        $this->authorize('viewAny', DuesPayment::class);
        $this->validate(['bulkReason' => 'nullable|string|max:500']);

        $payments = DuesPayment::whereIn('id', $this->selected)->recorded()->get();
        foreach ($payments as $payment) {
            $payment->update([
                'status' => 'confirmed',
                'confirmed_by' => auth()->id(),
                'confirmed_at' => now(),
            ]);
            app(DuesService::class)->forgetCache(Member::withoutGlobalScopes()->find($payment->member_id));
        }

        $this->selected = [];
        $this->bulkReason = '';
        $this->showBulkConfirm = false;
        session()->flash('success', __(':n payments confirmed.', ['n' => $payments->count()]));
    }

    public function render()
    {
        return view('livewire.dues.confirm-queue', ['payments' => $this->payments]);
    }
}

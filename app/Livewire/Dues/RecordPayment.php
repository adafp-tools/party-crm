<?php

namespace App\Livewire\Dues;

use App\Models\DuesPayment;
use App\Models\DuesPlan;
use App\Models\Member;
use App\Services\DuesService;
use Livewire\Component;

class RecordPayment extends Component
{
    public ?Member $member = null;

    public string $memberId = '';

    public string $memberSearch = '';

    public string $duesPlanId = '';

    public string $amount = '';

    public string $currency = '';

    public string $method = 'cash';

    public string $reference = '';

    public string $receiptNo = '';

    public string $periodFrom = '';

    public string $periodTo = '';

    public function mount(?Member $member = null): void
    {
        $this->authorize('create', DuesPayment::class);
        if ($member && $member->exists) {
            $this->member = $member;
            $this->memberId = $member->id;
        }
        $this->currency = config('app.currency', 'USD');
        $this->periodFrom = now()->startOfMonth()->toDateString();
        $this->periodTo = now()->endOfMonth()->toDateString();
    }

    public function getPlansProperty()
    {
        return DuesPlan::active()->orderBy('name')->get();
    }

    public function updatedDuesPlanId(): void
    {
        $plan = DuesPlan::find($this->duesPlanId);
        if ($plan) {
            $this->amount = (string) $plan->amount;
            $this->currency = $plan->currency;
        }
    }

    public function save(): void
    {
        $requiresReference = in_array($this->method, ['telebirr', 'wave', 'orange_money', 'bank']);
        $requiresReceipt = $this->method === 'cash';

        $this->validate([
            'memberId' => 'required|exists:members,id',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|max:10',
            'method' => 'required|in:cash,telebirr,wave,orange_money,bank,other',
            'reference' => $requiresReference ? 'required|string|max:100' : 'nullable|string|max:100',
            'receiptNo' => $requiresReceipt ? 'required|string|max:100' : 'nullable|string|max:100',
            'periodFrom' => 'nullable|date',
            'periodTo' => 'nullable|date|after_or_equal:periodFrom',
        ]);

        $payment = DuesPayment::create([
            'member_id' => $this->memberId,
            'dues_plan_id' => $this->duesPlanId ?: null,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'method' => $this->method,
            'reference' => $this->reference ?: null,
            'receipt_no' => $this->receiptNo ?: null,
            'period_from' => $this->periodFrom ?: null,
            'period_to' => $this->periodTo ?: null,
            'status' => 'recorded',
            'recorded_by' => auth()->id(),
        ]);

        app(DuesService::class)->forgetCache(Member::withoutGlobalScopes()->find($this->memberId));

        session()->flash('success', __('Payment recorded.'));

        if ($this->member) {
            $this->redirectRoute('members.show', $this->member);
        } else {
            $this->reset(['memberId', 'memberSearch', 'duesPlanId', 'amount', 'reference', 'receiptNo']);
        }
    }

    public function render()
    {
        return view('livewire.dues.record-payment', ['plans' => $this->plans]);
    }
}

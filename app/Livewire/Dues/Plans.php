<?php

namespace App\Livewire\Dues;

use App\Models\DuesPlan;
use Livewire\Component;

class Plans extends Component
{
    public bool $showForm = false;

    public ?string $editId = null;

    public string $name = '';

    public string $amount = '';

    public string $currency = '';

    public string $period = 'monthly';

    public string $appliesTo = 'all';

    public bool $isActive = true;

    public function mount(): void
    {
        $this->authorize('viewAny', DuesPlan::class);
        $this->currency = config('app.currency', 'USD');
    }

    public function getPlansProperty()
    {
        return DuesPlan::orderBy('name')->get();
    }

    public function openCreate(): void
    {
        $this->authorize('create', DuesPlan::class);
        $this->reset(['editId', 'name', 'amount', 'period', 'appliesTo', 'isActive']);
        $this->currency = config('app.currency', 'USD');
        $this->isActive = true;
        $this->showForm = true;
    }

    public function openEdit(string $id): void
    {
        $plan = DuesPlan::findOrFail($id);
        $this->authorize('update', $plan);
        $this->editId = $id;
        $this->name = $plan->name;
        $this->amount = (string) $plan->amount;
        $this->currency = $plan->currency;
        $this->period = $plan->period;
        $this->appliesTo = $plan->applies_to;
        $this->isActive = $plan->is_active;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:200',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|max:10',
            'period' => 'required|in:monthly,yearly,one_off',
            'appliesTo' => 'required|in:all,member,supporter,youth_wing,women_wing',
        ]);

        $data = [
            'name' => $this->name,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'period' => $this->period,
            'applies_to' => $this->appliesTo,
            'is_active' => $this->isActive,
        ];

        if ($this->editId) {
            DuesPlan::findOrFail($this->editId)->update($data);
            session()->flash('success', __('Dues plan updated.'));
        } else {
            DuesPlan::create($data);
            session()->flash('success', __('Dues plan created.'));
        }

        $this->showForm = false;
    }

    public function toggleActive(string $id): void
    {
        $plan = DuesPlan::findOrFail($id);
        $this->authorize('update', $plan);
        $plan->update(['is_active' => ! $plan->is_active]);
    }

    public function render()
    {
        return view('livewire.dues.plans', ['plans' => $this->plans]);
    }
}

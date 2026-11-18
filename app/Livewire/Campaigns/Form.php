<?php

namespace App\Livewire\Campaigns;

use App\Jobs\DispatchCampaign;
use App\Models\Campaign;
use App\Models\OrgUnit;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Form extends Component
{
    public ?Campaign $campaign = null;

    public string $type = 'sms';

    public string $title = '';

    public string $subject = '';

    public string $orgUnitId = '';

    public string $body = '';

    public function mount(?Campaign $campaign = null): void
    {
        if ($campaign && $campaign->exists) {
            Gate::authorize('update', $campaign);
            $this->campaign = $campaign;
            $this->type = $campaign->type;
            $this->title = $campaign->title;
            $this->subject = $campaign->subject ?? '';
            $this->orgUnitId = $campaign->org_unit_id ?? '';
            $this->body = $campaign->body;
        } else {
            Gate::authorize('create', Campaign::class);
        }
    }

    public function getOrgUnitsProperty()
    {
        return OrgUnit::where('is_active', true)->orderBy('name')->get();
    }

    public function save(): void
    {
        $this->validate($this->rules());

        $data = [
            'type' => $this->type,
            'title' => $this->title,
            'subject' => $this->type === 'email' ? ($this->subject ?: null) : null,
            'org_unit_id' => $this->orgUnitId ?: null,
            'body' => $this->body,
        ];

        if ($this->campaign) {
            $this->campaign->update(array_merge($data, ['updated_by' => auth()->id()]));
            session()->flash('success', __('Campaign saved.'));
        } else {
            $this->campaign = Campaign::create(array_merge($data, [
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]));
            session()->flash('success', __('Campaign saved as draft.'));
        }

        $this->redirectRoute('campaigns.index');
    }

    public function send(): void
    {
        $this->validate($this->rules());

        $data = [
            'type' => $this->type,
            'title' => $this->title,
            'subject' => $this->type === 'email' ? ($this->subject ?: null) : null,
            'org_unit_id' => $this->orgUnitId ?: null,
            'body' => $this->body,
        ];

        if ($this->campaign) {
            Gate::authorize('send', $this->campaign);
            $this->campaign->update(array_merge($data, ['updated_by' => auth()->id()]));
        } else {
            Gate::authorize('create', Campaign::class);
            $this->campaign = Campaign::create(array_merge($data, [
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]));
            Gate::authorize('send', $this->campaign);
        }

        $this->campaign->update(['status' => 'sending', 'updated_by' => auth()->id()]);

        DispatchCampaign::dispatch($this->campaign);

        session()->flash('success', __('Campaign is being sent.'));
        $this->redirectRoute('campaigns.index');
    }

    private function rules(): array
    {
        return [
            'type' => 'required|in:sms,email',
            'title' => 'required|string|max:200',
            'subject' => $this->type === 'email' ? 'required|string|max:300' : 'nullable',
            'orgUnitId' => 'nullable|exists:org_units,id',
            'body' => 'required|string|max:'.($this->type === 'sms' ? '160' : '10000'),
        ];
    }

    public function render()
    {
        return view('livewire.campaigns.form', [
            'orgUnits' => $this->orgUnits,
        ]);
    }
}

<?php

namespace App\Livewire\Events;

use App\Models\Event;
use App\Models\OrgUnit;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $filterType = '';

    public string $filterUnit = '';

    public string $search = '';

    // Form fields
    public bool $showForm = false;

    public ?string $editId = null;

    public string $title = '';

    public string $type = 'meeting';

    public string $orgUnitId = '';

    public string $startsAt = '';

    public string $endsAt = '';

    public string $locationText = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Event::class);
    }

    public function getEventsProperty()
    {
        return Event::with(['orgUnit'])
            ->when($this->filterType, fn ($q) => $q->where('type', $this->filterType))
            ->when($this->filterUnit, fn ($q) => $q->where('org_unit_id', $this->filterUnit))
            ->when($this->search, fn ($q) => $q->where('title', 'like', '%'.$this->search.'%'))
            ->orderByDesc('starts_at')
            ->paginate(25);
    }

    public function getOrgUnitsProperty()
    {
        return OrgUnit::where('is_active', true)->orderBy('name')->get();
    }

    public function openCreate(): void
    {
        Gate::authorize('create', Event::class);
        $this->reset(['editId', 'title', 'type', 'orgUnitId', 'startsAt', 'endsAt', 'locationText']);
        $this->type = 'meeting';
        $this->startsAt = now()->addDay()->format('Y-m-d\TH:i');
        $this->showForm = true;
    }

    public function openEdit(string $id): void
    {
        $event = Event::findOrFail($id);
        Gate::authorize('update', $event);
        $this->editId = $id;
        $this->title = $event->title;
        $this->type = $event->type;
        $this->orgUnitId = $event->org_unit_id;
        $this->startsAt = $event->starts_at->format('Y-m-d\TH:i');
        $this->endsAt = $event->ends_at ? $event->ends_at->format('Y-m-d\TH:i') : '';
        $this->locationText = $event->location_text ?? '';
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'title' => 'required|string|max:300',
            'type' => 'required|in:meeting,rally,training,congress,other',
            'orgUnitId' => 'required|exists:org_units,id',
            'startsAt' => 'required|date',
            'endsAt' => 'nullable|date|after_or_equal:startsAt',
            'locationText' => 'nullable|string|max:500',
        ]);

        $data = [
            'title' => $this->title,
            'type' => $this->type,
            'org_unit_id' => $this->orgUnitId,
            'starts_at' => $this->startsAt,
            'ends_at' => $this->endsAt ?: null,
            'location_text' => $this->locationText ?: null,
        ];

        if ($this->editId) {
            $event = Event::findOrFail($this->editId);
            Gate::authorize('update', $event);
            $event->update(array_merge($data, ['updated_by' => auth()->id()]));
            session()->flash('success', __('Event updated.'));
        } else {
            Gate::authorize('create', Event::class);
            Event::create(array_merge($data, ['created_by' => auth()->id()]));
            session()->flash('success', __('Event created.'));
        }

        $this->showForm = false;
    }

    public function delete(string $id): void
    {
        $event = Event::findOrFail($id);
        Gate::authorize('delete', $event);
        $event->delete();
        session()->flash('success', __('Event deleted.'));
    }

    public function render()
    {
        return view('livewire.events.index', [
            'events' => $this->events,
            'orgUnits' => $this->orgUnits,
        ]);
    }
}

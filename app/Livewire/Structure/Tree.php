<?php

namespace App\Livewire\Structure;

use App\Models\OrgLevel;
use App\Models\OrgUnit;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Tree extends Component
{
    public string $search = '';

    public bool $showModal = false;

    public function mount(): void
    {
        $this->authorize('viewAny', OrgUnit::class);
    }

    public string $modalMode = 'create';

    public ?string $editingUnitId = null;

    public ?string $parentId = null;

    public string $unitName = '';

    public string $unitCode = '';

    public string $unitOrgLevelId = '';

    public ?string $moveToParentId = null;

    public string $confirmDeactivateId = '';

    #[Computed]
    public function tree(): array
    {
        $query = OrgUnit::with(['orgLevel', 'parent'])
            ->withCount('members')
            ->orderBy('path');

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('code', 'like', '%'.$this->search.'%');
            });
        }

        $flat = $query->get()->all();

        return OrgUnit::buildTree($flat, null);
    }

    #[Computed]
    public function orgLevels(): Collection
    {
        return OrgLevel::orderBy('depth')->get();
    }

    public function openCreateModal(?string $parentId = null): void
    {
        $this->authorize('create', OrgUnit::class);

        $this->reset(['unitName', 'unitCode', 'unitOrgLevelId', 'editingUnitId']);
        $this->parentId = $parentId;
        $this->modalMode = 'create';
        $this->showModal = true;
    }

    public function openEditModal(string $unitId): void
    {
        $unit = OrgUnit::findOrFail($unitId);
        $this->authorize('update', $unit);

        $this->editingUnitId = $unitId;
        $this->unitName = $unit->name;
        $this->unitCode = $unit->code ?? '';
        $this->unitOrgLevelId = $unit->org_level_id;
        $this->parentId = $unit->parent_id;
        $this->modalMode = 'edit';
        $this->showModal = true;
    }

    public function saveUnit(): void
    {
        $this->validate([
            'unitName' => ['required', 'string', 'min:2', 'max:255'],
            'unitCode' => ['nullable', 'string', 'max:50'],
            'unitOrgLevelId' => ['required', 'exists:org_levels,id'],
        ]);

        if ($this->modalMode === 'create') {
            $this->authorize('create', OrgUnit::class);

            $unit = OrgUnit::create([
                'parent_id' => $this->parentId ?: null,
                'org_level_id' => $this->unitOrgLevelId,
                'name' => $this->unitName,
                'code' => $this->unitCode ?: null,
                'path' => '/',
                'is_active' => true,
            ]);

            $unit->rebuildPath();
        } else {
            $unit = OrgUnit::findOrFail($this->editingUnitId);
            $this->authorize('update', $unit);

            $unit->update([
                'name' => $this->unitName,
                'code' => $this->unitCode ?: null,
                'org_level_id' => $this->unitOrgLevelId,
            ]);
        }

        $this->showModal = false;
        $this->reset(['unitName', 'unitCode', 'unitOrgLevelId', 'editingUnitId', 'parentId']);
        $this->dispatch('unit-saved');
        session()->flash('success', __('Organisation unit saved.'));
    }

    public function moveUnit(string $unitId, ?string $newParentId): void
    {
        $unit = OrgUnit::findOrFail($unitId);
        $this->authorize('update', $unit);

        if ($newParentId) {
            $newParent = OrgUnit::findOrFail($newParentId);
            if (str_starts_with($newParent->path, $unit->path)) {
                session()->flash('error', __('Cannot move a unit into one of its own descendants.'));

                return;
            }
        }

        $unit->parent_id = $newParentId ?: null;
        $unit->save();
        $unit->rebuildPath();

        session()->flash('success', __('Unit moved.'));
    }

    public function toggleActive(string $unitId): void
    {
        $unit = OrgUnit::findOrFail($unitId);
        $this->authorize('update', $unit);

        $unit->update(['is_active' => ! $unit->is_active]);
    }

    public function render(): View
    {
        return view('livewire.structure.tree')->layout('layouts.app', [
            'title' => __('Organisation Structure'),
        ]);
    }
}

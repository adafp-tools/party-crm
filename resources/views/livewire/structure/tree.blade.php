<div>
    {{-- Search bar + Add root unit --}}
    <div class="mb-6 flex items-center gap-4">
        <div class="flex-1">
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('Search units…') }}"
                class="form-input w-full max-w-sm"
            >
        </div>
        @can('create', \App\Models\OrgUnit::class)
            <button wire:click="openCreateModal(null)" class="btn-primary">
                + {{ __('Add top-level unit') }}
            </button>
        @endcan
    </div>

    {{-- Flash --}}
    @if (session('success'))
        <div class="mb-4 rounded border border-green-300 bg-green-50 px-4 py-2 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded border border-red-300 bg-red-50 px-4 py-2 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    {{-- Tree --}}
    <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
        @forelse ($this->tree as $node)
            @include('livewire.structure._node', ['node' => $node, 'depth' => 0])
        @empty
            <p class="p-6 text-sm text-gray-500">
                {{ $search ? __('No units match your search.') : __('No units yet. Add one to get started.') }}
            </p>
        @endforelse
    </div>

    {{-- Create / Edit modal --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl" x-trap.inert.noscroll="true">
                <h2 class="mb-4 text-lg font-semibold font-heading">
                    {{ $modalMode === 'create' ? __('Add unit') : __('Edit unit') }}
                </h2>

                <div class="space-y-4">
                    <div>
                        <label class="form-label">{{ __('Name') }}</label>
                        <input wire:model="unitName" type="text" class="form-input w-full" autofocus>
                        @error('unitName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">{{ __('Code') }}
                            <span class="text-gray-400 text-xs">({{ __('optional') }})</span>
                        </label>
                        <input wire:model="unitCode" type="text" class="form-input w-full" maxlength="50">
                        @error('unitCode') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">{{ __('Level') }}</label>
                        <select wire:model="unitOrgLevelId" class="form-input w-full">
                            <option value="">— {{ __('Select') }} —</option>
                            @foreach ($this->orgLevels as $level)
                                <option value="{{ $level->id }}">{{ __($level->name_key) }}</option>
                            @endforeach
                        </select>
                        @error('unitOrgLevelId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button wire:click="$set('showModal', false)" type="button" class="btn-secondary">
                        {{ __('Cancel') }}
                    </button>
                    <button wire:click="saveUnit" type="button" class="btn-primary" wire:loading.attr="disabled">
                        {{ __('Save') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

<div>
    {{-- Header --}}
    <div class="mb-6 flex items-center gap-4">
        <div class="flex-1">
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('Search users…') }}"
                class="form-input w-full max-w-sm"
            >
        </div>
        @can('create', \App\Models\User::class)
            <button wire:click="openCreateModal" class="btn-primary">
                + {{ __('Add user') }}
            </button>
        @endcan
    </div>

    @if (session('success'))
        <div class="mb-4 rounded border border-green-300 bg-green-50 px-4 py-2 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    {{-- User table --}}
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <table class="w-full text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">{{ __('Name') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('Email') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('Roles & Units') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('2FA') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('Status') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($this->users as $user)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $user->name }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $user->email }}</td>
                        <td class="px-4 py-3">
                            @foreach ($user->roleAssignments as $ra)
                                <span class="mb-1 mr-1 inline-flex rounded bg-primary-50 px-2 py-0.5 text-xs text-primary-700">
                                    {{ $ra->role?->name }}
                                    @if ($ra->orgUnit)
                                        — {{ $ra->orgUnit->name }}
                                    @endif
                                </span>
                            @endforeach
                        </td>
                        <td class="px-4 py-3">
                            @if ($user->hasTotpEnabled())
                                <span class="text-green-600">✓</span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($user->is_active)
                                <span class="rounded bg-green-50 px-2 py-0.5 text-xs text-green-700">{{ __('Active') }}</span>
                            @else
                                <span class="rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-500">{{ __('Inactive') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @can('update', $user)
                                <button wire:click="openEditModal('{{ $user->id }}')" class="mr-2 text-primary-600 hover:underline">{{ __('Edit') }}</button>
                            @endcan
                            @can('update', $user)
                                @if ($user->is_active && $user->id !== auth()->id())
                                    <button
                                        wire:click="deactivateUser('{{ $user->id }}')"
                                        wire:confirm="{{ __('Deactivate this user and revoke all sessions?') }}"
                                        class="text-red-600 hover:underline"
                                    >{{ __('Deactivate') }}</button>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">
                            {{ __('No users found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Create / Edit modal --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 py-8">
            <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
                <h2 class="mb-4 font-heading text-lg font-semibold">
                    {{ $modalMode === 'create' ? __('Add user') : __('Edit user') }}
                </h2>

                <div class="space-y-4">
                    <div>
                        <label class="form-label">{{ __('Full name') }}</label>
                        <input wire:model="userName" type="text" class="form-input w-full" autofocus>
                        @error('userName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">{{ __('Email address') }}</label>
                        <input wire:model="userEmail" type="email" class="form-input w-full">
                        @error('userEmail') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">{{ __('Phone') }} <span class="text-gray-400 text-xs">({{ __('optional') }})</span></label>
                        <input wire:model="userPhone" type="tel" class="form-input w-full">
                    </div>
                    <div>
                        <label class="form-label">{{ __('Password') }}
                            @if ($modalMode === 'edit')
                                <span class="text-gray-400 text-xs">({{ __('leave blank to keep current') }})</span>
                            @endif
                        </label>
                        <input wire:model="userPassword" type="password" class="form-input w-full" autocomplete="new-password">
                        @error('userPassword') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">{{ __('Locale') }}</label>
                        <select wire:model="userLocale" class="form-input w-full">
                            <option value="en">English</option>
                            <option value="fr">Français</option>
                            <option value="am">አማርኛ</option>
                        </select>
                    </div>

                    {{-- Role assignments --}}
                    <div>
                        <label class="form-label">{{ __('Role assignments') }}</label>
                        <div class="space-y-2">
                            @foreach ($assignments as $i => $assignment)
                                <div class="flex items-center gap-2">
                                    <select wire:model="assignments.{{ $i }}.role_id" class="form-input flex-1">
                                        <option value="">— {{ __('Role') }} —</option>
                                        @foreach ($this->roles as $role)
                                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                    <select wire:model="assignments.{{ $i }}.org_unit_id" class="form-input flex-1">
                                        <option value="">— {{ __('All units') }} —</option>
                                        @foreach ($this->orgUnits as $unit)
                                            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                        @endforeach
                                    </select>
                                    <button wire:click="removeAssignment({{ $i }})" type="button" class="text-red-500 hover:text-red-700">✕</button>
                                </div>
                            @endforeach
                        </div>
                        <button wire:click="addAssignment" type="button" class="mt-2 text-xs text-primary-600 hover:underline">
                            + {{ __('Add assignment') }}
                        </button>
                        @error('assignments.*.role_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button wire:click="$set('showModal', false)" type="button" class="btn-secondary">
                        {{ __('Cancel') }}
                    </button>
                    <button wire:click="saveUser" type="button" class="btn-primary" wire:loading.attr="disabled">
                        {{ __('Save') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

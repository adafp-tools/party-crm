<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">{{ __('Dues Plans') }}</h1>
        @can('create', \App\Models\DuesPlan::class)
            <button wire:click="openCreate" class="btn-primary">{{ __('New Plan') }}</button>
        @endcan
    </div>

    @if (session('success'))
        <div class="alert-success mb-4">{{ session('success') }}</div>
    @endif

    @if ($showForm)
        <div class="card mb-6">
            <h2 class="card-title">{{ $editId ? __('Edit Plan') : __('New Plan') }}</h2>
            <form wire:submit="save" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-3">
                    <label class="label">{{ __('Plan Name') }} *</label>
                    <input wire:model="name" type="text" class="input @error('name') input-error @enderror" />
                    @error('name') <p class="error-text">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">{{ __('Amount') }} *</label>
                    <input wire:model="amount" type="number" step="0.01" min="0" class="input @error('amount') input-error @enderror" />
                    @error('amount') <p class="error-text">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">{{ __('Currency') }} *</label>
                    <input wire:model="currency" type="text" class="input" maxlength="10" />
                </div>
                <div>
                    <label class="label">{{ __('Period') }} *</label>
                    <select wire:model="period" class="input">
                        <option value="monthly">{{ __('Monthly') }}</option>
                        <option value="yearly">{{ __('Yearly') }}</option>
                        <option value="one_off">{{ __('One-off') }}</option>
                    </select>
                </div>
                <div>
                    <label class="label">{{ __('Applies to') }}</label>
                    <select wire:model="appliesTo" class="input">
                        <option value="all">{{ __('All members') }}</option>
                        <option value="member">{{ __('Members only') }}</option>
                        <option value="supporter">{{ __('Supporters only') }}</option>
                        <option value="youth_wing">{{ __('Youth Wing') }}</option>
                        <option value="women_wing">{{ __("Women's Wing") }}</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <label class="flex items-center gap-2">
                        <input wire:model="isActive" type="checkbox" class="checkbox" />
                        {{ __('Active') }}
                    </label>
                </div>
                <div class="md:col-span-3 flex gap-2">
                    <button type="submit" class="btn-primary">{{ __('Save') }}</button>
                    <button type="button" wire:click="$set('showForm', false)" class="btn-secondary">{{ __('Cancel') }}</button>
                </div>
            </form>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="th">{{ __('Name') }}</th>
                    <th class="th">{{ __('Amount') }}</th>
                    <th class="th">{{ __('Period') }}</th>
                    <th class="th">{{ __('Applies to') }}</th>
                    <th class="th">{{ __('Status') }}</th>
                    <th class="th"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($plans as $plan)
                    <tr class="{{ $plan->is_active ? '' : 'opacity-50' }}">
                        <td class="td font-medium">{{ $plan->name }}</td>
                        <td class="td font-mono">{{ number_format($plan->amount, 2) }} {{ $plan->currency }}</td>
                        <td class="td text-sm">{{ __(ucfirst(str_replace('_', '-', $plan->period))) }}</td>
                        <td class="td text-sm">{{ __(ucfirst(str_replace('_', ' ', $plan->applies_to))) }}</td>
                        <td class="td">
                            <span class="status-badge {{ $plan->is_active ? 'status-active' : 'status-lapsed' }}">
                                {{ $plan->is_active ? __('Active') : __('Inactive') }}
                            </span>
                        </td>
                        <td class="td text-right text-sm flex gap-2 justify-end">
                            @can('update', $plan)
                                <button wire:click="openEdit('{{ $plan->id }}')" class="link">{{ __('Edit') }}</button>
                                <button wire:click="toggleActive('{{ $plan->id }}')" class="link text-gray-400">
                                    {{ $plan->is_active ? __('Deactivate') : __('Activate') }}
                                </button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="td text-center text-gray-500">{{ __('No dues plans defined.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

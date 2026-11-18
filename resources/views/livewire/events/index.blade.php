<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">{{ __('Events') }}</h1>
        @can('create', \App\Models\Event::class)
            <button wire:click="openCreate" class="btn-primary">{{ __('New Event') }}</button>
        @endcan
    </div>

    @if (session('success'))
        <div class="alert-success mb-4">{{ session('success') }}</div>
    @endif

    {{-- Filters --}}
    <div class="flex flex-wrap gap-3 mb-4">
        <input wire:model.live.debounce.300ms="search" type="text"
            class="input w-56" placeholder="{{ __('Search title…') }}" />
        <select wire:model.live="filterType" class="input w-40">
            <option value="">{{ __('All types') }}</option>
            <option value="meeting">{{ __('Meeting') }}</option>
            <option value="rally">{{ __('Rally') }}</option>
            <option value="training">{{ __('Training') }}</option>
            <option value="congress">{{ __('Congress') }}</option>
            <option value="other">{{ __('Other') }}</option>
        </select>
        <select wire:model.live="filterUnit" class="input w-56">
            <option value="">{{ __('All units') }}</option>
            @foreach ($orgUnits as $unit)
                <option value="{{ $unit->id }}">{{ $unit->name }}</option>
            @endforeach
        </select>
    </div>

    {{-- Create / Edit form --}}
    @if ($showForm)
        <div class="card mb-6">
            <h2 class="card-title">{{ $editId ? __('Edit Event') : __('New Event') }}</h2>
            <form wire:submit="save" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="label">{{ __('Title') }} *</label>
                    <input wire:model="title" type="text" class="input @error('title') input-error @enderror" />
                    @error('title') <p class="error-text">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">{{ __('Type') }} *</label>
                    <select wire:model="type" class="input">
                        <option value="meeting">{{ __('Meeting') }}</option>
                        <option value="rally">{{ __('Rally') }}</option>
                        <option value="training">{{ __('Training') }}</option>
                        <option value="congress">{{ __('Congress') }}</option>
                        <option value="other">{{ __('Other') }}</option>
                    </select>
                </div>
                <div>
                    <label class="label">{{ __('Unit') }} *</label>
                    <select wire:model="orgUnitId" class="input @error('orgUnitId') input-error @enderror">
                        <option value="">{{ __('Select unit…') }}</option>
                        @foreach ($orgUnits as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                        @endforeach
                    </select>
                    @error('orgUnitId') <p class="error-text">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">{{ __('Starts at') }} *</label>
                    <input wire:model="startsAt" type="datetime-local" class="input @error('startsAt') input-error @enderror" />
                    @error('startsAt') <p class="error-text">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">{{ __('Ends at') }}</label>
                    <input wire:model="endsAt" type="datetime-local" class="input @error('endsAt') input-error @enderror" />
                    @error('endsAt') <p class="error-text">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="label">{{ __('Location') }}</label>
                    <input wire:model="locationText" type="text" class="input" maxlength="500" />
                </div>
                <div class="md:col-span-2 flex gap-2">
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
                    <th class="th">{{ __('Title') }}</th>
                    <th class="th">{{ __('Type') }}</th>
                    <th class="th">{{ __('Unit') }}</th>
                    <th class="th">{{ __('Date') }}</th>
                    <th class="th text-right">{{ __('Attendance') }}</th>
                    <th class="th"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($events as $event)
                    <tr>
                        <td class="td font-medium">
                            <a href="{{ route('events.attendance', $event) }}" class="link">{{ $event->title }}</a>
                        </td>
                        <td class="td text-sm">{{ __(ucfirst($event->type)) }}</td>
                        <td class="td text-sm">{{ $event->orgUnit?->name ?? '—' }}</td>
                        <td class="td text-sm">{{ $event->starts_at->format('d M Y H:i') }}</td>
                        <td class="td text-right text-sm">{{ $event->attendanceRate() }}%</td>
                        <td class="td text-right text-sm flex gap-2 justify-end">
                            @can('update', $event)
                                <button wire:click="openEdit('{{ $event->id }}')" class="link">{{ __('Edit') }}</button>
                                <button wire:click="delete('{{ $event->id }}')"
                                    wire:confirm="{{ __('Delete this event?') }}"
                                    class="link text-red-600">{{ __('Delete') }}</button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="td text-center text-gray-500">{{ __('No events found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t">{{ $events->links() }}</div>
    </div>
</div>

<div>
    <h1 class="text-2xl font-bold mb-6">{{ $task ? __('Edit Task') : __('New Task') }}</h1>

    @if (session('success'))
        <div class="alert-success mb-4">{{ session('success') }}</div>
    @endif

    <form wire:submit="save" class="card space-y-4 max-w-2xl">
        <div>
            <label class="label">{{ __('Title') }} *</label>
            <input wire:model="title" type="text" class="input @error('title') input-error @enderror" />
            @error('title') <p class="error-text">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label">{{ __('Details') }}</label>
            <textarea wire:model="details" rows="4" class="input"></textarea>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                <label class="label">{{ __('Assign to') }}</label>
                <select wire:model="assigneeId" class="input">
                    <option value="">{{ __('Unassigned') }}</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">{{ __('Due at') }}</label>
                <input wire:model="dueAt" type="datetime-local" class="input" />
            </div>
            <div>
                <label class="label">{{ __('Status') }}</label>
                <select wire:model="status" class="input">
                    <option value="pending">{{ __('Pending') }}</option>
                    <option value="in_progress">{{ __('In Progress') }}</option>
                    <option value="done">{{ __('Done') }}</option>
                    <option value="cancelled">{{ __('Cancelled') }}</option>
                </select>
            </div>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="btn-primary">{{ __('Save') }}</button>
            <a href="{{ route('tasks.index') }}" class="btn-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
</div>

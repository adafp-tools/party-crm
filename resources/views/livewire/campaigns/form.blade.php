<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">
            {{ $campaign ? __('Edit Campaign') : __('New Campaign') }}
        </h1>
        <a href="{{ route('campaigns.index') }}" class="btn-secondary">{{ __('Cancel') }}</a>
    </div>

    @if (session('success'))
        <div class="alert-success mb-4">{{ session('success') }}</div>
    @endif

    <div class="card max-w-2xl">
        <form wire:submit="save" class="grid grid-cols-1 gap-4">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="label">{{ __('Type') }} *</label>
                    <select wire:model.live="type" class="input">
                        <option value="sms">{{ __('SMS') }}</option>
                        <option value="email">{{ __('Email') }}</option>
                    </select>
                    @error('type') <p class="error-text">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">{{ __('Target unit') }}</label>
                    <select wire:model="orgUnitId" class="input">
                        <option value="">{{ __('All units') }}</option>
                        @foreach ($orgUnits as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="label">{{ __('Campaign title') }} *</label>
                <input wire:model="title" type="text" class="input @error('title') input-error @enderror"
                    placeholder="{{ __('Internal reference name') }}" maxlength="200" />
                @error('title') <p class="error-text">{{ $message }}</p> @enderror
            </div>

            @if ($type === 'email')
                <div>
                    <label class="label">{{ __('Subject line') }} *</label>
                    <input wire:model="subject" type="text" class="input @error('subject') input-error @enderror"
                        maxlength="300" />
                    @error('subject') <p class="error-text">{{ $message }}</p> @enderror
                </div>
            @endif

            <div>
                <label class="label">{{ __('Message body') }} *</label>
                @if ($type === 'sms')
                    <p class="text-xs text-gray-500 mb-1">{{ __('Max 160 characters for a single SMS.') }}</p>
                @endif
                <textarea wire:model="body"
                    class="input @error('body') input-error @enderror"
                    rows="{{ $type === 'sms' ? 3 : 8 }}"
                    maxlength="{{ $type === 'sms' ? 160 : 10000 }}"></textarea>
                @if ($type === 'sms')
                    <p class="text-xs text-gray-500 text-right">{{ strlen($body) }}/160</p>
                @endif
                @error('body') <p class="error-text">{{ $message }}</p> @enderror
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-secondary">{{ __('Save draft') }}</button>
                @can($campaign ? 'send' : 'create', $campaign ?? \App\Models\Campaign::class)
                    <button type="button" wire:click="send"
                        wire:confirm="{{ __('Send this campaign now to all targeted members?') }}"
                        class="btn-primary">
                        {{ __('Send now') }}
                    </button>
                @endcan
            </div>
        </form>
    </div>
</div>

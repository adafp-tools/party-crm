<div>
    @section('title', __('Settings'))

    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold">{{ __('Settings') }}</h1>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800 ring-1 ring-green-200">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-6">

        {{-- Party Identity --}}
        <div class="card">
            <h2 class="card-title">{{ __('Party Identity') }}</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="label">{{ __('Party Name') }} *</label>
                    <input wire:model="partyName" type="text" class="input @error('partyName') input-error @enderror" />
                    @error('partyName') <p class="error-text">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">{{ __('Short Name / Abbreviation') }} *</label>
                    <input wire:model="partyShortName" type="text" class="input @error('partyShortName') input-error @enderror" />
                    @error('partyShortName') <p class="error-text">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">{{ __('Membership Number Prefix') }}</label>
                    <input type="text" value="{{ $membershipPrefix }}" class="input bg-gray-50 cursor-not-allowed" readonly disabled />
                    <p class="mt-1 text-xs text-gray-500">{{ __('Cannot be changed after installation — would invalidate existing membership numbers.') }}</p>
                </div>
            </div>
        </div>

        {{-- Localisation --}}
        <div class="card">
            <h2 class="card-title">{{ __('Localisation') }}</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <label class="label">{{ __('Enabled Locales') }}</label>
                    <input type="text" value="{{ $locales }}" class="input bg-gray-50 cursor-not-allowed" readonly disabled />
                    <p class="mt-1 text-xs text-gray-500">{{ __('Set via party:install. Contact support to change.') }}</p>
                </div>
                <div>
                    <label class="label">{{ __('Default Locale') }} *</label>
                    <select wire:model="defaultLocale" class="input @error('defaultLocale') input-error @enderror">
                        <option value="en">English</option>
                        <option value="fr">Français</option>
                        <option value="am">አማርኛ</option>
                    </select>
                    @error('defaultLocale') <p class="error-text">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">{{ __('Currency') }} *</label>
                    <input wire:model="currency" type="text" maxlength="10" class="input @error('currency') input-error @enderror" placeholder="XOF" />
                    @error('currency') <p class="error-text">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">{{ __('Timezone') }} *</label>
                    <input wire:model="timezone" type="text" class="input @error('timezone') input-error @enderror" placeholder="Africa/Dakar" />
                    @error('timezone') <p class="error-text">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                {{ __('Save Settings') }}
            </button>
        </div>

    </form>
</div>

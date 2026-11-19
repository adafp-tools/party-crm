<div>
    @section('title', __('Settings'))

    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold">{{ __('Settings') }}</h1>
        <button wire:click="save" class="btn-primary" wire:loading.attr="disabled">
            {{ __('Save Settings') }}
        </button>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-md bg-green-50 p-4 text-sm text-green-800 ring-1 ring-green-200">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-6">

        {{-- Party Identity --}}
        <div class="card p-6">
            <h2 class="mb-4 text-base font-semibold text-gray-900">{{ __('Party Identity') }}</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

                <div>
                    <label class="form-label">{{ __('Party Name') }} *</label>
                    <input wire:model="partyName" type="text"
                        class="form-input mt-1 @error('partyName') ring-red-500 @enderror" />
                    @error('partyName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="form-label">{{ __('Short Name / Abbreviation') }} *</label>
                    <input wire:model="partyShortName" type="text"
                        class="form-input mt-1 @error('partyShortName') ring-red-500 @enderror" />
                    @error('partyShortName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="form-label">{{ __('Logo URL') }}</label>
                    <input wire:model="partyLogoUrl" type="url"
                        class="form-input mt-1 @error('partyLogoUrl') ring-red-500 @enderror"
                        placeholder="https://example.com/logo.png" />
                    @error('partyLogoUrl') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    @if ($partyLogoUrl)
                        <div class="mt-2">
                            <img src="{{ $partyLogoUrl }}" alt="{{ __('Party logo') }}"
                                class="h-12 w-auto rounded border border-gray-200 object-contain p-1" />
                        </div>
                    @endif
                </div>

                <div>
                    <label class="form-label">{{ __('Membership Number Prefix') }}</label>
                    <input type="text" value="{{ $membershipPrefixDisplay }}"
                        class="form-input mt-1 cursor-not-allowed bg-gray-50" readonly disabled />
                    <p class="mt-1 text-xs text-gray-500">{{ __('Cannot be changed — would invalidate existing membership numbers.') }}</p>
                </div>

                <div class="md:col-span-2">
                    <label class="form-label">{{ __('Description / About') }}</label>
                    <textarea wire:model="partyDescription" rows="3"
                        class="form-input mt-1 @error('partyDescription') ring-red-500 @enderror"
                        placeholder="{{ __('Brief description of the party for public-facing pages…') }}"></textarea>
                    @error('partyDescription') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

            </div>
        </div>

        {{-- Contact Information --}}
        <div class="card p-6">
            <h2 class="mb-4 text-base font-semibold text-gray-900">{{ __('Contact Information') }}</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

                <div>
                    <label class="form-label">{{ __('Website') }}</label>
                    <input wire:model="partyWebsite" type="url"
                        class="form-input mt-1 @error('partyWebsite') ring-red-500 @enderror"
                        placeholder="https://www.party.example" />
                    @error('partyWebsite') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="form-label">{{ __('Contact Email') }}</label>
                    <input wire:model="partyEmail" type="email"
                        class="form-input mt-1 @error('partyEmail') ring-red-500 @enderror" />
                    @error('partyEmail') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="form-label">{{ __('Phone') }}</label>
                    <input wire:model="partyPhone" type="tel"
                        class="form-input mt-1" />
                </div>

                <div>
                    <label class="form-label">{{ __('Address') }}</label>
                    <input wire:model="partyAddress" type="text"
                        class="form-input mt-1 @error('partyAddress') ring-red-500 @enderror" />
                    @error('partyAddress') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

            </div>
        </div>

        {{-- Localisation --}}
        <div class="card p-6">
            <h2 class="mb-4 text-base font-semibold text-gray-900">{{ __('Language & Localisation') }}</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

                <div>
                    <label class="form-label mb-2 block">{{ __('Enabled Languages') }}</label>
                    <div class="space-y-2">
                        <label class="flex items-center gap-2">
                            <input wire:model="localeEn" type="checkbox" class="rounded border-gray-300" />
                            <span class="text-sm">English</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input wire:model="localeFr" type="checkbox" class="rounded border-gray-300" />
                            <span class="text-sm">Français</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input wire:model="localePt" type="checkbox" class="rounded border-gray-300" />
                            <span class="text-sm">Português</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input wire:model="localeAr" type="checkbox" class="rounded border-gray-300" />
                            <span class="text-sm" dir="rtl">العربية</span>
                        </label>
                    </div>
                    @error('localeEn') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="form-label">{{ __('Default Language') }} *</label>
                    <select wire:model="defaultLocale"
                        class="form-input mt-1 @error('defaultLocale') ring-red-500 @enderror">
                        <option value="en">English</option>
                        <option value="fr">Français</option>
                        <option value="pt">Português</option>
                        <option value="ar">العربية</option>
                    </select>
                    @error('defaultLocale') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="form-label">{{ __('Party Colour') }}</label>
                    <div class="mt-1 flex items-center gap-3">
                        <input wire:model="partyColor" type="color"
                            class="h-9 w-16 cursor-pointer rounded-md border border-gray-300 p-0.5"
                            value="{{ $partyColor ?: '#1a5c3a' }}" />
                        <input wire:model="partyColor" type="text" maxlength="7"
                            class="form-input @error('partyColor') ring-red-500 @enderror"
                            placeholder="#1a5c3a" />
                    </div>
                    <p class="mt-1 text-xs text-gray-500">{{ __('Primary colour (hex code, e.g. #1a5c3a)') }}</p>
                    @error('partyColor') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="form-label">{{ __('Currency') }} *</label>
                    <input wire:model="currency" type="text" maxlength="10"
                        class="form-input mt-1 @error('currency') ring-red-500 @enderror"
                        placeholder="XOF" />
                    <p class="mt-1 text-xs text-gray-500">{{ __('ISO 4217 code, e.g. XOF, EUR, ETB') }}</p>
                    @error('currency') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="form-label">{{ __('Timezone') }} *</label>
                    <input wire:model="timezone" type="text"
                        class="form-input mt-1 @error('timezone') ring-red-500 @enderror"
                        placeholder="Africa/Dakar" />
                    <p class="mt-1 text-xs text-gray-500">{{ __('e.g. Africa/Dakar, Europe/Paris, Africa/Addis_Ababa') }}</p>
                    @error('timezone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

            </div>
        </div>

        {{-- System Info (read-only) --}}
        <div class="card p-6">
            <h2 class="mb-4 text-base font-semibold text-gray-900">{{ __('System Information') }}</h2>
            <dl class="grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
                <div class="flex gap-2">
                    <dt class="font-medium text-gray-500 w-40 flex-shrink-0">{{ __('Membership Prefix') }}</dt>
                    <dd class="font-mono text-gray-900">{{ $membershipPrefixDisplay }}</dd>
                </div>
                <div class="flex gap-2">
                    <dt class="font-medium text-gray-500 w-40 flex-shrink-0">{{ __('Installed At') }}</dt>
                    <dd class="text-gray-900">{{ $installedAt }}</dd>
                </div>
                <div class="flex gap-2">
                    <dt class="font-medium text-gray-500 w-40 flex-shrink-0">{{ __('Demo Mode') }}</dt>
                    <dd>
                        @if ($isDemo)
                            <span class="rounded bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-700">{{ __('Yes') }}</span>
                        @else
                            <span class="rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-600">{{ __('No') }}</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        <div class="flex gap-3 pb-6">
            <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                {{ __('Save Settings') }}
            </button>
        </div>

    </form>
</div>

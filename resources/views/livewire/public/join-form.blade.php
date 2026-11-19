<div>
@if($submitted)
    {{-- Success / duplicate state --}}
    <div class="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-gray-200 text-center">
        @if($isDuplicate)
            <div class="mx-auto mb-4 inline-flex h-14 w-14 items-center justify-center rounded-full bg-amber-100">
                <svg class="h-7 w-7 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
            </div>
            <h2 class="text-xl font-semibold text-gray-900">{{ __('Already registered') }}</h2>
            <p class="mt-2 text-sm text-gray-500">{{ __('Our records show you may already be a member. Please contact your branch if you need help.') }}</p>
        @else
            <div class="mx-auto mb-4 inline-flex h-14 w-14 items-center justify-center rounded-full bg-primary-100">
                <svg class="h-7 w-7 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>
            <h2 class="text-xl font-semibold text-gray-900">{{ __('Application received') }}</h2>
            <p class="mt-2 text-sm text-gray-500">{{ __('Thank you for applying. A party representative will be in touch shortly.') }}</p>
        @endif
    </div>

@else
    <div class="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-gray-200">

        <h1 class="text-2xl font-bold text-gray-900">{{ __('Join the party') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('Complete the form below to submit your membership application.') }}</p>

        @if($errors->has('rateLimit'))
            <div class="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">
                {{ $errors->first('rateLimit') }}
            </div>
        @endif

        <form wire:submit.prevent="submit" class="mt-6 space-y-5" novalidate>

            {{-- Honeypot: visually and physically hidden, bots fill it --}}
            <div style="position:absolute;left:-9999px;top:-9999px;opacity:0;pointer-events:none;" aria-hidden="true" tabindex="-1">
                <input wire:model="website" type="text" name="website" id="website" autocomplete="off" tabindex="-1">
            </div>

            {{-- Name row --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="firstName" class="label">{{ __('First Name') }} <span class="text-red-500">*</span></label>
                    <input wire:model="firstName" type="text" id="firstName" autocomplete="given-name"
                           class="form-input mt-1 @error('firstName') ring-red-500 @enderror">
                    @error('firstName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="lastName" class="label">{{ __('Last Name') }} <span class="text-red-500">*</span></label>
                    <input wire:model="lastName" type="text" id="lastName" autocomplete="family-name"
                           class="form-input mt-1 @error('lastName') ring-red-500 @enderror">
                    @error('lastName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Email --}}
            <div>
                <label for="email" class="label">{{ __('Email') }}</label>
                <input wire:model="email" type="email" id="email" autocomplete="email"
                       class="form-input mt-1 @error('email') ring-red-500 @enderror">
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Phone --}}
            <div>
                <label for="phone" class="label">{{ __('Phone') }}</label>
                <input wire:model="phone" type="tel" id="phone" autocomplete="tel"
                       class="form-input mt-1 @error('phone') ring-red-500 @enderror">
                @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Date of birth --}}
            <div>
                <label for="dateOfBirth" class="label">{{ __('Date of Birth') }}</label>
                <input wire:model="dateOfBirth" type="date" id="dateOfBirth" autocomplete="bday"
                       class="form-input mt-1 @error('dateOfBirth') ring-red-500 @enderror"
                       max="{{ now()->subDay()->format('Y-m-d') }}">
                @error('dateOfBirth') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Cascading org unit --}}
            <div>
                <label for="regionId" class="label">{{ __('Region') }} <span class="text-red-500">*</span></label>
                <select wire:model.live="regionId" id="regionId"
                        class="form-input mt-1 @error('regionId') ring-red-500 @enderror">
                    <option value="">— {{ __('Select') }} —</option>
                    @foreach($this->regions as $region)
                        <option value="{{ $region->id }}">{{ $region->name }}</option>
                    @endforeach
                </select>
                @error('regionId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            @if($regionId && $this->branches->isNotEmpty())
            <div>
                <label for="branchId" class="label">{{ __('Branch') }}</label>
                <select wire:model="branchId" id="branchId"
                        class="form-input mt-1 @error('branchId') ring-red-500 @enderror">
                    <option value="">— {{ __('Select') }} —</option>
                    @foreach($this->branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
                @error('branchId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            @endif

            {{-- Consent --}}
            <div class="rounded-lg bg-gray-50 p-4">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input wire:model="consentGiven" type="checkbox" id="consentGiven"
                           class="mt-0.5 h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 @error('consentGiven') ring-red-500 @enderror">
                    <span class="text-sm text-gray-700">
                        {{ __('I consent to the party storing and processing my personal data for membership purposes, in accordance with applicable data protection law.') }}
                        <span class="text-red-500">*</span>
                    </span>
                </label>
                @error('consentGiven') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Submit --}}
            <div class="pt-2">
                <button type="submit"
                        class="btn-primary w-full justify-center py-2.5 text-sm font-semibold"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-75 cursor-not-allowed">
                    <span wire:loading.remove>{{ __('Submit application') }}</span>
                    <span wire:loading class="flex items-center justify-center gap-2">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        {{ __('Submitting…') }}
                    </span>
                </button>
            </div>

        </form>
    </div>
@endif
</div>

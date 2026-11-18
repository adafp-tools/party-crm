<div>
    <h1 class="text-2xl font-bold mb-6">
        {{ $member && $member->exists ? __('Edit Member') : __('Register Member') }}
    </h1>

    @if ($duplicateWarning)
        <div class="alert-warning mb-4">
            ⚠ {{ $duplicateWarning['message'] }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-6">
        {{-- Personal Information --}}
        <div class="card">
            <h2 class="card-title">{{ __('Personal Information') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="label">{{ __('First Name') }} *</label>
                    <input wire:model="firstName" type="text" class="input @error('firstName') input-error @enderror" />
                    @error('firstName') <p class="error-text">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">{{ __('Middle Name') }}</label>
                    <input wire:model="middleName" type="text" class="input" />
                </div>
                <div>
                    <label class="label">{{ __('Last Name') }} *</label>
                    <input wire:model="lastName" type="text" class="input @error('lastName') input-error @enderror" />
                    @error('lastName') <p class="error-text">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">{{ __('Gender') }} *</label>
                    <select wire:model="gender" class="input @error('gender') input-error @enderror">
                        <option value="">{{ __('Select…') }}</option>
                        <option value="male">{{ __('Male') }}</option>
                        <option value="female">{{ __('Female') }}</option>
                        <option value="other">{{ __('Other') }}</option>
                    </select>
                    @error('gender') <p class="error-text">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">{{ __('Date of Birth') }} *</label>
                    <input wire:model="dateOfBirth" type="date" class="input @error('dateOfBirth') input-error @enderror" />
                    @error('dateOfBirth') <p class="error-text">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">{{ __('Occupation') }}</label>
                    <input wire:model="occupation" type="text" class="input" />
                </div>
            </div>
        </div>

        {{-- Contact Details --}}
        <div class="card">
            <h2 class="card-title">{{ __('Contact Details') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="label">{{ __('Email') }}</label>
                    <input wire:model="email" type="email" class="input @error('email') input-error @enderror" />
                    @error('email') <p class="error-text">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">{{ __('Phone') }}</label>
                    <input wire:model.blur="phone" type="tel" class="input @error('phone') input-error @enderror"
                        placeholder="+1234567890" />
                    @error('phone') <p class="error-text">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="label">{{ __('Address') }}</label>
                    <input wire:model="addressLine" type="text" class="input" />
                </div>
            </div>
        </div>

        {{-- Membership Details --}}
        <div class="card">
            <h2 class="card-title">{{ __('Membership Details') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="label">{{ __('Organisation Unit') }} *</label>
                    <select wire:model="orgUnitId" class="input @error('orgUnitId') input-error @enderror">
                        <option value="">{{ __('Select unit…') }}</option>
                        @foreach ($orgUnits as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                        @endforeach
                    </select>
                    @error('orgUnitId') <p class="error-text">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">{{ __('Category') }}</label>
                    <select wire:model="category" class="input">
                        <option value="member">{{ __('Member') }}</option>
                        <option value="supporter">{{ __('Supporter') }}</option>
                    </select>
                </div>
                <div>
                    <label class="label">{{ __('Joined Date') }}</label>
                    <input wire:model="joinedDate" type="date" class="input" />
                </div>
                <div>
                    <label class="label">{{ __('Preferred Language') }}</label>
                    <input wire:model="preferredLanguage" type="text" class="input" maxlength="10" />
                </div>
            </div>
            <div class="mt-4 flex flex-wrap gap-4">
                <label class="flex items-center gap-2">
                    <input wire:model="isYouthWing" type="checkbox" class="checkbox" />
                    {{ __('Youth Wing') }}
                </label>
                <label class="flex items-center gap-2">
                    <input wire:model="isWomenWing" type="checkbox" class="checkbox" />
                    {{ __("Women's Wing") }}
                </label>
                <label class="flex items-center gap-2">
                    <input wire:model="volunteer" type="checkbox" class="checkbox" />
                    {{ __('Volunteer') }}
                </label>
            </div>
        </div>

        {{-- Skills --}}
        <div class="card">
            <h2 class="card-title">{{ __('Skills') }}</h2>
            <div>
                <label class="label">{{ __('Skills (comma-separated)') }}</label>
                <input wire:model="skills" type="text" class="input" placeholder="{{ __('e.g. public speaking, logistics') }}" />
            </div>
        </div>

        {{-- Consent --}}
        <div class="card">
            <h2 class="card-title">{{ __('Consent & Communication') }}</h2>
            <div class="space-y-3">
                <label class="flex items-start gap-2">
                    <input wire:model="consentGiven" type="checkbox" class="checkbox mt-1" />
                    <span>{{ __('I confirm that this member has given consent for their data to be stored and processed.') }}</span>
                </label>
                @if ($consentGiven)
                    <div>
                        <label class="label">{{ __('Consent Channel') }}</label>
                        <select wire:model="consentChannel" class="input">
                            <option value="web">{{ __('Web form') }}</option>
                            <option value="paper">{{ __('Paper form') }}</option>
                            <option value="phone_app">{{ __('Phone app') }}</option>
                        </select>
                    </div>
                @endif
                <div class="flex gap-4">
                    <label class="flex items-center gap-2">
                        <input wire:model="smsOptOut" type="checkbox" class="checkbox" />
                        {{ __('Opt out of SMS') }}
                    </label>
                    <label class="flex items-center gap-2">
                        <input wire:model="emailOptOut" type="checkbox" class="checkbox" />
                        {{ __('Opt out of email') }}
                    </label>
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="btn-primary">
                {{ $member && $member->exists ? __('Save Changes') : __('Register Member') }}
            </button>
            <a href="{{ route('members.index') }}" class="btn-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
</div>

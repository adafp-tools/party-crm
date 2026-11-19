<div class="max-w-2xl mx-auto">

    {{-- Step indicator --}}
    <div class="flex items-center justify-center gap-2 mb-8">
        @foreach([1 => 'Requirements', 2 => 'Database', 3 => 'Party', 4 => 'Admin', 5 => 'Done'] as $n => $label)
            <div class="flex items-center {{ $loop->first ? '' : 'gap-2' }}">
                @if (!$loop->first)
                    <div class="w-8 h-px {{ $step >= $n ? 'bg-primary-600' : 'bg-gray-300' }}"></div>
                @endif
                <div class="flex flex-col items-center">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full text-sm font-medium
                        {{ $step > $n ? 'bg-primary-600 text-white' : ($step === $n ? 'bg-primary-600 text-white ring-2 ring-primary-200' : 'bg-gray-200 text-gray-500') }}">
                        {{ $step > $n ? '✓' : $n }}
                    </span>
                    <span class="mt-1 text-xs {{ $step === $n ? 'text-primary-600 font-medium' : 'text-gray-400' }}">{{ $label }}</span>
                </div>
            </div>
        @endforeach
    </div>

    @if(session('error'))
        <div class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-800 ring-1 ring-red-200">{{ session('error') }}</div>
    @endif

    <div class="card px-6 py-8">

        {{-- Step 1: Requirements --}}
        @if($step === 1)
            <h2 class="text-lg font-semibold mb-4">{{ __('Server Requirements') }}</h2>
            <table class="min-w-full text-sm">
                <tbody class="divide-y divide-gray-100">
                    @foreach($requirements as $name => $req)
                        <tr>
                            <td class="py-2 pr-4 font-medium text-gray-700">{{ $name }}</td>
                            <td class="py-2 pr-4 font-mono text-xs text-gray-500">{{ $req['message'] }}</td>
                            <td class="py-2 text-right">
                                @if($req['ok'])
                                    <span class="text-green-600 font-semibold">✓</span>
                                @else
                                    <span class="text-red-600 font-semibold">✗</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-6 flex justify-end">
                <button wire:click="nextStep" class="btn-primary" {{ $this->allRequirementsMet() ? '' : 'disabled' }}>
                    {{ __('Continue') }} →
                </button>
            </div>
        @endif

        {{-- Step 2: Database --}}
        @if($step === 2)
            <h2 class="text-lg font-semibold mb-4">{{ __('Database Connection') }}</h2>
            <div class="space-y-4">
                <div class="grid grid-cols-3 gap-4">
                    <div class="col-span-2">
                        <label class="form-label">{{ __('Host') }}</label>
                        <input wire:model="dbHost" type="text" class="form-input mt-1">
                        @error('dbHost') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">{{ __('Port') }}</label>
                        <input wire:model="dbPort" type="text" class="form-input mt-1">
                    </div>
                </div>
                <div>
                    <label class="form-label">{{ __('Database name') }}</label>
                    <input wire:model="dbName" type="text" class="form-input mt-1">
                    @error('dbName') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">{{ __('Username') }}</label>
                        <input wire:model="dbUser" type="text" class="form-input mt-1 font-mono">
                    </div>
                    <div>
                        <label class="form-label">{{ __('Password') }}</label>
                        <input wire:model="dbPassword" type="password" class="form-input mt-1 font-mono">
                    </div>
                </div>
            </div>
            <div class="mt-6 flex justify-end">
                <button wire:click="nextStep" class="btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>{{ __('Connect & migrate') }}</span>
                    <span wire:loading>{{ __('Connecting…') }}</span>
                </button>
            </div>
        @endif

        {{-- Step 3: Party settings --}}
        @if($step === 3)
            <h2 class="text-lg font-semibold mb-4">{{ __('Party Settings') }}</h2>
            <div class="space-y-4">
                <div>
                    <label class="form-label">{{ __('Full party name') }} *</label>
                    <input wire:model="partyName" type="text" class="form-input mt-1">
                    @error('partyName') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">{{ __('Abbreviation') }} *</label>
                        <input wire:model="shortName" type="text" class="form-input mt-1">
                        @error('shortName') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">{{ __('Membership prefix') }} *</label>
                        <input wire:model="membershipPrefix" type="text" class="form-input mt-1 uppercase" maxlength="6">
                        @error('membershipPrefix') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">{{ __('Currency (ISO 4217)') }}</label>
                        <input wire:model="currency" type="text" class="form-input mt-1 uppercase" maxlength="3">
                    </div>
                    <div>
                        <label class="form-label">{{ __('Timezone') }}</label>
                        <input wire:model="timezone" type="text" class="form-input mt-1">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">{{ __('Enabled locales (comma-separated)') }}</label>
                        <input wire:model="locales" type="text" class="form-input mt-1" placeholder="en,fr">
                    </div>
                    <div>
                        <label class="form-label">{{ __('Default locale') }}</label>
                        <input wire:model="defaultLocale" type="text" class="form-input mt-1" placeholder="fr">
                    </div>
                </div>
            </div>
            <div class="mt-6 flex justify-end">
                <button wire:click="nextStep" class="btn-primary">{{ __('Continue') }} →</button>
            </div>
        @endif

        {{-- Step 4: Admin account --}}
        @if($step === 4)
            <h2 class="text-lg font-semibold mb-4">{{ __('First Admin Account') }}</h2>
            <p class="text-sm text-gray-500 mb-4">{{ __('This account will have full administrative access. Enable two-factor authentication after first login.') }}</p>
            <div class="space-y-4">
                <div>
                    <label class="form-label">{{ __('Full name') }} *</label>
                    <input wire:model="adminName" type="text" class="form-input mt-1">
                    @error('adminName') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">{{ __('Email address') }} *</label>
                    <input wire:model="adminEmail" type="email" class="form-input mt-1">
                    @error('adminEmail') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">{{ __('Password') }} * <span class="text-xs text-gray-400">({{ __('minimum 12 characters') }})</span></label>
                    <input wire:model="adminPassword" type="password" class="form-input mt-1">
                    @error('adminPassword') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">{{ __('Confirm password') }} *</label>
                    <input wire:model="adminPasswordConfirmation" type="password" class="form-input mt-1">
                </div>
            </div>
            <div class="mt-6 flex justify-end">
                <button wire:click="nextStep" class="btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>{{ __('Complete installation') }}</span>
                    <span wire:loading>{{ __('Installing…') }}</span>
                </button>
            </div>
        @endif

        {{-- Step 5: Done --}}
        @if($step === 5)
            <div class="text-center py-6">
                <div class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-green-100 mb-4">
                    <svg class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <h2 class="text-xl font-semibold text-gray-900 mb-2">{{ __('Installation complete!') }}</h2>
                <p class="text-sm text-gray-500 mb-6">
                    {{ __('Party CRM is installed. Sign in with the admin account you just created, then enable two-factor authentication immediately.') }}
                </p>
                <a href="{{ route('login') }}" class="btn-primary">{{ __('Go to sign-in') }}</a>
            </div>
        @endif

    </div>
</div>

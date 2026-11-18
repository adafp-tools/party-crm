<div>
    <h1 class="text-2xl font-bold mb-6">{{ __('Record Payment') }}</h1>

    @if (session('success'))
        <div class="alert-success mb-4">{{ session('success') }}</div>
    @endif

    <form wire:submit="save" class="card space-y-4">
        @unless ($member)
            <div>
                <label class="label">{{ __('Member') }} *</label>
                <input wire:model="memberId" type="text" class="input @error('memberId') input-error @enderror"
                    placeholder="{{ __('Member ID') }}" />
                @error('memberId') <p class="error-text">{{ $message }}</p> @enderror
            </div>
        @else
            <div class="bg-gray-50 rounded p-3 text-sm">
                {{ __('Recording payment for:') }} <strong>{{ $member->fullName() }}</strong>
                ({{ $member->membership_no ?? __('No number') }})
            </div>
        @endunless

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="label">{{ __('Dues Plan') }}</label>
                <select wire:model.live="duesPlanId" class="input">
                    <option value="">{{ __('— custom amount —') }}</option>
                    @foreach ($plans as $plan)
                        <option value="{{ $plan->id }}">{{ $plan->name }} ({{ number_format($plan->amount, 2) }} {{ $plan->currency }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">{{ __('Method') }} *</label>
                <select wire:model.live="method" class="input">
                    <option value="cash">{{ __('Cash') }}</option>
                    <option value="telebirr">Telebirr</option>
                    <option value="wave">Wave</option>
                    <option value="orange_money">Orange Money</option>
                    <option value="bank">{{ __('Bank transfer') }}</option>
                    <option value="other">{{ __('Other') }}</option>
                </select>
            </div>
            <div>
                <label class="label">{{ __('Amount') }} *</label>
                <input wire:model="amount" type="number" step="0.01" min="0.01" class="input @error('amount') input-error @enderror" />
                @error('amount') <p class="error-text">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">{{ __('Currency') }}</label>
                <input wire:model="currency" type="text" class="input" maxlength="10" />
            </div>

            @if (in_array($method, ['telebirr', 'wave', 'orange_money', 'bank']))
                <div>
                    <label class="label">{{ __('Reference') }} *</label>
                    <input wire:model="reference" type="text" class="input @error('reference') input-error @enderror"
                        placeholder="{{ __('Transaction reference') }}" />
                    @error('reference') <p class="error-text">{{ $message }}</p> @enderror
                </div>
            @endif

            @if ($method === 'cash')
                <div>
                    <label class="label">{{ __('Receipt Number') }} *</label>
                    <input wire:model="receiptNo" type="text" class="input @error('receiptNo') input-error @enderror" />
                    @error('receiptNo') <p class="error-text">{{ $message }}</p> @enderror
                </div>
            @endif

            <div>
                <label class="label">{{ __('Period From') }}</label>
                <input wire:model="periodFrom" type="date" class="input" />
            </div>
            <div>
                <label class="label">{{ __('Period To') }}</label>
                <input wire:model="periodTo" type="date" class="input" />
            </div>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="btn-primary">{{ __('Record Payment') }}</button>
            @if ($member)
                <a href="{{ route('members.show', $member) }}" class="btn-secondary">{{ __('Cancel') }}</a>
            @endif
        </div>
    </form>
</div>

<div>
    <h1 class="text-2xl font-bold mb-6">{{ __('Reconcile Payments') }}</h1>

    @if (session('success'))
        <div class="alert-success mb-4">{{ session('success') }}</div>
    @endif

    {{-- Step 1 --}}
    @if ($step === 1)
        <div class="card">
            <h2 class="card-title">{{ __('Step 1: Upload CSV') }}</h2>
            <p class="text-sm text-gray-500 mb-4">{{ __('Upload a CSV export from your mobile-money merchant account or bank statement.') }}</p>
            <input wire:model="csvFile" type="file" accept=".csv,.txt" class="input" />
            @error('csvFile') <p class="error-text">{{ $message }}</p> @enderror
            <div wire:loading wire:target="csvFile" class="mt-2 text-sm text-gray-500">{{ __('Parsing…') }}</div>
        </div>
    @endif

    {{-- Step 2: Column mapping --}}
    @if ($step === 2)
        <div class="card">
            <h2 class="card-title">{{ __('Step 2: Map Columns') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                @foreach (['reference' => __('Reference / Transaction ID'), 'amount' => __('Amount'), 'date' => __('Date')] as $key => $label)
                    <div>
                        <label class="label">{{ $label }}</label>
                        <select wire:model="mapping.{{ $key }}" class="input">
                            <option value="">{{ __('— skip —') }}</option>
                            @foreach ($headers as $h)
                                <option value="{{ $h }}">{{ $h }}</option>
                            @endforeach
                        </select>
                        @if ($key !== 'date')
                            @error("mapping.{$key}") <p class="error-text">{{ $message }}</p> @enderror
                        @endif
                    </div>
                @endforeach
            </div>
            <div class="overflow-x-auto mb-4">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>@foreach ($headers as $h)<th class="th">{{ $h }}</th>@endforeach</tr>
                    </thead>
                    <tbody>
                        @foreach ($preview as $row)
                            <tr class="divide-x">@foreach ($headers as $h)<td class="td text-xs">{{ $row[$h] ?? '' }}</td>@endforeach</tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <button wire:click="findMatches" class="btn-primary">{{ __('Find Matches →') }}</button>
        </div>
    @endif

    {{-- Step 3: Review matches --}}
    @if ($step === 3)
        <div class="card">
            <h2 class="card-title">{{ __('Step 3: Review Matches') }}</h2>
            <p class="text-sm text-gray-500 mb-4">{{ __('Review the matched payments below. Uncheck any you do not want to reconcile.') }}</p>
            <div class="overflow-x-auto mb-4">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="th">{{ __('Accept') }}</th>
                            <th class="th">{{ __('CSV Ref') }}</th>
                            <th class="th">{{ __('CSV Amount') }}</th>
                            <th class="th">{{ __('Member') }}</th>
                            <th class="th">{{ __('Payment Amount') }}</th>
                            <th class="th">{{ __('Match') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($matches as $i => $m)
                            <tr class="{{ $m['payment_id'] ? '' : 'bg-yellow-50' }}">
                                <td class="td">
                                    @if ($m['payment_id'])
                                        <input type="checkbox" class="checkbox"
                                            wire:model="matches.{{ $i }}.accept" />
                                    @else
                                        <span class="text-gray-400 text-xs">{{ __('No match') }}</span>
                                    @endif
                                </td>
                                <td class="td font-mono text-xs">{{ $m['csv_ref'] }}</td>
                                <td class="td font-mono text-xs">{{ number_format($m['csv_amount'], 2) }}</td>
                                <td class="td text-xs">{{ $m['member_name'] ?? '—' }}</td>
                                <td class="td font-mono text-xs">{{ $m['payment_amount'] ? number_format($m['payment_amount'], 2) : '—' }}</td>
                                <td class="td text-xs">
                                    @if ($m['payment_id'])
                                        <span class="{{ $m['amount_match'] ? 'text-green-600' : 'text-orange-500' }}">
                                            {{ $m['amount_match'] ? '✓' : __('Amount differs') }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="flex gap-2">
                <button wire:click="acceptAll" class="btn-primary">{{ __('Reconcile Accepted') }}</button>
                <button wire:click="$set('step', 2)" class="btn-secondary">{{ __('← Back') }}</button>
            </div>
        </div>
    @endif
</div>

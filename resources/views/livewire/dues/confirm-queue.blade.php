<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">{{ __('Payment Confirmation Queue') }}</h1>
        @if (count($selected) > 0)
            <button wire:click="$set('showBulkConfirm', true)" class="btn-primary">
                {{ __('Bulk Confirm (:n)', ['n' => count($selected)]) }}
            </button>
        @endif
    </div>

    @if (session('success'))
        <div class="alert-success mb-4">{{ session('success') }}</div>
    @endif

    <div class="mb-4">
        <select wire:model.live="filterMethod" class="input w-48">
            <option value="">{{ __('All methods') }}</option>
            <option value="cash">{{ __('Cash') }}</option>
            <option value="telebirr">Telebirr</option>
            <option value="wave">Wave</option>
            <option value="orange_money">Orange Money</option>
            <option value="bank">{{ __('Bank') }}</option>
            <option value="other">{{ __('Other') }}</option>
        </select>
    </div>

    @if ($showBulkConfirm)
        <div class="card mb-4">
            <h3 class="font-semibold mb-2">{{ __('Bulk confirm :n payments', ['n' => count($selected)]) }}</h3>
            <input wire:model="bulkReason" type="text" class="input mb-3" placeholder="{{ __('Reason (optional)') }}" />
            <div class="flex gap-2">
                <button wire:click="bulkConfirm" class="btn-primary">{{ __('Confirm All') }}</button>
                <button wire:click="$set('showBulkConfirm', false)" class="btn-secondary">{{ __('Cancel') }}</button>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="th w-8"><input type="checkbox" class="checkbox"
                        x-on:change="$event.target.checked
                            ? $wire.selected = {{ json_encode($payments->pluck('id')->toArray()) }}
                            : $wire.selected = []" /></th>
                    <th class="th">{{ __('Member') }}</th>
                    <th class="th">{{ __('Amount') }}</th>
                    <th class="th">{{ __('Method') }}</th>
                    <th class="th">{{ __('Reference / Receipt') }}</th>
                    <th class="th">{{ __('Recorded by') }}</th>
                    <th class="th">{{ __('Date') }}</th>
                    <th class="th"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($payments as $payment)
                    <tr>
                        <td class="td w-8">
                            <input type="checkbox" class="checkbox"
                                wire:model="selected" value="{{ $payment->id }}" />
                        </td>
                        <td class="td">
                            <a href="{{ route('members.show', $payment->member_id) }}" class="link font-medium">
                                {{ $payment->member?->fullName() }}
                            </a>
                        </td>
                        <td class="td font-mono text-sm">{{ number_format($payment->amount, 2) }} {{ $payment->currency }}</td>
                        <td class="td text-sm">{{ __(ucfirst(str_replace('_', ' ', $payment->method))) }}</td>
                        <td class="td font-mono text-sm">{{ $payment->reference ?? $payment->receipt_no ?? '—' }}</td>
                        <td class="td text-sm">{{ $payment->recordedBy?->name ?? '—' }}</td>
                        <td class="td text-sm">{{ $payment->created_at->format('Y-m-d') }}</td>
                        <td class="td text-right text-sm flex gap-2 justify-end">
                            <button wire:click="confirm('{{ $payment->id }}')"
                                class="text-green-600 hover:underline">{{ __('Confirm') }}</button>
                            <button wire:click="reject('{{ $payment->id }}')"
                                class="text-red-600 hover:underline">{{ __('Reject') }}</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="td text-center text-gray-500">{{ __('No payments awaiting confirmation.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t">{{ $payments->links() }}</div>
    </div>
</div>

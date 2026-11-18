<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">{{ __('Campaigns') }}</h1>
        @can('create', \App\Models\Campaign::class)
            <a href="{{ route('campaigns.create') }}" class="btn-primary">{{ __('New Campaign') }}</a>
        @endcan
    </div>

    @if (session('success'))
        <div class="alert-success mb-4">{{ session('success') }}</div>
    @endif

    {{-- Filters --}}
    <div class="flex flex-wrap gap-3 mb-4">
        <select wire:model.live="filterType" class="input w-36">
            <option value="">{{ __('All types') }}</option>
            <option value="sms">{{ __('SMS') }}</option>
            <option value="email">{{ __('Email') }}</option>
        </select>
        <select wire:model.live="filterStatus" class="input w-40">
            <option value="">{{ __('All statuses') }}</option>
            <option value="draft">{{ __('Draft') }}</option>
            <option value="sending">{{ __('Sending') }}</option>
            <option value="sent">{{ __('Sent') }}</option>
            <option value="failed">{{ __('Failed') }}</option>
        </select>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="th">{{ __('Title') }}</th>
                    <th class="th">{{ __('Type') }}</th>
                    <th class="th">{{ __('Target unit') }}</th>
                    <th class="th">{{ __('Status') }}</th>
                    <th class="th text-right">{{ __('Recipients') }}</th>
                    <th class="th">{{ __('Sent') }}</th>
                    <th class="th"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($campaigns as $campaign)
                    <tr>
                        <td class="td font-medium">{{ $campaign->title }}</td>
                        <td class="td text-sm">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                {{ $campaign->type === 'sms' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                {{ strtoupper($campaign->type) }}
                            </span>
                        </td>
                        <td class="td text-sm">{{ $campaign->orgUnit?->name ?? __('All units') }}</td>
                        <td class="td text-sm">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                {{ match($campaign->status) {
                                    'draft'    => 'bg-gray-100 text-gray-700',
                                    'sending'  => 'bg-yellow-100 text-yellow-800',
                                    'sent'     => 'bg-green-100 text-green-800',
                                    'failed'   => 'bg-red-100 text-red-800',
                                } }}">
                                {{ __(ucfirst($campaign->status)) }}
                            </span>
                        </td>
                        <td class="td text-right text-sm">
                            {{ $campaign->status === 'draft' ? '—' : number_format($campaign->recipient_count) }}
                        </td>
                        <td class="td text-sm">{{ $campaign->sent_at?->format('d M Y H:i') ?? '—' }}</td>
                        <td class="td text-right text-sm flex gap-2 justify-end">
                            @can('update', $campaign)
                                <a href="{{ route('campaigns.edit', $campaign) }}" class="link">{{ __('Edit') }}</a>
                            @endcan
                            @can('delete', $campaign)
                                <button wire:click="delete('{{ $campaign->id }}')"
                                    wire:confirm="{{ __('Delete this campaign?') }}"
                                    class="link text-red-600">{{ __('Delete') }}</button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="td text-center text-gray-500">{{ __('No campaigns found.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t">{{ $campaigns->links() }}</div>
    </div>
</div>

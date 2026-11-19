<div x-data="{ exportOpen: false }">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">{{ __('Reports') }}</h1>
        <div class="flex gap-2">
            @can('exportMembers')
                <button @click="exportOpen = true" class="btn-secondary text-sm">
                    ↓ {{ __('Export members (CSV)') }}
                </button>
            @endcan
            @can('viewAuditLog')
                <a href="{{ route('reports.audit-log') }}" class="btn-secondary text-sm">
                    {{ __('Audit log') }}
                </a>
            @endcan
        </div>
    </div>

    {{-- Member export modal --}}
    <div x-show="exportOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
         @keydown.escape.window="exportOpen = false">
        <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-md mx-4" @click.stop>
            <h2 class="text-lg font-semibold text-gray-900 mb-1">{{ __('Export members') }}</h2>
            <p class="text-sm text-gray-500 mb-4">{{ __('This export will be logged. Please state why you need it.') }}</p>
            <form method="POST" action="{{ route('exports.members') }}">
                @csrf
                <div>
                    <label for="reason" class="form-label">{{ __('Reason for export') }} <span class="text-red-500">*</span></label>
                    <textarea id="reason" name="reason" rows="3" required minlength="5"
                              class="form-input mt-1 text-sm"
                              placeholder="{{ __('e.g. Annual membership audit for national congress') }}"></textarea>
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" @click="exportOpen = false" class="btn-secondary">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn-primary">{{ __('Download CSV') }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Membership by status --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 mb-8">
        <div class="card p-5">
            <h2 class="card-title">{{ __('Members by status') }}</h2>
            <table class="min-w-full text-sm mt-3">
                <tbody class="divide-y divide-gray-100">
                    @foreach (['active' => 'text-green-700', 'applicant' => 'text-blue-700', 'suspended' => 'text-yellow-700', 'inactive' => 'text-gray-500', 'deceased' => 'text-gray-400'] as $status => $colour)
                        <tr>
                            <td class="py-2 pr-4 font-medium {{ $colour }}">{{ __(ucfirst($status)) }}</td>
                            <td class="py-2 text-right font-mono">{{ number_format($stats['memberCounts'][$status]) }}</td>
                        </tr>
                    @endforeach
                    <tr class="font-semibold">
                        <td class="py-2 pr-4">{{ __('Total') }}</td>
                        <td class="py-2 text-right font-mono">{{ number_format(array_sum($stats['memberCounts'])) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Dues summary --}}
        <div class="card p-5">
            <h2 class="card-title">{{ __('Dues – current month') }}</h2>
            <div class="mt-3 space-y-3">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">{{ __('Confirmed payments') }}</span>
                    <span class="font-semibold font-mono">{{ number_format($stats['dues']['count']) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">{{ __('Total amount') }}</span>
                    <span class="font-semibold font-mono">{{ number_format($stats['dues']['total'], 0, '.', ',') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Active members by unit --}}
    <div class="card p-5 mb-8">
        <div class="flex items-center justify-between mb-3">
            <h2 class="card-title">{{ __('Active members by unit') }}</h2>
            <p class="text-xs text-gray-500">
                {{ __('Counts below :n are shown as "<5" to protect member privacy.', ['n' => 5]) }}
            </p>
        </div>
        @if ($stats['byUnit']->isEmpty())
            <p class="text-sm text-gray-500">{{ __('No units found.') }}</p>
        @else
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="th text-left">{{ __('Unit') }}</th>
                        <th class="th text-right">{{ __('Active members') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($stats['byUnit'] as $row)
                        <tr>
                            <td class="td">{{ $row['name'] }}</td>
                            <td class="td text-right font-mono
                                {{ $row['suppressed'] ? 'text-gray-400 italic' : '' }}">
                                {{ $row['display'] }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Events & tasks --}}
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div class="card p-5">
            <h2 class="card-title">{{ __('Upcoming events') }}</h2>
            <p class="mt-2 text-3xl font-semibold font-heading text-blue-700">
                {{ number_format($stats['upcomingEvents']) }}
            </p>
        </div>
        <div class="card p-5">
            <h2 class="card-title">{{ __('Overdue tasks') }}</h2>
            <p class="mt-2 text-3xl font-semibold font-heading {{ $stats['overdueTasks'] > 0 ? 'text-red-600' : 'text-gray-900' }}">
                {{ number_format($stats['overdueTasks']) }}
            </p>
        </div>
    </div>
</div>

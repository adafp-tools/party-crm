<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">{{ __('Reports') }}</h1>
        @if (auth()->user()->hasRole('national_admin'))
            <a href="{{ route('reports.audit-export') }}" class="btn-secondary text-sm">
                ↓ {{ __('Download audit log (CSV)') }}
            </a>
        @endif
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

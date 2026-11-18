@extends('layouts.app')

@section('title', __('Dashboard'))

@section('content')

    {{-- KPI cards --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 mb-8">
        <div class="card p-5">
            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Total members') }}</dt>
            <dd class="mt-2 text-3xl font-semibold font-heading text-gray-900">
                {{ number_format(array_sum($memberCounts)) }}
            </dd>
        </div>
        <div class="card p-5">
            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Active') }}</dt>
            <dd class="mt-2 text-3xl font-semibold font-heading text-green-700">
                {{ number_format($memberCounts['active']) }}
            </dd>
        </div>
        <div class="card p-5">
            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Upcoming events') }}</dt>
            <dd class="mt-2 text-3xl font-semibold font-heading text-blue-700">
                {{ number_format($upcomingEvents) }}
            </dd>
        </div>
        <div class="card p-5">
            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Overdue tasks') }}</dt>
            <dd class="mt-2 text-3xl font-semibold font-heading {{ $overdueTasks > 0 ? 'text-red-600' : 'text-gray-900' }}">
                {{ number_format($overdueTasks) }}
            </dd>
        </div>
    </div>

    {{-- Membership status breakdown --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="card p-5">
            <h2 class="text-sm font-semibold text-gray-700 mb-4 uppercase tracking-wide">{{ __('Members by status') }}</h2>
            <table class="min-w-full text-sm">
                <tbody class="divide-y divide-gray-100">
                    @foreach (['active' => 'text-green-700', 'applicant' => 'text-blue-700', 'suspended' => 'text-yellow-700', 'inactive' => 'text-gray-500', 'deceased' => 'text-gray-400'] as $status => $colour)
                        @if ($memberCounts[$status] > 0)
                            <tr>
                                <td class="py-2 pr-4 font-medium {{ $colour }}">{{ __(ucfirst($status)) }}</td>
                                <td class="py-2 text-right font-mono text-gray-900">{{ number_format($memberCounts[$status]) }}</td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Dues this month --}}
        <div class="card p-5">
            <h2 class="text-sm font-semibold text-gray-700 mb-4 uppercase tracking-wide">{{ __('Dues this month') }}</h2>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">{{ __('Confirmed payments') }}</span>
                    <span class="text-xl font-semibold font-heading text-gray-900">{{ number_format($dues['count']) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">{{ __('Total collected') }}</span>
                    <span class="text-xl font-semibold font-heading text-gray-900">
                        {{ number_format($dues['total'], 0, '.', ',') }}
                    </span>
                </div>
            </div>
            @if (Route::has('reports.index'))
                <div class="mt-4">
                    <a href="{{ route('reports.index') }}" class="text-sm link">{{ __('View full reports →') }}</a>
                </div>
            @endif
        </div>
    </div>

@endsection

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

    {{-- New members this month + sparkline --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3 mb-8">

        {{-- New members tile --}}
        @php
            $delta = $newMembersThisMonth - $newMembersLastMonth;
            $deltaSign = $delta > 0 ? '+' : ($delta < 0 ? '' : '');
            $deltaColour = $delta > 0 ? 'text-green-700' : ($delta < 0 ? 'text-red-600' : 'text-gray-500');
        @endphp
        <div class="card p-5 flex flex-col justify-between">
            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('New members this month') }}</dt>
            <dd class="mt-2 text-3xl font-semibold font-heading text-gray-900">
                {{ number_format($newMembersThisMonth) }}
            </dd>
            <dd class="mt-1 text-sm {{ $deltaColour }}">
                {{ $deltaSign }}{{ number_format($delta) }} {{ __('vs last month') }}
            </dd>
        </div>

        {{-- Member growth sparkline (last 6 months) --}}
        <div class="card p-5 col-span-2"
             x-data="{
                 init() {
                     new window.Chart(this.$refs.canvas, {
                         type: 'line',
                         data: {
                             labels: {{ Js::from(array_column($sparkline, 'label')) }},
                             datasets: [{
                                 label: {{ Js::from(__('New members')) }},
                                 data: {{ Js::from(array_column($sparkline, 'count')) }},
                                 fill: true,
                                 tension: 0.4,
                                 borderColor: 'var(--color-primary-600, #6366f1)',
                                 backgroundColor: 'color-mix(in oklab, var(--color-primary-600, #6366f1) 15%, transparent)',
                                 pointRadius: 3,
                                 pointHoverRadius: 5,
                             }]
                         },
                         options: {
                             responsive: true,
                             maintainAspectRatio: false,
                             plugins: { legend: { display: false } },
                             scales: {
                                 y: {
                                     beginAtZero: true,
                                     ticks: { precision: 0, color: '#6b7280', font: { size: 11 } },
                                     grid: { color: '#f3f4f6' },
                                 },
                                 x: {
                                     ticks: { color: '#6b7280', font: { size: 11 } },
                                     grid: { display: false },
                                 },
                             },
                         }
                     });
                 }
             }">
            <h2 class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-3">{{ __('Member growth (6 months)') }}</h2>
            <div class="relative h-36">
                <canvas x-ref="canvas"></canvas>
            </div>
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

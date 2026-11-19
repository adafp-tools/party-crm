<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ \App\Models\Setting::get('party_name', config('app.name')) }} – @yield('title', __('Dashboard'))</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600|space-grotesk:500,600,700|noto-sans-ethiopic:400,500,700&display=swap" rel="stylesheet">
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gray-50">

    @if(\App\Models\Setting::get('is_demo') === '1')
        <div class="demo-banner">{{ __('DEMO DATA') }} — {{ __('This is a demo installation. Data shown is fictitious.') }}</div>
    @endif

    <div x-data="{ sidebarOpen: true }" class="flex h-full">

        {{-- Sidebar --}}
        <aside
            x-show="sidebarOpen"
            class="flex flex-col w-64 bg-primary-600 text-white min-h-screen flex-shrink-0"
        >
            {{-- Party name / logo area --}}
            <div class="flex h-16 items-center px-4 border-b border-primary-500">
                <span class="font-heading text-lg font-semibold tracking-wide truncate">
                    {{ \App\Models\Setting::get('party_short_name', config('app.name')) }}
                </span>
            </div>

            {{-- Nav --}}
            <nav class="flex-1 overflow-y-auto py-4 space-y-1 px-2">
                @php
                    $navItems = [
                        ['route' => 'dashboard', 'label' => __('Dashboard'), 'icon' => 'squares-2x2'],
                        ['route' => 'members.index', 'label' => __('Members'), 'icon' => 'users'],
                        ['route' => 'structure.index', 'label' => __('Structure'), 'icon' => 'building-office-2'],
                        ['route' => 'dues.index', 'label' => __('Dues'), 'icon' => 'banknotes'],
                        ['route' => 'events.index', 'label' => __('Events'), 'icon' => 'calendar-days'],
                        ['route' => 'tasks.index', 'label' => __('Tasks'), 'icon' => 'check-circle'],
                        ['route' => 'campaigns.index', 'label' => __('Campaigns'), 'icon' => 'envelope'],
                        ['route' => 'users.index', 'label' => __('Users'), 'icon' => 'users'],
                        ['route' => 'reports.index', 'label' => __('Reports'), 'icon' => 'chart-bar'],
                        ['route' => 'settings.index', 'label' => __('Settings'), 'icon' => 'cog-6-tooth'],
                    ];
                @endphp

                @foreach($navItems as $item)
                    @if(Route::has($item['route']))
                        <a href="{{ route($item['route']) }}"
                           class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors
                               {{ request()->routeIs($item['route'].'*')
                                   ? 'bg-primary-700 text-white'
                                   : 'text-primary-100 hover:bg-primary-700 hover:text-white' }}">
                            <x-nav-icon :name="$item['icon']" class="h-5 w-5 flex-shrink-0" />
                            {{ $item['label'] }}
                        </a>
                    @endif
                @endforeach
            </nav>

            {{-- User footer --}}
            <div class="border-t border-primary-500 p-3">
                <div class="flex items-center gap-3">
                    <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-primary-500 text-xs font-semibold uppercase">
                        {{ substr(auth()->user()->name, 0, 1) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-white">{{ auth()->user()->name }}</p>
                        <p class="truncate text-xs text-primary-200">{{ auth()->user()->getRoleNames()->first() }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" title="{{ __('Sign out') }}" class="text-primary-200 hover:text-white transition-colors">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        {{-- Main content area --}}
        <div class="flex flex-1 flex-col min-w-0">

            {{-- Top bar --}}
            <header class="flex h-16 flex-shrink-0 items-center gap-4 bg-primary-600 px-4 shadow-sm">
                <button @click="sidebarOpen = !sidebarOpen"
                        class="text-primary-100 hover:text-white transition-colors"
                        aria-label="Toggle sidebar">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>
                <h1 class="font-heading text-base font-semibold text-white">@yield('title', __('Dashboard'))</h1>
                <div class="ml-auto flex items-center gap-3 text-xs text-primary-100">
                    {{ \App\Models\Setting::get('party_name') }}
                </div>
            </header>

            {{-- Page content --}}
            <main class="flex-1 overflow-y-auto p-6">
                @if(session('success'))
                    <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800 ring-1 ring-green-200">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-800 ring-1 ring-red-200">
                        {{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </main>

        </div>
    </div>

</body>
</html>

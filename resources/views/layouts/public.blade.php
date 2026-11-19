<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ config('locales.' . app()->getLocale() . '.dir', 'ltr') }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ \App\Models\Setting::get('party_name', config('app.name')) }} – @yield('title', __('Join the party'))</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600|space-grotesk:500,600,700|noto-sans-arabic:400,500,700&display=swap" rel="stylesheet">
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @php $partyColor = \App\Models\Setting::get('party_color', ''); @endphp
    @if($partyColor)
    <style>
        :root {
            --party-base: {{ $partyColor }};
            --color-primary-50:  color-mix(in oklab, var(--party-base)  5%, white);
            --color-primary-100: color-mix(in oklab, var(--party-base) 15%, white);
            --color-primary-200: color-mix(in oklab, var(--party-base) 30%, white);
            --color-primary-300: color-mix(in oklab, var(--party-base) 50%, white);
            --color-primary-400: color-mix(in oklab, var(--party-base) 70%, white);
            --color-primary-500: color-mix(in oklab, var(--party-base) 85%, white);
            --color-primary-600: var(--party-base);
            --color-primary-700: color-mix(in oklab, var(--party-base) 85%, black);
            --color-primary-800: color-mix(in oklab, var(--party-base) 70%, black);
            --color-primary-900: color-mix(in oklab, var(--party-base) 55%, black);
        }
    </style>
    @endif
</head>
<body class="min-h-full bg-gray-50">

    {{-- Header --}}
    <header class="bg-primary-600 text-white">
        <div class="mx-auto max-w-2xl px-4 py-4 flex items-center gap-3">
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-primary-500">
                <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                </svg>
            </span>
            <span class="font-heading text-lg font-semibold">
                {{ \App\Models\Setting::get('party_name', config('app.name')) }}
            </span>

            {{-- Locale switcher --}}
            <div class="ms-auto flex items-center gap-2 text-xs text-primary-100">
                @foreach(array_filter(explode(',', \App\Models\Setting::get('locales', 'en'))) as $loc)
                    <form method="POST" action="{{ route('locale.switch') }}" class="inline">
                        @csrf
                        <input type="hidden" name="locale" value="{{ trim($loc) }}">
                        <button type="submit" class="{{ app()->getLocale() === trim($loc) ? 'font-semibold text-white' : 'hover:text-white' }}">
                            {{ config('locales.' . trim($loc) . '.native', trim($loc)) }}
                        </button>
                    </form>
                @endforeach
            </div>
        </div>
    </header>

    {{-- Page content --}}
    <main class="mx-auto max-w-2xl px-4 py-10">
        @yield('content')
        {{ $slot ?? '' }}
    </main>

    {{-- Privacy footer --}}
    <footer class="mt-8 border-t border-gray-200 bg-white py-4 text-center text-xs text-gray-400">
        {{ \App\Models\Setting::get('party_name') }}
        @if(\App\Models\Setting::get('party_website'))
            · <a href="{{ \App\Models\Setting::get('party_website') }}" class="hover:text-gray-600" target="_blank" rel="noopener">{{ \App\Models\Setting::get('party_website') }}</a>
        @endif
        · <a href="{{ route('privacy') }}" class="hover:text-gray-600">{{ __('Privacy Notice') }}</a>
    </footer>

</body>
</html>

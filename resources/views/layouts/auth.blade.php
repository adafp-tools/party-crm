<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ \App\Models\Setting::get('party_name', config('app.name')) }} – @yield('title')</title>
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gray-50">

    @if(\App\Models\Setting::get('is_demo') === '1')
        <div class="demo-banner">{{ __('DEMO DATA') }} — {{ __('This is a demo installation. Data shown is fictitious.') }}</div>
    @endif

    <div class="flex min-h-full flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="flex justify-center">
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-primary-600">
                    <svg class="h-7 w-7 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                    </svg>
                </span>
            </div>
            <h2 class="mt-4 text-center text-2xl font-bold leading-9 tracking-tight text-gray-900">
                {{ \App\Models\Setting::get('party_name', config('app.name')) }}
            </h2>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="card px-6 py-8 sm:px-10">
                @yield('content')
            </div>

            {{-- Locale switcher --}}
            <div class="mt-4 flex justify-center gap-3 text-xs text-gray-500">
                @foreach(array_filter(explode(',', \App\Models\Setting::get('locales', 'en'))) as $locale)
                    <form method="POST" action="{{ route('locale.switch') }}" class="inline">
                        @csrf
                        <input type="hidden" name="locale" value="{{ trim($locale) }}">
                        <button type="submit" class="{{ app()->getLocale() === trim($locale) ? 'font-semibold text-primary-600' : 'hover:text-gray-800' }}">
                            {{ match(trim($locale)) {
                                'fr' => 'Français',
                                'am' => 'አማርኛ',
                                default => 'English',
                            } }}
                        </button>
                    </form>
                @endforeach
            </div>
        </div>
    </div>

</body>
</html>

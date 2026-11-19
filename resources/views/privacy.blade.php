@extends('layouts.public')

@section('title', __('Privacy Notice'))

@section('content')
    <div class="prose prose-sm max-w-none">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">{{ __('Privacy Notice') }}</h1>

        @php $notice = \App\Models\Setting::get('privacy_notice'); @endphp

        @if($notice)
            {!! nl2br(e($notice)) !!}
        @else
            <p class="text-gray-700 leading-relaxed">
                {{ __('This application collects and processes personal data on behalf of the party for membership management purposes. Data is stored securely and is not shared with third parties without your consent.') }}
            </p>
            <p class="mt-4 text-gray-700 leading-relaxed">
                {{ __('You have the right to access, correct, or request deletion of your personal data. To exercise these rights, contact your party administrator.') }}
            </p>
            <p class="mt-4 text-gray-700 leading-relaxed">
                {{ __('Data collected: name, date of birth, contact details, membership status, and party unit assignment.') }}
            </p>
        @endif
    </div>
@endsection

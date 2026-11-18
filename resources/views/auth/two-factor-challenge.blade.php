@extends('layouts.auth')

@section('title', __('Two-factor authentication'))

@section('content')
    <h2 class="text-center text-xl font-semibold text-gray-900 mb-2">
        {{ __('Two-factor authentication') }}
    </h2>
    <p class="text-center text-sm text-gray-500 mb-6">
        {{ __('Enter the code from your authenticator app') }}
    </p>

    @if($errors->any())
        <div class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-800 ring-1 ring-red-200">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('two-factor.verify') }}" class="space-y-5">
        @csrf

        <div>
            <label for="code" class="form-label">{{ __('Authentication code') }}</label>
            <input id="code" type="text" name="code"
                   required autofocus autocomplete="one-time-code"
                   inputmode="numeric" maxlength="6"
                   class="form-input mt-1 text-center tracking-[0.5em] text-lg @error('code') ring-red-500 @enderror"
                   placeholder="000000">
        </div>

        <button type="submit" class="btn-primary w-full justify-center">
            {{ __('Verify') }}
        </button>
    </form>
@endsection

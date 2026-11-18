@extends('layouts.auth')

@section('title', __('Set up two-factor authentication'))

@section('content')
    <h2 class="text-center text-xl font-semibold text-gray-900 mb-2">
        {{ __('Set up two-factor authentication') }}
    </h2>
    <p class="text-center text-sm text-gray-500 mb-6">
        {{ __('Scan this QR code with your authenticator app') }}
    </p>

    @if($errors->any())
        <div class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-800 ring-1 ring-red-200">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="flex justify-center mb-4">
        {!! $qrCodeSvg !!}
    </div>

    <details class="mb-5 text-sm text-gray-500">
        <summary class="cursor-pointer text-primary-600 hover:text-primary-500">
            {{ __('Or enter this code manually') }}
        </summary>
        <code class="mt-2 block break-all rounded bg-gray-100 px-3 py-2 font-mono text-xs text-gray-800">
            {{ $secret }}
        </code>
    </details>

    <form method="POST" action="{{ route('two-factor.setup.confirm') }}" class="space-y-5">
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
            {{ __('Confirm and enable') }}
        </button>
    </form>
@endsection

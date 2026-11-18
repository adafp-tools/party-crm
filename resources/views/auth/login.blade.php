@extends('layouts.auth')

@section('title', __('Sign in'))

@section('content')
    <h2 class="text-center text-xl font-semibold text-gray-900 mb-6">
        {{ __('Sign in to your account') }}
    </h2>

    @if($errors->any())
        <div class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-800 ring-1 ring-red-200">
            {{ $errors->first() }}
        </div>
    @endif

    @if(session('status'))
        <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800 ring-1 ring-green-200">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="form-label">{{ __('Email address') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   required autofocus autocomplete="email"
                   class="form-input mt-1 @error('email') ring-red-500 @enderror">
        </div>

        <div>
            <label for="password" class="form-label">{{ __('Password') }}</label>
            <input id="password" type="password" name="password"
                   required autocomplete="current-password"
                   class="form-input mt-1 @error('password') ring-red-500 @enderror">
        </div>

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="remember" class="rounded border-gray-300 text-primary-600">
                {{ __('Remember me') }}
            </label>
            @if(Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-sm text-primary-600 hover:text-primary-500">
                    {{ __('Forgot your password?') }}
                </a>
            @endif
        </div>

        <button type="submit" class="btn-primary w-full justify-center">
            {{ __('Sign in') }}
        </button>
    </form>
@endsection

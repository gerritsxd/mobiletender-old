@extends('layouts.auth')

@section('title', __('Login') . ' — ' . config('app.name'))

@section('content')
    <h2 class="mb-6 text-center text-xl font-semibold text-slate-900">{{ __('Login') }}</h2>
    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <div>
            <label for="email" class="label-tw">{{ __('E-Mail Address') }}</label>
            <input id="email" type="email" class="input-tw mt-1 w-full @error('email') border-red-500 @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="password" class="label-tw">{{ __('Password') }}</label>
            <input id="password" type="password" class="input-tw mt-1 w-full @error('password') border-red-500 @enderror" name="password" required autocomplete="current-password">
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <label class="flex items-center gap-2">
            <input class="h-4 w-4 rounded border-slate-300 text-brand focus:ring-brand" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
            <span class="text-sm text-slate-700">{{ __('Remember Me') }}</span>
        </label>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <button type="submit" class="btn-primary w-full sm:w-auto">{{ __('Login') }}</button>
            @if (Route::has('password.request'))
                <a class="text-sm text-brand-dark hover:underline" href="{{ route('password.request') }}">{{ __('Forgot Your Password?') }}</a>
            @endif
        </div>
    </form>

    @if (Route::has('register'))
        <p class="mt-6 text-center text-sm text-slate-600">
            {{ __('¿No tienes cuenta?') }}
            <a class="font-semibold text-brand-dark hover:underline" href="{{ route('register') }}">{{ __('Regístrate') }}</a>
        </p>
    @endif
@endsection

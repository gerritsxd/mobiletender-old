@extends('layouts.auth')

@section('title', __('Register') . ' — ' . config('app.name'))

@section('content')
    <h2 class="mb-2 text-center text-xl font-semibold text-slate-900">{{ __('Register') }}</h2>
    @php $grantRole = (string) config('customoptions.register_default_role', ''); @endphp
    @if ($grantRole !== '')
        <p class="mb-6 rounded-lg border border-brand/40 bg-brand-light px-3 py-2 text-center text-sm text-sea">
            {{ __('Entorno de pruebas: tu cuenta tendrá acceso de') }} <b>{{ $grantRole }}</b> {{ __('para probar la app.') }}
        </p>
    @else
        <div class="mb-6"></div>
    @endif
    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf
        <div>
            <label for="name" class="label-tw">{{ __('Name') }}</label>
            <input id="name" type="text" class="input-tw mt-1 w-full @error('name') border-red-500 @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>
            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="email" class="label-tw">{{ __('E-Mail Address') }}</label>
            <input id="email" type="email" class="input-tw mt-1 w-full @error('email') border-red-500 @enderror" name="email" value="{{ old('email') }}" required autocomplete="email">
            @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password" class="label-tw">{{ __('Password') }}</label>
            <input id="password" type="password" class="input-tw mt-1 w-full @error('password') border-red-500 @enderror" name="password" required autocomplete="new-password">
            @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password-confirm" class="label-tw">{{ __('Confirm Password') }}</label>
            <input id="password-confirm" type="password" class="input-tw mt-1 w-full" name="password_confirmation" required autocomplete="new-password">
        </div>
        <button type="submit" class="btn-primary w-full">{{ __('Register') }}</button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
        {{ __('¿Ya tienes cuenta?') }}
        <a class="font-semibold text-brand-dark hover:underline" href="{{ route('login') }}">{{ __('Inicia sesión') }}</a>
    </p>
@endsection

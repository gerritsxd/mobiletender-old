<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    @vite(['resources/css/main.css', 'resources/js/main.js'])
    @stack('head')
</head>
<body class="min-h-screen bg-slate-50 pb-20 text-slate-800 antialiased sm:pb-0" data-page="@yield('page', 'shop')">
<div id="overlay" class="overlay" aria-hidden="true">
    <img src="/img/loader.gif" alt="" class="mx-auto" /><br/>
    <span class="text-slate-600">Loading…</span>
</div>

@include('partials.shop.topbar')

{{-- Live flash offer banners (filled by resources/js/shop/flash-offers.js) --}}
<div id="flash-offer-root" class="pointer-events-none fixed inset-x-0 top-14 z-40 mx-auto max-w-md space-y-2 px-3"></div>

<main class="mx-auto max-w-6xl px-3 py-4 sm:px-4 sm:py-6">
    @include('partials.shop.flash')
    @yield('content')
</main>

@include('partials.shop.bottom-bar')

@stack('scripts')
@yield('scripts')
</body>
</html>

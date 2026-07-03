<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth<meta name="mt-staff" content="1">@endauth
    <title>@yield('title', config('app.name'))</title>
    @vite(['resources/css/main.css', 'resources/js/main.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased" data-page="@yield('page', 'admin')" x-data="{ sidebarOpen: false }">
<div id="overlay" class="overlay">
    <img src="/img/loader.gif" alt="Loading" class="mx-auto" /><br/>
    <span class="text-slate-600">Loading…</span>
</div>

<div class="flex min-h-screen">
    {{-- Sidebar desktop --}}
    <aside class="hidden w-64 shrink-0 border-r border-slate-200 bg-white lg:block">
        @include('partials.admin.sidebar')
    </aside>
    {{-- Mobile sidebar --}}
    <div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-50 bg-black/40 lg:hidden" style="display: none;" @click="sidebarOpen = false"></div>
    <aside x-show="sidebarOpen" x-transition class="fixed inset-y-0 left-0 z-50 w-64 overflow-y-auto border-r border-slate-200 bg-white lg:hidden" style="display: none;">
        @include('partials.admin.sidebar')
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        @include('partials.admin.topbar')
        <main class="flex-1 p-4 lg:p-6">
            @hasSection('page_header')
                <div class="mb-6">
                    @yield('page_header')
                </div>
            @endif
            @include('partials.shared.flash')
            @yield('content')
        </main>
    </div>
</div>
@stack('scripts')
@yield('scripts')
</body>
</html>

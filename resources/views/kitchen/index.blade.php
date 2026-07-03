<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Cocina') }} — {{ config('app.name') }}</title>
    @vite(['resources/css/main.css', 'resources/js/main.js'])
</head>
<body class="min-h-screen overflow-x-hidden bg-slate-950 text-slate-100 antialiased" data-page="kitchen">

<header class="sticky top-0 z-40 flex items-center justify-between gap-3 border-b border-slate-800 bg-slate-950/95 px-4 py-3">
    <div class="flex items-center gap-3">
        <x-icon name="chef" class="h-6 w-6 text-brand" />
        <h1 class="text-xl font-bold">{{ __('Cocina') }}</h1>
        <span id="kds-clock" class="font-mono text-lg tabular-nums text-slate-400"></span>
    </div>
    <a href="{{ route('admin') }}" class="rounded-lg border border-slate-700 px-3 py-1.5 text-sm text-slate-300 no-underline">{{ __('Salir') }}</a>
</header>

<main class="grid gap-3 p-3 md:grid-cols-3">
    {{-- TODO --}}
    <section class="flex flex-col rounded-xl bg-slate-900/40 p-2" aria-label="{{ __('Por hacer') }}">
        <h2 class="mb-2 flex items-center justify-between px-1 text-sm font-bold uppercase tracking-widest text-slate-400">
            <span class="inline-flex items-center gap-2"><x-icon name="list" class="h-4 w-4" /> {{ __('Por hacer') }}</span>
            <span id="count-todo" class="rounded-full bg-slate-800 px-2 py-0.5 text-xs tabular-nums">0</span>
        </h2>
        <div id="col-todo" class="flex flex-1 flex-col gap-2"></div>
    </section>

    {{-- DOING --}}
    <section class="flex flex-col rounded-xl bg-amber-950/20 p-2" aria-label="{{ __('En marcha') }}">
        <h2 class="mb-2 flex items-center justify-between px-1 text-sm font-bold uppercase tracking-widest text-brand">
            <span class="inline-flex items-center gap-2"><x-icon name="fire" class="h-4 w-4" /> {{ __('En marcha') }}</span>
            <span id="count-doing" class="rounded-full bg-amber-900/40 px-2 py-0.5 text-xs tabular-nums">0</span>
        </h2>
        <div id="col-doing" class="flex flex-1 flex-col gap-2"></div>
    </section>

    {{-- DONE --}}
    <section class="flex flex-col rounded-xl bg-emerald-950/20 p-2" aria-label="{{ __('Listo para servir') }}">
        <h2 class="mb-2 flex items-center justify-between px-1 text-sm font-bold uppercase tracking-widest text-emerald-400">
            <span class="inline-flex items-center gap-2"><x-icon name="bell" class="h-4 w-4" /> {{ __('Listo — llevar a') }}</span>
            <span id="count-done" class="rounded-full bg-emerald-900/40 px-2 py-0.5 text-xs tabular-nums">0</span>
        </h2>
        <div id="col-done" class="flex flex-1 flex-col gap-2"></div>
    </section>
</main>

</body>
</html>

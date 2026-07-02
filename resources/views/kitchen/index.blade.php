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

<header class="sticky top-0 z-40 flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 bg-slate-950/95 px-4 py-3">
    <div class="flex items-center gap-3">
        <h1 class="text-xl font-bold">👨‍🍳 {{ __('Cocina') }}</h1>
        <span id="kds-clock" class="font-mono text-lg tabular-nums text-slate-400"></span>
    </div>
    <div id="kds-stations" class="flex flex-wrap gap-2">
        {{-- station filter chips rendered by kitchen.js --}}
    </div>
    <a href="{{ route('admin') }}" class="rounded-lg border border-slate-700 px-3 py-1.5 text-sm text-slate-300">{{ __('Salir') }}</a>
</header>

<main class="grid gap-4 p-4 lg:grid-cols-[2fr,1fr]">
    <section aria-label="{{ __('En cocina') }}">
        <h2 class="mb-3 text-sm font-semibold uppercase tracking-widest text-amber-400">🔥 {{ __('En cocina — por orden de llegada') }}</h2>
        <div id="kds-pending" class="grid gap-3 sm:grid-cols-2"></div>
        <p id="kds-pending-empty" class="hidden rounded-xl border border-dashed border-slate-800 py-14 text-center text-slate-500">
            {{ __('Sin comandas pendientes 🎉') }}
        </p>
    </section>

    <section aria-label="{{ __('Listo para servir') }}">
        <h2 class="mb-3 text-sm font-semibold uppercase tracking-widest text-emerald-400">🛎️ {{ __('Listo — servir a…') }}</h2>
        <div id="kds-ready" class="grid gap-3"></div>
        <p id="kds-ready-empty" class="hidden rounded-xl border border-dashed border-slate-800 py-14 text-center text-slate-500">
            {{ __('Nada esperando camarero') }}
        </p>
    </section>
</main>

</body>
</html>

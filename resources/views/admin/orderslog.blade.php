@extends('layouts.admin')

@section('title', __('Todos los pedidos') . ' — ' . config('app.name'))

@section('page', 'admin-orderslog')

@php
    $printerCount = (int) config('app.nr-of-printers', 3);
    $printerCount = max(1, min($printerCount, 6));
@endphp

@section('page_header')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-2xl font-bold text-slate-900">{{ __('Todos los pedidos') }}</h1>
        <nav class="flex flex-wrap gap-2" aria-label="{{ __('Estación') }}" data-orderslog-filters>
            <button type="button" class="chip chip-active" data-printer="all">{{ __('Todas') }}</button>
            @for ($i = 1; $i <= $printerCount; $i++)
                <button type="button" class="chip" data-printer="{{ $i }}">{{ __('Impresora') }} {{ $i }}</button>
            @endfor
        </nav>
    </div>
@endsection

@section('content')
    <div id="orderslog-list" class="space-y-2"></div>

    <div id="orderslog-empty" class="hidden py-16 text-center text-slate-500">
        {{ __('No hay pedidos para esta estación.') }}
    </div>

    <div id="orderslog-loading" class="flex items-center justify-center py-8 text-sm text-slate-400">
        <svg class="mr-2 h-5 w-5 animate-spin text-brand-dark" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
        </svg>
        {{ __('Cargando…') }}
    </div>

    <div id="orderslog-end" class="hidden py-8 text-center text-xs uppercase tracking-widest text-slate-400">
        {{ __('· Fin ·') }}
    </div>

    {{-- Sentinel watched by the infinite-scroll observer --}}
    <div id="orderslog-sentinel" class="h-1"></div>
@endsection

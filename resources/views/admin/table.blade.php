@extends('layouts.admin')

@section('title', __('Mesas') . ' — ' . config('app.name'))

@section('page', 'admin-tables')

@section('page_header')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-2xl font-bold text-slate-900">{{ __('Mesas') }}</h1>
        <div class="relative sm:w-64">
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
            </svg>
            <input
                id="table-filter"
                type="search"
                autocomplete="off"
                placeholder="{{ __('Buscar mesa…') }}"
                aria-label="{{ __('Buscar mesa') }}"
                class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm focus:border-brand focus:outline-none"
            />
        </div>
    </div>
@endsection

@section('content')
    <div class="mb-4 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-600">
        <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded-full border border-slate-300 bg-slate-200"></span>{{ __('Libre') }}</span>
        <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded-full border border-green-600 bg-green-500"></span>{{ __('Ocupada') }}</span>
        <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded-full border border-amber-500 bg-amber-400"></span>{{ __('Pedido sin enviar') }}</span>
    </div>

    <div id="tables-grid" class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
        @foreach ($places as $place)
            @php
                $sum = $openTicketSum[$loop->iteration - 1] ?? 0;
                $hasOpen = in_array($place->id, $openTicket) && $sum > 0;
                $unord = $ticketWithUnorderdItems[$loop->iteration - 1] ?? false;
            @endphp
            <a href="/order/table/{{ $place->id }}"
               data-table-name="{{ Str::lower($place->name . ' ' . $place->id) }}"
               class="table-tile flex min-h-[5.5rem] flex-col items-center justify-center gap-1 rounded-xl border-2 px-3 py-3 text-center no-underline transition hover:opacity-90
                @if ($hasOpen && $unord) border-amber-500 bg-amber-400 text-slate-900
                @elseif($hasOpen) border-green-600 bg-green-500 text-white
                @else border-slate-300 bg-slate-200 text-slate-700
                @endif">
                <span class="text-lg font-bold leading-tight">{{ $place->name }}</span>
                @if ($hasOpen)
                    <span class="text-sm font-semibold tabular-nums opacity-90">@money($sum * 1.1)</span>
                @endif
                @if ($hasOpen && $unord)
                    <span class="rounded-full bg-slate-900/80 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-300">{{ __('Sin enviar') }}</span>
                @endif
            </a>
        @endforeach
    </div>

    <p id="tables-no-match" class="hidden py-8 text-center text-slate-500">{{ __('Ninguna mesa coincide.') }}</p>
@endsection

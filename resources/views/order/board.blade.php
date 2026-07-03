@extends('layouts.shop')

@section('title', __('Carta') . ' — ' . config('app.name'))

@section('page', 'order-board')

@php
    $skins = [
        ['bg' => 'linear-gradient(150deg,#EC8A5F,#C7502F)', 'text' => '#ffffff', 'go' => 'rgba(255,255,255,.22)'],
        ['bg' => 'linear-gradient(150deg,#2E8C86,#16404D)', 'text' => '#ffffff', 'go' => 'rgba(255,255,255,.22)'],
        ['bg' => 'linear-gradient(155deg,#B14FC2,#6A1B7A)', 'text' => '#ffffff', 'go' => 'rgba(255,255,255,.22)'],
        ['bg' => 'linear-gradient(150deg,#5AA9A0,#2A7D74)', 'text' => '#ffffff', 'go' => 'rgba(255,255,255,.22)'],
        ['bg' => 'linear-gradient(150deg,#E9A94B,#D07B2E)', 'text' => '#4A2417', 'go' => 'rgba(74,36,23,.18)'],
    ];
@endphp

@push('head')
<style>
    .board-tile{position:relative;overflow:hidden;border-radius:22px;min-height:148px;padding:16px;
        display:flex;flex-direction:column;justify-content:space-between;text-decoration:none;
        box-shadow:0 14px 26px -16px rgba(22,64,77,.55);transition:transform .16s ease}
    .board-tile:active{transform:scale(.975)}
    .board-tile .sun{position:absolute;width:150px;height:150px;border-radius:50%;top:-60px;right:-40px;
        background:radial-gradient(circle,rgba(255,255,255,.32),rgba(255,255,255,0) 70%)}
    .board-tile .go{position:absolute;top:14px;right:14px;width:28px;height:28px;border-radius:999px;display:grid;place-items:center;font-weight:700}
    .board-glyph{font-size:36px;line-height:1;filter:drop-shadow(0 4px 8px rgba(0,0,0,.18))}
    .board-shimmer::after{content:"";position:absolute;inset:0;background:linear-gradient(115deg,transparent 30%,rgba(255,255,255,.35) 48%,transparent 66%);
        transform:translateX(-120%);animation:board-sweep 3.6s ease-in-out infinite}
    .board-live .pulse{width:7px;height:7px;border-radius:50%;background:#4A2417;animation:board-pulse 1.8s infinite}
    @keyframes board-sweep{0%{transform:translateX(-120%)}55%,100%{transform:translateX(120%)}}
    @keyframes board-pulse{0%{box-shadow:0 0 0 0 rgba(74,36,23,.45)}70%{box-shadow:0 0 0 8px rgba(74,36,23,0)}100%{box-shadow:0 0 0 0 rgba(74,36,23,0)}}
    @media (prefers-reduced-motion:reduce){.board-shimmer::after,.board-live .pulse{animation:none}}
</style>
@endpush

@section('content')
    @include('partials.shop.tutorial')

    <div class="mx-auto max-w-3xl space-y-5">
        {{-- Smart search --}}
        <div class="relative">
            <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-brand-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
            </svg>
            <input id="product-search" type="search" inputmode="search" autocomplete="off"
                placeholder="{{ __('Buscar… mojito, boquerones…') }}"
                aria-label="{{ __('Buscar productos') }}"
                class="w-full rounded-2xl border border-amber-200/70 bg-white py-3.5 pl-10 pr-10 text-base shadow-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/30">
            <button type="button" id="product-search-clear"
                class="absolute right-2 top-1/2 hidden -translate-y-1/2 rounded-full p-1.5 text-slate-400 hover:bg-slate-100"
                aria-label="{{ __('Borrar búsqueda') }}">✕</button>
        </div>

        {{-- Search results --}}
        <div id="search-results" class="hidden">
            <p id="search-results-count" class="px-1 pb-2 text-sm text-slate-500" role="status"></p>
            <div class="card-tw"><div class="card-tw-body">
                <ul id="search-results-grid" role="list" class="grid grid-cols-2 gap-2 sm:gap-3 lg:grid-cols-3"></ul>
                <div id="search-no-results" class="hidden py-10 text-center text-slate-500">
                    {{ __('No hemos encontrado nada… prueba con otra palabra.') }}
                </div>
            </div></div>
        </div>

        <div id="category-browse" class="space-y-6">
            {{-- The board --}}
            <section>
                <div class="mb-3 flex items-baseline justify-between px-1">
                    <span class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500">{{ __('La carta') }}</span>
                    <span class="text-xs text-slate-400">{{ __('toca para explorar') }}</span>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    {{-- Ofertas tile --}}
                    <a href="{{ route('order.offers') }}" class="board-tile board-shimmer" style="background:linear-gradient(150deg,#F2B54B,#E76F51);color:#4A2417">
                        <span class="sun"></span>
                        <span class="go" style="background:rgba(74,36,23,.16)"><x-icon name="arrow-right" class="h-4 w-4"/></span>
                        @if ($liveOffers > 0)
                            <span class="board-live inline-flex w-fit items-center gap-1.5 rounded-full bg-[rgba(74,36,23,.16)] px-2.5 py-1 text-[11px] font-bold">
                                <span class="pulse"></span>{{ $liveOffers }} {{ __('activas') }}
                            </span>
                        @else
                            <span class="inline-flex w-fit items-center gap-1.5 rounded-full bg-[rgba(74,36,23,.16)] px-2.5 py-1 text-[11px] font-bold">{{ __('Flash') }}</span>
                        @endif
                        <div>
                            <x-icon name="bolt" class="mb-1 h-8 w-8" style="stroke-width:1.4" />
                            <div class="text-xl font-bold leading-tight">{{ __('Ofertas') }}</div>
                            <div class="text-xs font-semibold opacity-90">{{ __('Hoy en la playa') }}</div>
                        </div>
                    </a>

                    @foreach ($board as $i => $cat)
                        @php $skin = $skins[$i % count($skins)]; @endphp
                        <a href="/order/category/{{ $cat->id }}" class="board-tile"
                           style="background:{{ $skin['bg'] }};color:{{ $skin['text'] }}">
                            <span class="sun"></span>
                            <span class="go" style="background:{{ $skin['go'] }}"><x-icon name="arrow-right" class="h-4 w-4"/></span>
                            <div class="mt-auto">
                                <div class="text-2xl font-bold leading-none tracking-tight">{{ __($cat->name) }}</div>
                                <div class="mt-1.5 text-xs font-semibold uppercase tracking-wider opacity-80">{{ $cat->count }} {{ __('platos') }}</div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>

            {{-- Populares --}}
            @if ($populares->isNotEmpty())
                <section>
                    <div class="mb-3 flex items-baseline justify-between px-1">
                        <span class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500">{{ __('Lo más pedido') }}</span>
                        <span class="text-xs text-slate-400">{{ __('en la playa') }}</span>
                    </div>
                    <ul role="list" class="-mx-3 flex gap-3 overflow-x-auto px-3 pb-2" style="scrollbar-width:none">
                        @foreach ($populares as $product)
                            <li class="w-40 shrink-0">
                                <div class="card-tw card-tw-product product-card flex h-full flex-col" data-product-id="{{ $product->id }}">
                                    <img src="/dbimage/{{ $product->id }}.png" alt="{{ $product->name }}"
                                         class="img-drag h-24 w-full cursor-pointer object-cover"
                                         data-product-image loading="lazy"
                                         onclick="addProduct('{{ $product->id }}');"
                                         onerror="this.style.visibility='hidden'">
                                    <div class="flex flex-1 flex-col p-2.5">
                                        <p class="text-sm font-semibold leading-tight text-slate-900">{{ $product->name }}</p>
                                        <p class="mt-1 text-base font-bold text-slate-900">@money($product->pricesell * 1.1)</p>
                                        <button type="button" class="btn-primary add-to-cart mt-2 w-full text-sm"
                                                onclick="addProduct('{{ $product->id }}');">
                                            {{ __('Añadir') }}&nbsp;<img src="/img/cart.svg" width="15" alt="" class="inline">
                                        </button>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>
    </div>

    {{-- Add-on modal (for quick-add from Populares) --}}
    <div id="selectAddOnModal" class="fixed inset-0 z-[70] hidden items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="addonModalTitle">
        <div class="absolute inset-0 bg-slate-900/50" data-close-addon-modal></div>
        <div class="relative z-10 flex max-h-[90vh] w-full max-w-md flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl">
            <div class="border-b border-slate-100 px-4 py-3">
                <h2 id="addonModalTitle" class="text-lg font-semibold text-slate-900">{{ __('Con') }}</h2>
            </div>
            <div class="min-h-0 flex-1 overflow-y-auto p-4">
                <div id="addOnProductsList" class="space-y-2" role="listbox" aria-label="{{ __('Extras') }}"></div>
            </div>
            <div class="flex flex-wrap gap-2 border-t border-slate-100 px-4 py-3">
                <button type="button" class="btn-secondary" data-close-addon-modal>{{ __('Nada') }}</button>
            </div>
        </div>
    </div>
@endsection

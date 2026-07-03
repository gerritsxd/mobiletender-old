@extends('layouts.shop')

@section('title', __('Pedido') . ' — ' . config('app.name'))

@section('page', 'order-category')

@php
    $categoryChips = $categories->filter(function ($c) {
        return $c->parentid === null;
    });
    $activeCategoryId = $currentCategoryId ?? ($categoryChips->first()->id ?? null);
    $activeRootId = $rootCategoryId ?? $activeCategoryId;
    $subChips = ($subCategories ?? collect())->filter(fn ($c) => (int) ($c->catshowname ?? 1) === 1);
    $productRows = $products->filter(fn ($p) => (bool) $p->product_cat);
    // Real products as search examples, so we never suggest things we don't sell.
    $searchExamples = $productRows->pluck('name')
        ->filter(fn ($n) => mb_strlen($n) <= 18)
        ->take(2)
        ->implode(', ');
@endphp

@section('content')
    @include('partials.shop.tutorial')

    <div class="mx-auto max-w-5xl space-y-3">
        <a href="{{ route('order') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-slate-600 no-underline hover:text-slate-900">
            <span class="text-lg leading-none">‹</span> {{ __('La carta') }}
        </a>
        {{-- Smart search --}}
        <div class="relative">
            <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
            </svg>
            <input
                id="product-search"
                type="search"
                inputmode="search"
                autocomplete="off"
                placeholder="{{ $searchExamples ? __('Buscar…') . ' ' . $searchExamples . '…' : __('Buscar productos…') }}"
                aria-label="{{ __('Buscar productos') }}"
                class="w-full rounded-xl border border-slate-200 bg-white py-3 pl-10 pr-10 text-base shadow-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200"
            />
            <button
                type="button"
                id="product-search-clear"
                class="absolute right-2 top-1/2 hidden -translate-y-1/2 rounded-full p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                aria-label="{{ __('Borrar búsqueda') }}"
            >✕</button>
        </div>

        {{-- Search results (filled by product-search.js) --}}
        <div id="search-results" class="hidden">
            <p id="search-results-count" class="px-1 pb-2 text-sm text-slate-500" role="status"></p>
            <div class="card-tw">
                <div class="card-tw-body">
                    <ul id="search-results-grid" role="list" class="grid grid-cols-2 gap-2 sm:grid-cols-2 sm:gap-3 lg:grid-cols-3"></ul>
                    <div id="search-no-results" class="hidden py-10 text-center text-slate-500">
                        {{ __('No hemos encontrado nada… prueba con otra palabra.') }}
                    </div>
                </div>
            </div>
        </div>

        <div id="category-browse" class="space-y-3">
        <nav
            class="-mx-3 overflow-x-auto border-y border-slate-100 bg-white px-3 py-3 shadow-sm sm:mx-0 sm:rounded-xl sm:border"
            aria-label="{{ __('Categorías') }}"
            style="scrollbar-width: none;"
        >
            <div class="flex min-w-max gap-2">
                <a
                    href="{{ route('order.offers') }}"
                    class="inline-flex shrink-0 items-center rounded-full border border-amber-300 bg-amber-400 px-4 py-2 text-sm font-semibold text-amber-950 shadow-sm no-underline hover:bg-amber-300"
                >
                    {{ __('Ofertas') }}
                </a>
                @foreach ($categoryChips as $cat)
                    <a
                        href="/order/category/{{ $cat->id }}"
                        @class([
                            'inline-flex shrink-0 items-center rounded-full border px-4 py-2 text-sm font-medium no-underline shadow-sm',
                            'border-amber-400 bg-amber-100 text-amber-950' => (string) $cat->id === (string) $activeRootId,
                            'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' => (string) $cat->id !== (string) $activeRootId,
                        ])
                        @if ((string) $cat->id === (string) $activeRootId) aria-current="page" @endif
                    >{{ __($cat->name) }}</a>
                @endforeach
            </div>
        </nav>

        {{-- Sub-category chips: shown only when the active family has children --}}
        @if ($subChips->isNotEmpty())
            <nav
                class="-mx-3 overflow-x-auto px-3 sm:mx-0"
                aria-label="{{ __('Subcategorías') }}"
                style="scrollbar-width: none;"
            >
                <div class="flex min-w-max gap-2">
                    <a
                        href="/order/category/{{ $activeRootId }}"
                        @class([
                            'inline-flex shrink-0 items-center rounded-full border px-3 py-1.5 text-xs font-medium no-underline',
                            'border-slate-700 bg-slate-800 text-white' => (string) $activeCategoryId === (string) $activeRootId,
                            'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' => (string) $activeCategoryId !== (string) $activeRootId,
                        ])
                    >{{ __('Todo') }}</a>
                    @foreach ($subChips as $sub)
                        <a
                            href="/order/category/{{ $sub->id }}"
                            @class([
                                'inline-flex shrink-0 items-center rounded-full border px-3 py-1.5 text-xs font-medium no-underline',
                                'border-slate-700 bg-slate-800 text-white' => (string) $sub->id === (string) $activeCategoryId,
                                'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' => (string) $sub->id !== (string) $activeCategoryId,
                            ])
                            @if ((string) $sub->id === (string) $activeCategoryId) aria-current="page" @endif
                        >{{ __($sub->name) }}</a>
                    @endforeach
                </div>
            </nav>
        @endif

        <div class="card-tw">
            <div class="card-tw-body">
            @if ($productRows->isEmpty())
                <div class="py-12 text-center text-slate-500" role="status">
                    {{ __('No hay productos en esta categoría.') }}
                </div>
            @else
                <ul
                    id="products-grid"
                    role="list"
                    class="grid grid-cols-2 gap-2 sm:grid-cols-2 sm:gap-3 lg:grid-cols-3"
                >
                    @foreach ($productRows as $product)
                        <li class="card-tw product-card card-tw-product flex flex-col gap-2 p-2 sm:flex-row sm:gap-3 sm:p-3" data-product-id="{{ $product->id }}">
                            <img
                                src="/dbimage/{{ $product->id }}.png"
                                class="img-drag h-24 w-full shrink-0 cursor-pointer rounded-lg object-cover sm:h-20 sm:w-20"
                                data-product-image
                                onclick="addProduct('{{ $product->id }}');"
                                alt="{{ $product->name }}"
                            />
                            <div class="flex min-w-0 flex-1 flex-col">
                                <h3 class="text-sm font-semibold leading-tight text-slate-900">{{ $product->name }}</h3>
                                <p class="mt-1 text-base font-bold text-slate-900">@money($product->pricesell * 1.1)</p>
                                <div class="mt-auto flex flex-wrap gap-1.5 pt-2 sm:gap-2">
                                    <button
                                        type="button"
                                        class="btn-primary add-to-cart flex-1 text-sm sm:flex-none"
                                        onclick="addProduct('{{ $product->id }}');"
                                    >{{ __('Añadir') }}&nbsp;<img src="/img/cart.svg" width="16" alt="" class="inline" /></button>
                                    @if ($product->product_detail)
                                        <button
                                            type="button"
                                            class="btn-secondary text-sm"
                                            onclick="document.getElementById('product-info-modal-{{ $product->id }}').showModal();"
                                        >{{ __('+info') }}</button>
                                    @endif
                                    @if (Auth::user() && Auth::user()->isAdmin())
                                        <a href="{{ route('products.edit', $product->id) }}" class="btn-secondary text-sm">{{ __('Editar') }}</a>
                                    @endif
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="mt-6 flex justify-center">
                {{ $products->links() }}
            </div>
            </div>
        </div>
        </div>{{-- /#category-browse --}}
    </div>

    <div
        id="selectAddOnModal"
        class="fixed inset-0 z-[70] hidden items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="addonModalTitle"
    >
        <div
            class="absolute inset-0 bg-slate-900/50"
            data-close-addon-modal
        ></div>
        <div class="relative z-10 flex max-h-[90vh] w-full max-w-md flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl">
            <div class="border-b border-slate-100 px-4 py-3">
                <h2 id="addonModalTitle" class="text-lg font-semibold text-slate-900">{{ __('Con') }}</h2>
            </div>
            <div class="min-h-0 flex-1 overflow-y-auto p-4">
                <div id="addOnProductsList" class="space-y-2" role="listbox" aria-label="{{ __('Extras') }}"></div>
            </div>
            <div class="flex flex-wrap gap-2 border-t border-slate-100 px-4 py-3">
                <button type="button" class="btn-secondary" data-close-addon-modal>{{ __('Nada') }}</button>
                <button type="button" class="btn-primary" id="addAdonProductButton" hidden disabled aria-hidden="true">
                    {{ __('Añadir') }}
                </button>
            </div>
        </div>
    </div>

    @foreach ($productRows as $product)
        @if ($product->product_detail)
            @include('partials.shop.product-info-modal', ['product' => $product])
        @endif
    @endforeach
@endsection

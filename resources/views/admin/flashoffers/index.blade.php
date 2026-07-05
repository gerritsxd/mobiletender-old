@extends('layouts.admin')

@section('title', __('Ofertas Flash') . ' — ' . config('app.name'))

@section('page', 'admin-flashoffers-index')

@section('page_header')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-2xl font-bold text-slate-900">{{ __('Ofertas Flash') }}</h1>
    </div>
@endsection

@section('content')
    @if ($message = Session::get('success'))
        <div class="alert-success">{{ $message }}</div>
    @endif
    @if ($message = Session::get('error'))
        <div class="alert-error">{{ $message }}</div>
    @endif

    {{-- Quick create --}}
    <div class="card-tw mb-6">
        <div class="card-tw-body">
            <h2 class="mb-4 text-lg font-semibold text-slate-900">{{ __('Lanzar nueva oferta flash') }}</h2>
            <form action="{{ route('flashoffers.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Título') }} *</label>
                    <input type="text" name="title" required maxlength="120"
                           placeholder="{{ __('p.ej. ¡Mojitos a 5€ durante 1 hora!') }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:outline-none"
                           value="{{ old('title') }}">
                    @error('title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Mensaje (opcional)') }}</label>
                    <input type="text" name="message" maxlength="200"
                           placeholder="{{ __('p.ej. Solo en la barra de la playa') }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:outline-none"
                           value="{{ old('message') }}">
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Producto (opcional)') }}</label>
                        @php
                            $productsForJs = $products->map(fn ($p) => [
                                'id' => (string) $p->id,
                                'name' => $p->name,
                                'price' => round((float) $p->pricesell * 1.1, 2),
                            ])->values();
                        @endphp
                        <div
                            x-data="{
                                open: false,
                                query: '',
                                selectedId: {{ \Illuminate\Support\Js::from(old('product_id', '')) }},
                                products: {{ \Illuminate\Support\Js::from($productsForJs) }},
                                norm(s) { return (s || '').toString().toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, ''); },
                                get filtered() {
                                    if (!this.query) return this.products.slice(0, 60);
                                    const q = this.norm(this.query);
                                    return this.products.filter(p => this.norm(p.name).includes(q)).slice(0, 60);
                                },
                                get selectedLabel() {
                                    const p = this.products.find(x => x.id === this.selectedId);
                                    return p ? p.name + ' (' + p.price.toFixed(2) + '€)' : '';
                                },
                                choose(p) { this.selectedId = p.id; this.query = ''; this.open = false; },
                                clear() { this.selectedId = ''; this.query = ''; },
                            }"
                            class="relative"
                            x-cloak
                        >
                            <input type="hidden" name="product_id" :value="selectedId">

                            {{-- Selected product chip --}}
                            <div x-show="selectedId" class="flex items-center justify-between gap-2 rounded-lg border border-brand bg-brand-light px-3 py-2">
                                <span class="text-sm font-medium text-sea" x-text="selectedLabel"></span>
                                <button type="button" @click="clear()" class="shrink-0 rounded-full p-1 text-sea-soft hover:bg-brand-dark/20" aria-label="{{ __('Quitar producto') }}">✕</button>
                            </div>

                            {{-- Search box + results --}}
                            <div x-show="!selectedId">
                                <div class="relative">
                                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                                    </svg>
                                    <input type="text" x-model="query" @focus="open = true"
                                           placeholder="{{ __('Buscar producto…') }}"
                                           class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 focus:border-brand focus:outline-none"
                                           autocomplete="off">
                                </div>
                                <ul x-show="open" @click.away="open = false"
                                    class="absolute z-30 mt-1 max-h-64 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white py-1 shadow-lg">
                                    <template x-if="filtered.length === 0">
                                        <li class="px-3 py-2 text-sm text-slate-400">{{ __('Sin resultados') }}</li>
                                    </template>
                                    <template x-for="p in filtered" :key="p.id">
                                        <li>
                                            <button type="button" @click="choose(p)"
                                                    class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm hover:bg-slate-50">
                                                <span class="text-slate-800" x-text="p.name"></span>
                                                <span class="shrink-0 tabular-nums text-slate-500" x-text="p.price.toFixed(2) + '€'"></span>
                                            </button>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">{{ __('Con producto, el cliente puede añadirlo al pedido con un toque.') }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Precio flash € (IVA incl., opcional)') }}</label>
                        <input type="text" name="flash_price" inputmode="decimal" placeholder="{{ __('p.ej. 5,00 — vacío = precio normal') }}"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:outline-none"
                               value="{{ old('flash_price') }}">
                    </div>
                </div>
                <div x-data="{ duration: 60 }">
                    <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Duración') }}</label>
                    <input type="hidden" name="duration_minutes" :value="duration">
                    <div class="flex flex-wrap gap-2">
                        @foreach ([15, 30, 60, 120, 240] as $minutes)
                            <button type="button" @click="duration = {{ $minutes }}"
                                    :class="duration === {{ $minutes }} ? 'btn-primary' : 'btn-secondary'">
                                {{ $minutes >= 60 ? ($minutes / 60) . ' h' : $minutes . ' min' }}
                            </button>
                        @endforeach
                    </div>
                    @error('duration_minutes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="btn-primary w-full py-3 text-base sm:w-auto sm:px-8">
{{ __('Lanzar oferta flash') }}
                </button>
            </form>
        </div>
    </div>

    {{-- Live offers --}}
    <h2 class="mb-3 text-lg font-semibold text-slate-900">{{ __('Activas ahora') }}</h2>
    @if ($liveOffers->isEmpty())
        <div class="card-tw mb-6">
            <div class="card-tw-body text-center text-slate-500">{{ __('No hay ofertas flash activas.') }}</div>
        </div>
    @else
        <ul role="list" class="mb-6 space-y-3">
            @foreach ($liveOffers as $offer)
                <li class="card-tw border-l-4 border-l-amber-400">
                    <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <p class="text-base font-semibold text-slate-900">{{ $offer->title }}</p>
                            @if ($offer->message)
                                <p class="text-sm text-slate-600">{{ $offer->message }}</p>
                            @endif
                            <p class="mt-1 text-sm text-slate-500">
                                @if ($offer->product)
                                    {{ $offer->product->name }}
                                    @if ($offer->flash_price !== null)
                                        — <span class="font-semibold text-emerald-700">@money($offer->flash_price)</span>
                                        <span class="line-through">@money($offer->product->pricesell * 1.1)</span>
                                    @endif
                                @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="badge-success whitespace-nowrap font-mono" data-countdown="{{ $offer->secondsLeft() }}">
                                {{ gmdate('H:i:s', $offer->secondsLeft()) }}
                            </span>
                            <form action="{{ route('flashoffers.extend', $offer->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn-secondary whitespace-nowrap text-sm">+15 min</button>
                            </form>
                            <form action="{{ route('flashoffers.stop', $offer->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn-danger text-sm">{{ __('Parar') }}</button>
                            </form>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif

    {{-- Past offers --}}
    <h2 class="mb-3 text-lg font-semibold text-slate-900">{{ __('Historial') }}</h2>
    @if ($pastOffers->isEmpty())
        <div class="card-tw">
            <div class="card-tw-body text-center text-slate-500">{{ __('Sin ofertas anteriores.') }}</div>
        </div>
    @else
        <ul role="list" class="space-y-2">
            @foreach ($pastOffers as $offer)
                <li class="card-tw">
                    <div class="flex items-center justify-between gap-3 p-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-slate-800">{{ $offer->title }}</p>
                            <p class="text-xs text-slate-500">
                                {{ $offer->starts_at->format('d/m/Y H:i') }} → {{ $offer->ends_at->format('H:i') }}
                                @if ($offer->created_by) · {{ $offer->created_by }} @endif
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <form action="{{ route('flashoffers.extend', $offer->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn-secondary text-xs" title="{{ __('Relanzar 15 min') }}">{{ __('Relanzar') }}</button>
                            </form>
                            <form action="{{ route('flashoffers.destroy', $offer->id) }}" method="POST" onsubmit="return confirm('{{ __('¿Borrar la oferta flash?') }}');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-danger text-xs">{{ __('Borrar') }}</button>
                            </form>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
@endsection

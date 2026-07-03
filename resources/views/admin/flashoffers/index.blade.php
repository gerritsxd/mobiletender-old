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
                        <select name="product_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand focus:outline-none">
                            <option value="">{{ __('— Solo anuncio, sin producto —') }}</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}" @selected(old('product_id') === $product->id)>
                                    {{ $product->name }} (@money($product->pricesell * 1.1))
                                </option>
                            @endforeach
                        </select>
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

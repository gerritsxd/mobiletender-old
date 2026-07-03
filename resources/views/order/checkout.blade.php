@extends('layouts.shop')

@section('title', __('Pagar') . ' — ' . config('app.name'))

@section('page', 'checkout')

@php
    $checkoutPageConfig = [
        'ticketId' => Session::get('ticketID'),
        'newLinesTotal' => round((float) $newLinesPrice * 1.1, 2),
        'paypalPrepay' => (bool) (config('customoptions.eatin_prepay') || config('customoptions.takeaway_prepay') || config('customoptions.delivery_prepay')),
        'paypalClientId' => config('paypal.client_id'),
        'hasTableNumber' => (bool) Session::get('tableNumber'),
        'live' => config('paypal.settings.mode') === 'live',
    ];
    $hasTable = (bool) Session::get('tableNumber');
    $hasNewLines = $newLinesPrice > 0;
@endphp

@section('content')
    <div class="mx-auto max-w-2xl space-y-4">
        <h1 class="text-xl font-semibold text-slate-900">{{ __('Pagar') }}</h1>

        <section class="card-tw" aria-label="{{ __('Resumen') }}">
            <div class="card-tw-header text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('Resumen') }}</div>
            <dl class="divide-y divide-slate-100 text-sm">
                <div class="grid grid-cols-3 gap-2 px-4 py-3">
                    <dt class="text-slate-500">{{ __('Base') }}</dt>
                    <dd class="text-right tabular-nums">@money($totalBasketPrice)</dd>
                    <dd class="text-right tabular-nums text-slate-500">{{ __('IVA') }} @money($totalBasketPrice * 0.1)</dd>
                </div>
                <div class="flex items-baseline justify-between gap-2 bg-slate-50 px-4 py-3">
                    <dt class="text-base font-semibold text-slate-900">{{ __('TOTAL') }}</dt>
                    <dd class="text-2xl font-bold tabular-nums text-slate-900">@money($totalBasketPrice * 1.1)</dd>
                </div>
                @if ($hasNewLines)
                    <div class="flex items-baseline justify-between gap-2 px-4 py-3">
                        <dt class="text-sm font-medium text-slate-700">{{ __('A Pedir') }}</dt>
                        <dd class="text-base font-semibold tabular-nums text-slate-900">@money($newLinesPrice * 1.1)</dd>
                    </div>
                @endif
            </dl>
        </section>

        <section class="card-tw" aria-label="{{ __('Forma de pago') }}">
            <div class="card-tw-header text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('Forma de pago') }}</div>
            <div class="space-y-3 p-4">
                @if ($hasTable)
                    @if (config('customoptions.clean_table_after_order'))
                        {{-- Online payment first and highlighted: no waiting for staff --}}
                        <button type="button" class="relative inline-flex w-full items-center justify-center gap-2 rounded-xl bg-sea py-3.5 text-base font-semibold text-white active:scale-[0.98]" id="pagarOnline">
                            <x-icon name="card" class="h-5 w-5" /> {{ __('Pagar online') }}
                            <span class="absolute -top-2 right-3 rounded-full bg-brand px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-sea">{{ __('Sin esperas') }}</span>
                        </button>
                        <p class="text-center text-xs text-slate-500">{{ __('Tarjeta, Apple Pay o Google Pay — pagas y listo.') }}</p>
                        <div class="flex gap-2 pt-1">
                            <button type="button" class="btn-secondary flex-1" id="pagarEfectivo">{{ __('Efectivo') }}</button>
                            <button type="button" class="btn-secondary flex-1" id="pagarTarjeta">{{ __('Tarjeta al camarero') }}</button>
                        </div>
                    @else
                        <button type="button" class="btn-primary w-full" id="apuntarEnLaMesa">{{ __('Pedir') }}</button>
                    @endif
                @else
                    <button type="button" class="btn-mobilepos w-full" id="eatin">{{ __('Para tomarlo aqui') }}</button>
                    @if (config('customoptions.takeaway'))
                        <button type="button" class="btn-mobilepos w-full" id="takeaway">{{ __('Para recoger') }}</button>
                    @endif
                    @if (config('customoptions.delivery'))
                        <a href="" class="btn-mobilepos block w-full text-center no-underline">{{ __('Para entregar') }}</a>
                    @endif
                @endif

                <div id="applepay-container" class="empty:hidden"></div>
                <div id="googlepay-container" class="empty:hidden"></div>

                <details class="group rounded-lg border border-slate-200 bg-white">
                    <summary class="cursor-pointer select-none px-4 py-3 text-sm font-medium text-slate-700 marker:hidden">
                        {{ __('Otras formas de pagar') }}
                    </summary>
                    <div class="px-4 pb-4 pt-2">
                        <div id="paypal-button-container"></div>
                    </div>
                </details>
            </div>
        </section>

        <div id="scan-qr-instructions" class="card-tw hidden">
            <div class="space-y-3 p-4 text-center">
                <p class="text-sm text-slate-700">{{ __('Para añadir el pedido a su mesa, escanea con la cámara el código QR que tiene en su mesa.') }}</p>
                <img src="/img/qr-example.png" class="mx-auto max-w-[12rem]" alt="">
            </div>
        </div>

        <div id="div-takeaway" class="card-tw hidden">
            <div class="p-4 text-center text-sm text-slate-700">
                <p class="font-medium">{{ __('Horario de recogido') }}</p>
                <p class="tabular-nums">12:00 — 20:00</p>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script type="application/json" id="checkout-page-config">
@json($checkoutPageConfig)
</script>
@endpush

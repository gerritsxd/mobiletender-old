@extends('layouts.shop')

@section('title', __('Pago') . ' — ' . config('app.name'))

@section('page', 'pay')

@section('content')
@php
    $ticketId = Session::get('ticketID');
    $tableNumber = Session::get('tableNumber');
    $isPickup = (bool) Session::get('isPickup');
    $isTableMesa = $tableNumber && (int) $tableNumber < 100;

    $base = (float) $newLinesPrice;
    $iva = $base * 0.1;
    $amountDue = round($base + $iva, 2);
    $hasPreviousLines = $totalBasketPrice > $newLinesPrice;
    $previousBase = (float) $totalBasketPrice - $base;
    $previousIva = $previousBase * 0.1;
    $previousTotal = $previousBase + $previousIva;
    $grandTotal = round((float) $totalBasketPrice * 1.1, 2);

    $onApproveUrl = (config('customoptions.clean_table_after_order') || (is_numeric($ticketId) && (int) $ticketId < 100))
        ? url('/checkout/printOrderOnline/'.$ticketId)
        : url('/checkout/printOrderPagado/'.$ticketId);
    $payPageConfig = [
        'amount' => $amountDue,
        'paypalClientId' => config('paypal.client_id'),
        'onApproveUrl' => $onApproveUrl,
        'live' => config('paypal.settings.mode') === 'live',
    ];

    if ($isPickup) {
        $referenceLabel = __('Pedido para recoger');
        $referenceValue = (string) $tableNumber;
        $referenceIcon = 'pickup';
    } elseif ($isTableMesa) {
        $referenceLabel = __('Mesa');
        $referenceValue = (string) $tableNumber;
        $referenceIcon = 'table';
    } else {
        $referenceLabel = null;
        $referenceValue = null;
        $referenceIcon = null;
    }
@endphp

<div class="mx-auto max-w-md space-y-5 max-sm:pb-28">
    @if ($referenceLabel)
        <section
            class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm"
            aria-label="{{ $referenceLabel }} {{ $referenceValue }}"
        >
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-slate-900 text-white">
                @if ($referenceIcon === 'pickup')
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6" aria-hidden="true">
                        <path fill-rule="evenodd" d="M7.5 6v.75H5.513c-.96 0-1.764.724-1.865 1.679l-1.263 12A1.875 1.875 0 0 0 4.25 22.5h15.5a1.875 1.875 0 0 0 1.865-2.071l-1.263-12a1.875 1.875 0 0 0-1.865-1.679H16.5V6a4.5 4.5 0 1 0-9 0ZM12 3a3 3 0 0 0-3 3v.75h6V6a3 3 0 0 0-3-3Zm-3 8.25a3 3 0 1 0 6 0v-.75a.75.75 0 0 1 1.5 0v.75a4.5 4.5 0 0 1-9 0v-.75a.75.75 0 0 1 1.5 0v.75Z" clip-rule="evenodd" />
                    </svg>
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6" aria-hidden="true">
                        <path d="M11.47 1.72a.75.75 0 0 1 1.06 0l3 3a.75.75 0 0 1-1.06 1.06l-1.72-1.72V7.5h-1.5V4.06L9.53 5.78a.75.75 0 0 1-1.06-1.06l3-3ZM11.25 7.5v3.75h-7.5a.75.75 0 0 0 0 1.5h.546l1.39 7.81a1.5 1.5 0 0 0 1.477 1.24h9.674a1.5 1.5 0 0 0 1.477-1.24l1.39-7.81h.546a.75.75 0 0 0 0-1.5h-7.5V7.5h-1.5Z" />
                    </svg>
                @endif
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ $referenceLabel }}</p>
                <p class="truncate text-2xl font-bold leading-tight text-slate-900 tabular-nums">{{ $referenceValue }}</p>
            </div>
        </section>
    @endif

    <header class="text-center">
        <h1 class="text-2xl font-bold text-slate-900">{{ __('Pago') }}</h1>
    </header>

    <section class="card-tw overflow-hidden" aria-labelledby="pay-amount-label">
        <div class="flex flex-col items-center gap-1 px-6 py-7 text-center">
            <span id="pay-amount-label" class="text-xs font-medium uppercase tracking-wide text-slate-500">
                {{ __('A pagar') }}
            </span>
            <span class="text-5xl font-extrabold leading-none tracking-tight tabular-nums text-slate-900" data-pay-amount>
                @money($amountDue)
            </span>
            <dl class="mt-3 flex w-full max-w-xs items-center justify-center gap-x-5 text-xs text-slate-500">
                <div class="flex items-baseline gap-1.5">
                    <dt>{{ __('Base') }}</dt>
                    <dd class="font-medium tabular-nums text-slate-700">@money($base)</dd>
                </div>
                <div class="h-3 w-px bg-slate-200" aria-hidden="true"></div>
                <div class="flex items-baseline gap-1.5">
                    <dt>{{ __('IVA') }} 10%</dt>
                    <dd class="font-medium tabular-nums text-slate-700">@money($iva)</dd>
                </div>
            </dl>
        </div>

        @if ($hasPreviousLines)
            <div class="border-t border-slate-100 bg-slate-50 px-6 py-3 text-xs text-slate-600">
                <div class="flex items-baseline justify-between">
                    <span>{{ __('Ya en su cuenta') }}</span>
                    <span class="tabular-nums">@money($previousTotal)</span>
                </div>
                <div class="mt-1 flex items-baseline justify-between font-semibold text-slate-800">
                    <span>{{ __('Total acumulado') }}</span>
                    <span class="tabular-nums">@money($grandTotal)</span>
                </div>
            </div>
        @endif
    </section>

    <section class="space-y-3" aria-label="{{ __('Métodos de pago') }}">
        <div id="applepay-container" class="empty:hidden"></div>
        <div id="googlepay-container" class="empty:hidden"></div>
        <details class="group rounded-2xl border border-slate-200 bg-white shadow-sm">
            <summary class="flex cursor-pointer select-none items-center justify-between px-4 py-3 text-sm font-medium text-slate-700 marker:hidden">
                <span class="inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5 text-slate-400" aria-hidden="true">
                        <path fill-rule="evenodd" d="M2.25 8.25a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3v.75H2.25v-.75ZM2.25 11.25h19.5v4.5a3 3 0 0 1-3 3H5.25a3 3 0 0 1-3-3v-4.5Zm3.75 4.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 0 1.5h-3a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                    </svg>
                    {{ __('Otras formas de pagar') }}
                </span>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 text-slate-400 transition group-open:rotate-90" aria-hidden="true">
                    <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.168 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd" />
                </svg>
            </summary>
            <div class="border-t border-slate-100 px-4 pb-4 pt-3">
                <div id="paypal-button-container"></div>
            </div>
        </details>
    </section>

    <p class="flex items-center justify-center gap-1.5 text-center text-xs text-slate-400">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" class="h-3.5 w-3.5" aria-hidden="true">
            <path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 0 0-5.25 5.25v3a3 3 0 0 0-3 3v6.75a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3v-6.75a3 3 0 0 0-3-3v-3c0-2.9-2.35-5.25-5.25-5.25Zm3.75 8.25v-3a3.75 3.75 0 1 0-7.5 0v3h7.5Z" clip-rule="evenodd" />
        </svg>
        {{ __('Pago seguro. No se guardan los datos de la tarjeta.') }}
    </p>
</div>
@endsection

@push('scripts')
<script type="application/json" id="pay-page-config">
@json($payPageConfig)
</script>
@endpush

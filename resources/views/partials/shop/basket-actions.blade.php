@php
    $tableNum = Session::get('tableNumber');
    $isTableMesa = $tableNum && (int) $tableNum < 100;
    $takeaway = config('customoptions.takeaway');
    $delivery = config('customoptions.delivery');
@endphp

<div class="mt-0 flex w-full flex-col gap-2">
    @if ($isTableMesa)
        @if (! $unprintedlines)
            <button type="button" class="btn-pay relative" data-basket-action="pagar-online">
                💳 {{ __('Pagar online') }}
                <span class="absolute -top-2 right-3 rounded-full bg-amber-400 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-900">{{ __('Sin esperas') }}</span>
            </button>
            <p class="text-center text-xs text-slate-500">{{ __('Tarjeta, Apple Pay o Google Pay — pagas y listo.') }}</p>
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                <button type="button" class="btn-secondary" data-basket-action="pagar-tarjeta">{{ __('Pagar con tarjeta') }}</button>
                <button type="button" class="btn-secondary" data-basket-action="pagar-efectivo">{{ __('Pagar en efectivo') }}</button>
            </div>
        @else
            <button type="button" class="btn-pay" data-basket-action="apuntar-mesa">{{ __('Pedir') }}</button>
        @endif
    @else
        <button type="button" class="btn-pay" data-basket-action="eatin-toggle">{{ __('Para tomarlo aqui') }}</button>
        @if ($takeaway)
            <button type="button" class="btn-secondary" data-basket-action="takeaway">{{ __('Para recoger') }}</button>
        @endif
        @if ($delivery)
            <a href="" class="btn-secondary inline-flex w-full items-center justify-center no-underline">{{ __('Para entregar') }}</a>
        @endif
    @endif
</div>

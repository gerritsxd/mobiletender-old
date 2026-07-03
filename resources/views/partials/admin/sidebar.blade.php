@php
    $nav = [];
    if (Auth::user()->isEmployee()) {
        $nav[] = ['label' => __('Marcar Entrada o Salida'), 'href' => url('/timereport'), 'section' => __('Operación')];
        $nav[] = ['label' => __('Cocina (iPad)'), 'href' => url('/kitchen'), 'section' => __('Operación')];
    }
    if (Auth::user()->isWaiter()) {
        $nav[] = ['label' => __('Seleccionar mesa'), 'href' => url('/selecttable'), 'section' => __('Operación')];
    }
    if (Auth::user()->isManager()) {
        $nav[] = ['label' => __('Mesas y Pedidos'), 'href' => url('/openorders'), 'section' => __('Catálogo')];
        $nav[] = ['label' => __('Products'), 'href' => url('/products'), 'section' => __('Catálogo')];
        $nav[] = ['label' => __('Stock'), 'href' => url('/stockindex'), 'section' => __('Catálogo')];
        $nav[] = ['label' => __('Ofertas'), 'href' => url('/offers'), 'section' => __('Catálogo')];
        $nav[] = ['label' => __('Ofertas Flash'), 'href' => url('/flashoffers'), 'section' => __('Catálogo')];
    }
    if (Auth::user()->isManager()) {
        $nav[] = ['label' => __('Ventas por camarero'), 'href' => url('/waiterstats'), 'section' => __('Caja')];
    }
    if (Auth::user()->isFinance()) {
        $nav[] = ['label' => __('Cobrar'), 'href' => url('/paypanel'), 'section' => __('Caja')];
        $nav[] = ['label' => __('Movimientos'), 'href' => url('/movements'), 'section' => __('Caja')];
        $nav[] = ['label' => __('Cerrar caja'), 'href' => url('/closecash'), 'section' => __('Caja')];
    }
    if (Auth::user()->isAdmin()) {
        $nav[] = ['label' => __('Tickets cobrados'), 'href' => url('/receipts'), 'section' => __('Sistema')];
        $nav[] = ['label' => __('Categorias (Botones)'), 'href' => url('/categories'), 'section' => __('Sistema')];
        $nav[] = ['label' => __('Stats'), 'href' => url('/stats'), 'section' => __('Sistema')];
        $nav[] = ['label' => __('Usuarios'), 'href' => url('/showusers'), 'section' => __('Sistema')];
        $nav[] = ['label' => __('Demo config'), 'href' => url('/appconfig'), 'section' => __('Sistema')];
    }
    $grouped = collect($nav)->groupBy('section');
@endphp

<nav class="space-y-6 p-4 text-sm">
    <a href="{{ route('admin') }}" class="flex items-center gap-2 rounded-lg bg-brand/15 px-3 py-2 font-medium text-slate-900 hover:bg-brand/25">
        <svg class="h-5 w-5 text-brand-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        {{ __('Area Privada') }}
    </a>
    @foreach ($grouped as $section => $items)
        <div>
            <p class="mb-2 px-2 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $section }}</p>
            <ul class="space-y-1">
                @foreach ($items as $item)
                    <li>
                        <a href="{{ $item['href'] }}" class="block rounded-lg px-3 py-2 text-slate-700 hover:bg-slate-100">{{ $item['label'] }}</a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
    <div class="border-t border-slate-200 pt-4">
        <a href="{{ route('order') }}" class="block rounded-lg px-3 py-2 text-slate-700 hover:bg-slate-100">{{ __('Pedidos / Tienda') }}</a>
    </div>
</nav>

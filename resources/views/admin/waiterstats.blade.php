@extends('layouts.admin')

@section('title', __('Ventas por camarero') . ' — ' . config('app.name'))

@section('page', 'admin-waiterstats')

@section('page_header')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-2xl font-bold text-slate-900">{{ __('Ventas por camarero') }}</h1>
        <nav class="flex gap-2" aria-label="{{ __('Periodo') }}">
            @foreach (['today' => __('Hoy'), '7d' => __('7 días'), '30d' => __('30 días')] as $key => $label)
                <a href="{{ route('waiterstats', ['period' => $key]) }}"
                   @class([
                       'rounded-full border px-4 py-1.5 text-sm font-medium no-underline',
                       'border-amber-400 bg-amber-100 text-amber-950' => $period === $key,
                       'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' => $period !== $key,
                   ])>{{ $label }}</a>
            @endforeach
        </nav>
    </div>
@endsection

@section('content')
    <div class="grid gap-6 lg:grid-cols-2">
        <section class="card-tw" aria-label="{{ __('Vendido (pedidos enviados)') }}">
            <div class="card-tw-header text-sm font-semibold uppercase tracking-wide text-slate-500">
                🧾 {{ __('Vendido — quién tomó el pedido') }}
            </div>
            @if (empty($sold))
                <p class="p-6 text-center text-sm text-slate-500">{{ __('Sin pedidos en este periodo.') }}</p>
            @else
                <table class="table-tw">
                    <thead>
                        <tr>
                            <th>{{ __('Persona') }}</th>
                            <th class="text-right">{{ __('Pedidos') }}</th>
                            <th class="text-right">{{ __('Artículos') }}</th>
                            <th class="text-right">{{ __('Total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sold as $row)
                            <tr>
                                <td class="font-medium text-slate-900">{{ $row->person ?: 'Cliente' }}</td>
                                <td class="text-right tabular-nums">{{ $row->orders_count }}</td>
                                <td class="text-right tabular-nums">{{ $row->lines_count }}</td>
                                <td class="text-right font-semibold tabular-nums">@money($row->net * 1.1)</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
            <p class="border-t border-slate-100 px-4 py-2 text-xs text-slate-400">
                {{ __('«Cliente» = pedidos hechos por los clientes desde el QR de la mesa.') }}
            </p>
        </section>

        <section class="card-tw" aria-label="{{ __('Cobrado') }}">
            <div class="card-tw-header text-sm font-semibold uppercase tracking-wide text-slate-500">
                💶 {{ __('Cobrado — quién cerró la cuenta') }}
            </div>
            @if (empty($charged))
                <p class="p-6 text-center text-sm text-slate-500">{{ __('Sin cobros en este periodo.') }}</p>
            @else
                <table class="table-tw">
                    <thead>
                        <tr>
                            <th>{{ __('Persona') }}</th>
                            <th class="text-right">{{ __('Tickets') }}</th>
                            <th class="text-right">{{ __('Total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($charged as $row)
                            <tr>
                                <td class="font-medium text-slate-900">{{ $row->person ?: '—' }}</td>
                                <td class="text-right tabular-nums">{{ $row->receipts_count }}</td>
                                <td class="text-right font-semibold tabular-nums">@money($row->total)</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    </div>
@endsection

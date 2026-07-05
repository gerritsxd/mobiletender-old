@extends('layouts.admin')

@section('title', __('Area Privada') . ' — ' . config('app.name'))

@section('page_header')
    <div>
        <h1 class="text-2xl font-bold text-slate-900">{{ __('Area Privada') }}</h1>
        <p class="mt-1 text-slate-600">{{ __('Gestiona su local') }}</p>
    </div>
@endsection

@section('content')
    <div class="grid gap-8">
        @if (Auth::user()->isEmployee())
            <section>
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('Operación') }}</h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <a href="{{ url('/kitchen') }}" class="card-tw group border-l-4 border-l-brand transition hover:shadow-md">
                        <div class="card-tw-body">
                            <p class="font-semibold text-slate-900">{{ __('Cocina (iPad)') }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ __('Comandas: por hacer, en marcha y listo') }}</p>
                            <span class="mt-4 inline-flex text-sm font-medium text-brand-dark group-hover:underline">{{ __('Abrir') }} →</span>
                        </div>
                    </a>
                    <a href="{{ url('/timereport') }}" class="card-tw group transition hover:shadow-md">
                        <div class="card-tw-body">
                            <p class="font-semibold text-slate-900">{{ __('Marcar Entradad o Salida') }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ __('Checkin o Checkout') }}</p>
                            <span class="mt-4 inline-flex text-sm font-medium text-brand-dark group-hover:underline">{{ __('Abrir') }} →</span>
                        </div>
                    </a>
                </div>
            </section>
        @endif

        @if (Auth::user()->isWaiter())
            <section>
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('Operación') }}</h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <a href="{{ url('/selecttable') }}" class="card-tw group transition hover:shadow-md">
                        <div class="card-tw-body">
                            <p class="font-semibold text-slate-900">{{ __(' Seleccionar mesa') }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ __('Seleccionar una mesa para atender') }}</p>
                            <span class="mt-4 inline-flex text-sm font-medium text-brand-dark group-hover:underline">{{ __('Abrir') }} →</span>
                        </div>
                    </a>
                </div>
            </section>
        @endif

        @if (Auth::user()->isManager())
            <section>
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('MESAS y PRODUCTOS') }}</h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <a href="{{ url('/openorders') }}" class="card-tw group transition hover:shadow-md">
                        <div class="card-tw-body">
                            <p class="font-semibold text-slate-900">{{ __('Mesas y Pedidos') }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ __('Ver una listado de mesas abiertas') }}</p>
                            <span class="mt-4 inline-flex text-sm font-medium text-brand-dark group-hover:underline">{{ __('Abrir') }} →</span>
                        </div>
                    </a>
                    <a href="{{ url('/products') }}" class="card-tw group transition hover:shadow-md">
                        <div class="card-tw-body">
                            <p class="font-semibold text-slate-900">{{ __('Products') }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ __('Habilitar y editar productos') }}</p>
                            <span class="mt-4 inline-flex text-sm font-medium text-brand-dark group-hover:underline">{{ __('Abrir') }} →</span>
                        </div>
                    </a>
                    <a href="{{ url('/stockindex') }}" class="card-tw group transition hover:shadow-md">
                        <div class="card-tw-body">
                            <p class="font-semibold text-slate-900">{{ __('Stock') }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ __('Gestion de Stock') }}</p>
                            <span class="mt-4 inline-flex text-sm font-medium text-brand-dark group-hover:underline">{{ __('Abrir') }} →</span>
                        </div>
                    </a>
                </div>
            </section>
        @endif

        @if (Auth::user()->isFinance())
            <section>
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('CAJA') }}</h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <a href="{{ url('paypanel') }}" class="card-tw group transition hover:shadow-md">
                        <div class="card-tw-body">
                            <p class="font-semibold text-slate-900">{{ __('Cobrar') }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ __('Cobrar mesas') }}</p>
                            <span class="mt-4 inline-flex text-sm font-medium text-brand-dark group-hover:underline">{{ __('Abrir') }} →</span>
                        </div>
                    </a>
                    <a href="{{ url('movements') }}" class="card-tw group transition hover:shadow-md">
                        <div class="card-tw-body">
                            <p class="font-semibold text-slate-900">{{ __('Movimientos') }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ __('Entradas y Salidas de la caja') }}</p>
                            <span class="mt-4 inline-flex text-sm font-medium text-brand-dark group-hover:underline">{{ __('Abrir') }} →</span>
                        </div>
                    </a>
                    <a href="{{ url('closecash') }}" class="card-tw group transition hover:shadow-md">
                        <div class="card-tw-body">
                            <p class="font-semibold text-slate-900">{{ __('Cerrar caja') }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ __('Cerrar la Caja') }}</p>
                            <span class="mt-4 inline-flex text-sm font-medium text-brand-dark group-hover:underline">{{ __('Abrir') }} →</span>
                        </div>
                    </a>
                </div>
            </section>
        @endif

        @if (Auth::user()->isAdmin())
            <section>
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('ADMIN') }}</h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <a href="{{ url('/receipts') }}" class="card-tw group transition hover:shadow-md">
                        <div class="card-tw-body">
                            <p class="font-semibold text-slate-900">{{ __('Tickets cobrados') }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ __('Lista de las ultimas 50 tickets cobrados') }}</p>
                            <span class="mt-4 inline-flex text-sm font-medium text-brand-dark group-hover:underline">{{ __('Abrir') }} →</span>
                        </div>
                    </a>
                    <a href="{{ url('/categories') }}" class="card-tw group transition hover:shadow-md">
                        <div class="card-tw-body">
                            <p class="font-semibold text-slate-900">{{ __('Categorias (Botones)') }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ __('Stats') }}</p>
                            <span class="mt-4 inline-flex text-sm font-medium text-brand-dark group-hover:underline">{{ __('Abrir') }} →</span>
                        </div>
                    </a>
                    <a href="{{ url('/offers') }}" class="card-tw group transition hover:shadow-md">
                        <div class="card-tw-body">
                            <p class="font-semibold text-slate-900">{{ __('Ofertas') }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ __('Crear paquetes de productos a precio fijo') }}</p>
                            <span class="mt-4 inline-flex text-sm font-medium text-brand-dark group-hover:underline">{{ __('Abrir') }} →</span>
                        </div>
                    </a>
                    <a href="{{ url('/stats') }}" class="card-tw group transition hover:shadow-md">
                        <div class="card-tw-body">
                            <p class="font-semibold text-slate-900">{{ __('Stats') }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ __('Ver las estadisticas') }}</p>
                            <span class="mt-4 inline-flex text-sm font-medium text-brand-dark group-hover:underline">{{ __('Abrir') }} →</span>
                        </div>
                    </a>
                    <a href="{{ url('/showusers') }}" class="card-tw group transition hover:shadow-md">
                        <div class="card-tw-body">
                            <p class="font-semibold text-slate-900">{{ __('Usuarios') }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ __('Habilitar y editar Usuarios') }}</p>
                            <span class="mt-4 inline-flex text-sm font-medium text-brand-dark group-hover:underline">{{ __('Abrir') }} →</span>
                        </div>
                    </a>
                    <a href="{{ url('/appconfig') }}" class="card-tw group transition hover:shadow-md">
                        <div class="card-tw-body">
                            <p class="font-semibold text-slate-900">{{ __('Demo config') }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ __('Editar Config') }}</p>
                            <span class="mt-4 inline-flex text-sm font-medium text-brand-dark group-hover:underline">{{ __('Abrir') }} →</span>
                        </div>
                    </a>
                </div>
            </section>
        @endif

        <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50/80 p-6 text-center text-sm text-slate-500">
            {{ __('KPIs próximamente (pedidos hoy, facturación)') }}
        </div>
    </div>
@endsection

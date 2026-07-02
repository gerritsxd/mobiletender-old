@php
    $totalBasket = $totalBasketPrice ?? 0;
    $count = $basketItemCount ?? 0;
@endphp
<header class="sticky top-0 z-40 border-b border-slate-200 bg-white shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-2 px-3 py-2 sm:px-4">
        <div class="flex min-w-0 flex-1 items-center gap-2 sm:gap-4">
            <a href="{{ url('/') }}" class="truncate text-base font-semibold text-slate-900 sm:text-lg">{{ config('app.name') }}</a>
            @if (Session::get('tableNumber'))
                <span class="shrink-0 text-xs text-slate-600 sm:text-sm" title="{{ Session::get('tableNumber') < 100 ? __('Mesa') : __('NrPedido') }}">
                    @if (Session::get('tableNumber') < 100)
                        <b>{{ __('Mesa') }}: {{ Session::get('tableNumber') }}</b>
                    @else
                        <b>{{ __('NrPedido') }}: {{ Session::get('tableNumber') }}</b>
                    @endif
                </span>
            @endif
        </div>
        <div class="flex min-w-0 flex-wrap items-center justify-end gap-2">
            <a id="basketLink" href="{{ url('/basket') }}" class="relative inline-flex" aria-label="{{ __('Cuenta') }}">
                <span
                    class="btn-tab relative inline-flex min-h-[2.5rem] items-center gap-1.5 px-3 py-2 text-sm"
                >
                    <img src="/img/cart.svg" width="18" height="18" class="shrink-0" alt="" aria-hidden="true" />
                    <span class="whitespace-nowrap font-medium tabular-nums" id="ordertotal" aria-live="polite" aria-atomic="true">@money($totalBasket * 1.1)</span>
                    <span
                        id="basketItemCount"
                        @class([
                            'absolute -right-1 -top-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-red-600 px-1 text-xs font-medium text-white',
                            'hidden' => $count < 1,
                        ])
                        aria-hidden="{{ $count < 1 ? 'true' : 'false' }}"
                    >{{ $count > 0 ? $count : '0' }}</span>
                </span>
            </a>
            @if (Auth::user() && Auth::user()->isWaiter())
                <a href="{{ url('/selecttable') }}" class="btn-tab inline-flex min-h-[2.5rem] items-center gap-1 px-3 py-2 text-sm" title="{{ __('mesa') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                        <path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41zm-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9z"/>
                        <path fill-rule="evenodd" d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5.002 5.002 0 0 0 8 3zM3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9H3.1z"/>
                    </svg>
                    <span class="hidden sm:inline">{{ __('mesa') }}</span>
                </a>
            @endif
            @if (Auth::user() && Auth::user()->isEmployee())
                <a href="{{ route('admin') }}" class="btn-secondary text-xs sm:text-sm">{{ __('Volver a admin') }}</a>
            @endif
            @include('partials.shared.language-dropdown')
            @guest
                <a href="{{ route('login') }}" class="btn-secondary whitespace-nowrap px-2 py-1.5 text-xs sm:px-3 sm:text-sm">{{ __('Area Privada') }}</a>
            @else
                <div class="relative" x-data="{ open: false }">
                    <button type="button" @click="open = !open" @click.outside="open = false"
                            class="max-w-[8rem] truncate rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs text-slate-700 hover:bg-slate-50 sm:max-w-none sm:px-3 sm:text-sm">
                        {{ Auth::user()->name }}
                    </button>
                    <div x-show="open" x-transition class="absolute right-0 z-50 mt-1 w-48 rounded-lg border border-slate-200 bg-white py-1 shadow-lg" style="display: none;">
                        <a href="{{ route('admin') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">{{ __('Area Privada') }}</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">{{ __('Logout') }}</button>
                        </form>
                    </div>
                </div>
            @endguest
        </div>
    </div>
</header>

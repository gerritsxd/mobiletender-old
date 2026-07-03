@php
    $tutorialTable = Session::get('tableNumber');
@endphp
@if ($tutorialTable)
<div
    x-data="{
        step: 0,
        total: 6,
        open: false,
        init() {
            if (!localStorage.getItem('mtTutorialSeen')) {
                this.open = true;
                document.body.classList.add('overflow-hidden');
            }
        },
        next() {
            if (this.step < this.total - 1) {
                this.step++;
            } else {
                this.finish();
            }
        },
        finish() {
            localStorage.setItem('mtTutorialSeen', '1');
            this.open = false;
            document.body.classList.remove('overflow-hidden');
        }
    }"
    x-show="open"
    x-cloak
    style="display: none;"
    class="fixed inset-0 z-[90] flex items-center justify-center bg-slate-900/70 p-4"
    role="dialog"
    aria-modal="true"
    aria-label="{{ __('Cómo pedir') }}"
>
    <div class="w-full max-w-sm overflow-hidden rounded-2xl bg-white shadow-2xl">
        @php
            $steps = [
                ['icon' => 'phone', 'title' => __('Escanea tu mesa'), 'text' => __('¡Hecho! Estás en la mesa') . ' ' . $tutorialTable . '.'],
                ['icon' => 'receipt', 'title' => __('Tienes tu cuenta'), 'text' => __('Todo lo que pidas se apunta a tu mesa. La ves arriba a la derecha.')],
                ['icon' => 'eye', 'title' => __('Explora'), 'text' => __('Navega por las categorías o escribe en el buscador.')],
                ['icon' => 'tap', 'title' => __('Selecciona'), 'text' => __('Toca «Añadir» en lo que te apetezca.')],
                ['icon' => 'bell', 'title' => __('Pide'), 'text' => __('Abre tu cuenta y envía el pedido. Te lo llevamos a la mesa.')],
                ['icon' => 'card', 'title' => __('Paga cuando quieras'), 'text' => __('Online desde el móvil, sin esperar al camarero. También en efectivo o tarjeta.')],
            ];
        @endphp

        @foreach ($steps as $i => $s)
            <div x-show="step === {{ $i }}" class="flex flex-col items-center gap-3 px-6 pb-4 pt-8 text-center">
                <span class="flex h-20 w-20 items-center justify-center rounded-2xl bg-sand text-sea" aria-hidden="true">
                    <x-icon name="{{ $s['icon'] }}" class="h-9 w-9" />
                </span>
                <h2 class="text-xl font-bold text-sea">{{ $s['title'] }}</h2>
                <p class="text-sm text-sea-soft">{{ $s['text'] }}</p>
            </div>
        @endforeach

        <div class="flex items-center justify-center gap-1.5 pb-4" aria-hidden="true">
            @foreach ($steps as $i => $s)
                <span
                    class="h-1.5 rounded-full transition-all"
                    :class="step === {{ $i }} ? 'w-5 bg-brand' : 'w-1.5 bg-sand-line'"
                ></span>
            @endforeach
        </div>

        <div class="px-6 pb-6">
            <button
                type="button"
                @click="next()"
                class="w-full rounded-xl bg-sea py-3.5 text-base font-semibold text-white active:scale-[0.98]"
                x-text="step < total - 1 ? '{{ __('Siguiente') }}' : '{{ __('¡A pedir!') }}'"
            ></button>
        </div>
    </div>
</div>
@endif

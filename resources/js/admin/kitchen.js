import $ from 'jquery';

/**
 * Kitchen Display System (/kitchen): three-column board.
 *   Por hacer (pending)  ->  En marcha (preparing)  ->  Listo (ready)
 * Oldest first in each column. Only kitchen-printer lines reach here
 * (filtered server-side). Marking "Listo" notifies the waiter who took it.
 */
const POLL_MS = 8000;
let knownIds = new Set();
let firstLoad = true;

function csrf() {
    const el = document.head.querySelector('meta[name="csrf-token"]');
    return el ? el.content : '';
}

function beep() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.frequency.value = 880;
        gain.gain.setValueAtTime(0.25, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4);
        osc.start();
        osc.stop(ctx.currentTime + 0.4);
    } catch (e) {
        /* no audio */
    }
}

function elapsedLabel(seconds) {
    const m = Math.floor(seconds / 60);
    if (m < 60) return m + ' min';
    return Math.floor(m / 60) + 'h ' + (m % 60) + 'm';
}

function elapsedClass(seconds) {
    const m = seconds / 60;
    if (m < 5) return 'bg-emerald-500/20 text-emerald-300';
    if (m < 15) return 'bg-amber-500/20 text-amber-300';
    return 'bg-red-500/25 text-red-300';
}

function tableLabel(table) {
    const n = parseInt(table, 10);
    if (!isNaN(n) && n > 100) return 'PEDIDO ' + table;
    return 'MESA ' + table;
}

function setStatus(id, status, $btn) {
    $btn.prop('disabled', true).css('opacity', 0.6);
    $.ajax({
        url: '/kitchen/orders/' + id + '/status',
        type: 'POST',
        data: { status: status },
        headers: { 'X-CSRF-TOKEN': csrf() },
        success: function () { refresh(); },
        error: function () { $btn.prop('disabled', false).css('opacity', 1); },
    });
}

function renderCard(order) {
    const status = order.status;
    const isDone = status === 'ready';
    const isDoing = status === 'preparing';

    const border = isDone ? 'border-emerald-600' : isDoing ? 'border-amber-600' : 'border-slate-700';
    const bg = isDone ? 'bg-emerald-950/50' : isDoing ? 'bg-amber-950/40' : 'bg-slate-900';

    const $card = $('<div class="rounded-xl border ' + border + ' ' + bg + ' p-3"></div>');

    const $head = $('<div class="mb-2 flex items-center justify-between gap-2"></div>');
    $head.append($('<span class="text-lg font-extrabold tracking-tight"></span>').text(tableLabel(order.table)));
    $head.append(
        $('<span class="rounded-full px-2.5 py-1 text-xs font-bold tabular-nums ' + elapsedClass(order.stage_s) + '"></span>')
            .text(elapsedLabel(order.stage_s))
    );
    $card.append($head);

    const $list = $('<ul class="mb-3 space-y-1"></ul>');
    order.lines.forEach(function (l) {
        const $li = $('<li class="flex items-baseline gap-2 text-base"></li>');
        $li.append($('<span class="font-bold tabular-nums text-amber-300"></span>').text(l.qty + '×'));
        $li.append($('<span></span>').text(l.name));
        $list.append($li);
    });
    $card.append($list);

    if (isDone) {
        // DONE: emphasise WHERE it goes + who takes it there.
        $card.append(
            $('<p class="mb-2 text-sm font-semibold text-emerald-300"></p>')
                .text('→ ' + tableLabel(order.table) + (order.ordered_by ? ' · ' + order.ordered_by : ''))
        );
        const $btn = $('<button type="button" class="w-full rounded-lg bg-emerald-500 py-3 text-base font-bold text-emerald-950 active:scale-[0.98]">✓ ' + 'ENTREGADO' + '</button>');
        $btn.on('click', function () { setStatus(order.id, 'delivered', $(this)); });
        $card.append($btn);
    } else if (isDoing) {
        $card.append($('<p class="mb-2 text-xs text-slate-400"></p>').text('Pedido: ' + (order.ordered_by || 'Cliente')));
        const $btn = $('<button type="button" class="w-full rounded-lg bg-emerald-500 py-3 text-base font-bold text-emerald-950 active:scale-[0.98]">✓ LISTO</button>');
        $btn.on('click', function () { setStatus(order.id, 'ready', $(this)); });
        $card.append($btn);
    } else {
        // TODO
        $card.append($('<p class="mb-2 text-xs text-slate-400"></p>').text('Pedido: ' + (order.ordered_by || 'Cliente')));
        const $btn = $('<button type="button" class="w-full rounded-lg bg-amber-400 py-3 text-base font-bold text-amber-950 active:scale-[0.98]">▶ EMPEZAR</button>');
        $btn.on('click', function () { setStatus(order.id, 'preparing', $(this)); });
        $card.append($btn);
    }

    return $card;
}

function render(data) {
    const orders = (data && data.orders) || [];
    const cols = {
        pending: $('#col-todo').empty(),
        preparing: $('#col-doing').empty(),
        ready: $('#col-done').empty(),
    };
    const counts = { pending: 0, preparing: 0, ready: 0 };

    orders.forEach(function (o) {
        if (!cols[o.status]) return;
        cols[o.status].append(renderCard(o));
        counts[o.status]++;
    });

    $('#count-todo').text(counts.pending);
    $('#count-doing').text(counts.preparing);
    $('#count-done').text(counts.ready);

    // Chime on genuinely new tickets landing in TODO.
    const incoming = orders.filter(function (o) { return o.status === 'pending' && !knownIds.has(o.id); });
    if (!firstLoad && incoming.length > 0) {
        beep();
    }
    orders.forEach(function (o) { knownIds.add(o.id); });
    firstLoad = false;
}

function refresh() {
    $.getJSON('/kitchen/orders.json').done(render);
}

$(function () {
    if ($('body').data('page') !== 'kitchen') return;

    refresh();
    setInterval(refresh, POLL_MS);

    setInterval(function () {
        const d = new Date();
        $('#kds-clock').text(String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0'));
    }, 1000);

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') refresh();
    });
});

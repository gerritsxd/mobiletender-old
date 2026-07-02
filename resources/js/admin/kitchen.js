import $ from 'jquery';

/**
 * Kitchen Display System (/kitchen): polls orders, renders them FIFO
 * (oldest first = cook first), lets the kitchen mark orders LISTO and
 * waiters mark them ENTREGADO. Runs full-screen on an iPad.
 */
const POLL_MS = 8000;
let knownIds = new Set();
let currentStation = 'all';
let lastData = null;
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
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.5);
        osc.start();
        osc.stop(ctx.currentTime + 0.5);
    } catch (e) {
        // no audio available — visual cues still work
    }
}

function elapsedLabel(seconds) {
    const m = Math.floor(seconds / 60);
    if (m < 60) {
        return m + ' min';
    }
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
    if (!isNaN(n) && n > 100) {
        return 'PEDIDO ' + table;
    }
    return 'MESA ' + table;
}

function linesForStation(order) {
    if (currentStation === 'all') {
        return order.lines;
    }
    return order.lines.filter(function (l) { return String(l.printto) === String(currentStation); });
}

function setStatus(id, status, $btn) {
    $btn.prop('disabled', true);
    $.ajax({
        url: '/kitchen/orders/' + id + '/status',
        type: 'POST',
        data: { status: status },
        headers: { 'X-CSRF-TOKEN': csrf() },
        success: function () { refresh(); },
        error: function () { $btn.prop('disabled', false); },
    });
}

function renderCard(order, index) {
    const lines = linesForStation(order);
    const isReady = order.status === 'ready';
    const $card = $('<div class="rounded-xl border p-3 ' +
        (isReady
            ? 'border-emerald-600 bg-emerald-950/60'
            : 'border-slate-700 bg-slate-900') + '"></div>');

    const $head = $('<div class="mb-2 flex items-center justify-between gap-2"></div>');
    $head.append(
        $('<span class="text-lg font-extrabold tracking-tight"></span>')
            .text((isReady ? '' : '#' + (index + 1) + ' · ') + tableLabel(order.table))
    );
    $head.append(
        $('<span class="rounded-full px-2.5 py-1 text-xs font-bold tabular-nums ' + elapsedClass(order.elapsed_s) + '"></span>')
            .text(elapsedLabel(order.elapsed_s))
    );
    $card.append($head);

    const $list = $('<ul class="mb-3 space-y-1"></ul>');
    lines.forEach(function (l) {
        const $li = $('<li class="flex items-baseline gap-2 text-base"></li>');
        $li.append($('<span class="font-bold tabular-nums text-amber-300"></span>').text(l.qty + '×'));
        $li.append($('<span></span>').text(l.name));
        if (currentStation === 'all' && l.printto) {
            $li.append($('<span class="ml-auto rounded bg-slate-800 px-1.5 text-[10px] text-slate-400"></span>').text('P' + l.printto));
        }
        $list.append($li);
    });
    $card.append($list);

    $card.append(
        $('<p class="mb-2 text-xs text-slate-500"></p>').text('Pedido por: ' + (order.ordered_by || 'Cliente'))
    );

    if (isReady) {
        const $btn = $('<button type="button" class="w-full rounded-lg bg-emerald-500 py-3 text-base font-bold text-emerald-950 active:scale-[0.98]">✓ ENTREGADO</button>');
        $btn.on('click', function () { setStatus(order.id, 'delivered', $(this)); });
        $card.append($btn);
    } else {
        const $btn = $('<button type="button" class="w-full rounded-lg bg-amber-400 py-3 text-base font-bold text-amber-950 active:scale-[0.98]">✓ LISTO</button>');
        $btn.on('click', function () { setStatus(order.id, 'ready', $(this)); });
        $card.append($btn);
    }

    return $card;
}

function renderStations(orders) {
    const stations = new Set();
    orders.forEach(function (o) {
        o.lines.forEach(function (l) {
            if (l.printto) {
                stations.add(String(l.printto));
            }
        });
    });
    const $wrap = $('#kds-stations');
    $wrap.empty();
    const all = ['all'].concat(Array.from(stations).sort());
    all.forEach(function (s) {
        const label = s === 'all' ? 'Todo' : 'Estación ' + s;
        const active = currentStation === s;
        const $chip = $('<button type="button" class="rounded-full border px-3 py-1.5 text-sm font-medium ' +
            (active ? 'border-amber-400 bg-amber-400 text-amber-950' : 'border-slate-700 text-slate-300') +
            '"></button>').text(label);
        $chip.on('click', function () {
            currentStation = s;
            render(lastData);
        });
        $wrap.append($chip);
    });
}

function render(data) {
    if (!data) {
        return;
    }
    lastData = data;
    const orders = data.orders || [];
    renderStations(orders);

    const pending = orders.filter(function (o) { return o.status === 'pending' && linesForStation(o).length > 0; });
    const ready = orders.filter(function (o) { return o.status === 'ready' && linesForStation(o).length > 0; });

    const $pending = $('#kds-pending').empty();
    pending.forEach(function (o, i) { $pending.append(renderCard(o, i)); });
    $('#kds-pending-empty').toggleClass('hidden', pending.length > 0);

    const $ready = $('#kds-ready').empty();
    ready.forEach(function (o, i) { $ready.append(renderCard(o, i)); });
    $('#kds-ready-empty').toggleClass('hidden', ready.length > 0);

    // Chime on genuinely new orders (not on first page load).
    const incoming = pending.filter(function (o) { return !knownIds.has(o.id); });
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
    if ($('body').data('page') !== 'kitchen') {
        return;
    }

    refresh();
    setInterval(refresh, POLL_MS);

    setInterval(function () {
        const d = new Date();
        $('#kds-clock').text(
            String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0')
        );
    }, 1000);

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') {
            refresh();
        }
    });
});

import $ from 'jquery';

/**
 * Kitchen Display System (/kitchen).
 *  - Overview strip: total still to cook, per product ("5 Atún Tomate").
 *  - Three columns of INDIVIDUAL item tickets: Por hacer -> En marcha -> Listo.
 * Each dish is tracked on its own and marked ready independently, because a
 * table's dishes don't all leave the pass at the same time.
 */
const POLL_MS = 8000;
let knownIds = new Set();
let firstLoad = true;

function csrf() {
    const el = document.head.querySelector('meta[name="csrf-token"]');
    return el ? el.content : '';
}

function beep() {
    if (window.mtAlerts) {
        window.mtAlerts.alertUser('order', [180, 90, 180]);
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

function statusBase() {
    return $('body').data('status-base') || '/kitchen/lines';
}

function feedUrl() {
    return $('body').data('feed') || '/kitchen/orders.json';
}

function setStatus(id, status, $btn) {
    $btn.prop('disabled', true).css('opacity', 0.6);
    $.ajax({
        url: statusBase() + '/' + id + '/status',
        type: 'POST',
        data: { status: status },
        headers: { 'X-CSRF-TOKEN': csrf() },
        success: function () { refresh(); },
        error: function () { $btn.prop('disabled', false).css('opacity', 1); },
    });
}

function renderCard(item) {
    const status = item.status;
    const isDone = status === 'ready';
    const isDoing = status === 'preparing';

    const border = isDone ? 'border-emerald-600' : isDoing ? 'border-amber-600' : 'border-slate-700';
    const bg = isDone ? 'bg-emerald-950/50' : isDoing ? 'bg-amber-950/40' : 'bg-slate-900';

    const $card = $('<div class="rounded-xl border ' + border + ' ' + bg + ' p-3"></div>');

    const $head = $('<div class="mb-2 flex items-center justify-between gap-2"></div>');
    $head.append(
        $('<span class="text-base font-extrabold tracking-tight"></span>')
            .append($('<span class="text-amber-300"></span>').text(item.qty + '× '))
            .append(document.createTextNode(item.product))
    );
    $head.append(
        $('<span class="shrink-0 rounded-full px-2 py-1 text-xs font-bold tabular-nums ' + elapsedClass(item.stage_s) + '"></span>')
            .text(elapsedLabel(item.stage_s))
    );
    $card.append($head);

    $card.append(
        $('<p class="mb-2 text-xs ' + (isDone ? 'font-semibold text-emerald-300' : 'text-slate-400') + '"></p>')
            .text((isDone ? '→ ' : '') + tableLabel(item.table) + (item.ordered_by ? ' · ' + item.ordered_by : ''))
    );

    if (isDone) {
        const $btn = $('<button type="button" class="w-full rounded-lg bg-emerald-500 py-2.5 text-sm font-bold uppercase tracking-wide text-emerald-950 active:scale-[0.98]">Entregado</button>');
        $btn.on('click', function () { setStatus(item.id, 'delivered', $(this)); });
        $card.append($btn);
    } else if (isDoing) {
        const $btn = $('<button type="button" class="w-full rounded-lg bg-emerald-500 py-2.5 text-sm font-bold uppercase tracking-wide text-emerald-950 active:scale-[0.98]">Listo</button>');
        $btn.on('click', function () { setStatus(item.id, 'ready', $(this)); });
        $card.append($btn);
    } else {
        const $btn = $('<button type="button" class="w-full rounded-lg bg-amber-400 py-2.5 text-sm font-bold uppercase tracking-wide text-amber-950 active:scale-[0.98]">Empezar</button>');
        $btn.on('click', function () { setStatus(item.id, 'preparing', $(this)); });
        $card.append($btn);
    }

    return $card;
}

function renderOverview(overview) {
    const $wrap = $('#kds-overview').empty();
    if (!overview.length) {
        $wrap.append('<span class="text-sm text-slate-500">Sin nada en cocina.</span>');
        return;
    }
    overview.forEach(function (o) {
        $wrap.append(
            $('<span class="inline-flex items-center gap-2 rounded-lg bg-slate-800 px-3 py-1.5"></span>')
                .append($('<span class="text-lg font-extrabold tabular-nums text-amber-300"></span>').text(o.qty))
                .append($('<span class="text-sm font-semibold text-slate-100"></span>').text(o.product))
        );
    });
}

function render(data) {
    renderOverview((data && data.overview) || []);

    const items = (data && data.items) || [];
    const cols = {
        pending: $('#col-todo').empty(),
        preparing: $('#col-doing').empty(),
        ready: $('#col-done').empty(),
    };
    const counts = { pending: 0, preparing: 0, ready: 0 };

    items.forEach(function (it) {
        if (!cols[it.status]) return;
        cols[it.status].append(renderCard(it));
        counts[it.status]++;
    });

    $('#count-todo').text(counts.pending);
    $('#count-doing').text(counts.preparing);
    $('#count-done').text(counts.ready);

    const incoming = items.filter(function (it) { return it.status === 'pending' && !knownIds.has(it.id); });
    if (!firstLoad && incoming.length > 0) {
        beep();
    }
    items.forEach(function (it) { knownIds.add(it.id); });
    firstLoad = false;
}

function refresh() {
    $.getJSON(feedUrl()).done(render);
}

function markSoundOn() {
    $('#kds-sound-toggle')
        .removeClass('border-amber-500 bg-amber-500/10 text-amber-300')
        .addClass('border-emerald-500 bg-emerald-500/10 text-emerald-300');
    $('#kds-sound-label').text('Avisos activos');
}

$(function () {
    if ($('body').data('page') !== 'station') return;

    // The station screen is passive, so make enabling sound explicit: one tap
    // unlocks audio + a test chime for the whole shift.
    $('#kds-sound-toggle').on('click', function () {
        if (window.mtAlerts) {
            window.mtAlerts.unlock();
            window.mtAlerts.alertUser('ready', [120]);
        }
        markSoundOn();
    });
    document.addEventListener('mt-audio-unlocked', markSoundOn);

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

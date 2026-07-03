import $ from 'jquery';

/**
 * Waiter "your order is ready" notifier. Runs on any page for a logged-in
 * staff member: polls /kitchen/ready-for-me.json and pops a toast (+ chime +
 * vibrate) the first time each of their orders becomes ready to serve.
 * Not shown on the kitchen board itself.
 */
const POLL_MS = 15000;
const SEEN_KEY = 'waiterReadySeen';

function isStaff() {
    return !!document.head.querySelector('meta[name="mt-staff"]');
}

function seen() {
    try {
        return JSON.parse(sessionStorage.getItem(SEEN_KEY) || '[]');
    } catch (e) {
        return [];
    }
}

function markSeen(ids) {
    const merged = seen().concat(ids);
    sessionStorage.setItem(SEEN_KEY, JSON.stringify(merged.slice(-100)));
}

function chime() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        [880, 1175].forEach(function (freq, i) {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.value = freq;
            const t = ctx.currentTime + i * 0.18;
            gain.gain.setValueAtTime(0.0001, t);
            gain.gain.exponentialRampToValueAtTime(0.3, t + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, t + 0.35);
            osc.start(t);
            osc.stop(t + 0.35);
        });
    } catch (e) {
        /* no audio */
    }
}

function ensureRoot() {
    let $root = $('#waiter-toast-root');
    if (!$root.length) {
        $root = $('<div id="waiter-toast-root" class="fixed inset-x-0 top-3 z-[95] mx-auto flex max-w-sm flex-col gap-2 px-3"></div>');
        $('body').append($root);
    }
    return $root;
}

function toast(tableLabel) {
    const $root = ensureRoot();
    const $t = $(
        '<div class="pointer-events-auto flex items-center gap-3 rounded-xl bg-emerald-500 px-4 py-3 text-emerald-950 shadow-lg" role="alert">' +
            '<span class="text-2xl">🛎️</span>' +
            '<div class="flex-1"><p class="font-bold">¡Pedido listo!</p><p class="text-sm">' + tableLabel + ' — recoger en cocina</p></div>' +
            '<button type="button" class="rounded-full px-2 text-emerald-900" aria-label="Cerrar">✕</button>' +
        '</div>'
    );
    $t.on('click', function () { $t.remove(); });
    $root.append($t);
    if (navigator.vibrate) navigator.vibrate([200, 100, 200]);
    setTimeout(function () { $t.fadeOut(400, function () { $(this).remove(); }); }, 12000);
}

function tableLabel(table) {
    const n = parseInt(table, 10);
    if (!isNaN(n) && n > 100) return 'Pedido ' + table;
    return 'Mesa ' + table;
}

function poll() {
    $.getJSON('/kitchen/ready-for-me.json')
        .done(function (data) {
            const orders = (data && data.orders) || [];
            const already = seen();
            const fresh = orders.filter(function (o) { return already.indexOf(o.id) === -1; });
            if (fresh.length) {
                fresh.forEach(function (o) { toast(tableLabel(o.table)); });
                chime();
                markSeen(fresh.map(function (o) { return o.id; }));
            }
        });
}

$(function () {
    if (!isStaff()) return;
    if ($('body').data('page') === 'kitchen') return; // cook is not the runner

    poll();
    setInterval(poll, POLL_MS);
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') poll();
    });
});

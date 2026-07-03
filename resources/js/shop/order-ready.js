import $ from 'jquery';

/**
 * Customer "your order is on its way" popup. Polls /order/ready-status.json
 * (session-based, matched to the customer's table) and shows a celebratory
 * modal the first time each of their orders is marked ready in the kitchen.
 * Runs only for customers (not logged-in staff — they get the waiter toast).
 */
const POLL_MS = 15000;
const SEEN_KEY = 'clientReadySeen';

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
        [660, 880, 1175].forEach(function (freq, i) {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.value = freq;
            const t = ctx.currentTime + i * 0.15;
            gain.gain.setValueAtTime(0.0001, t);
            gain.gain.exponentialRampToValueAtTime(0.3, t + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, t + 0.3);
            osc.start(t);
            osc.stop(t + 0.3);
        });
    } catch (e) {
        /* no audio */
    }
}

function showPopup() {
    // One popup at a time is enough even if several orders turn ready together.
    if ($('#client-ready-popup').length) {
        return;
    }
    const $overlay = $(
        '<div id="client-ready-popup" class="fixed inset-0 z-[95] flex items-center justify-center bg-slate-900/70 p-4">' +
            '<div class="w-full max-w-xs overflow-hidden rounded-2xl bg-white text-center shadow-2xl">' +
                '<div class="bg-emerald-500 px-6 py-6 text-white">' +
                    '<div class="text-5xl">🛎️</div>' +
                    '<p class="mt-2 text-xl font-extrabold">¡Pedido listo!</p>' +
                '</div>' +
                '<div class="px-6 py-5">' +
                    '<p class="text-slate-700">Tu pedido va de camino a tu mesa 🍽️</p>' +
                    '<button type="button" class="mt-4 w-full rounded-xl bg-slate-900 py-3 font-semibold text-white active:scale-[0.98]" data-close-ready>¡Genial!</button>' +
                '</div>' +
            '</div>' +
        '</div>'
    );
    $overlay.on('click', function (e) {
        if (e.target === this || $(e.target).is('[data-close-ready]')) {
            $overlay.remove();
        }
    });
    $('body').append($overlay);
    if (navigator.vibrate) navigator.vibrate([200, 100, 200, 100, 200]);
    chime();
}

function poll() {
    $.getJSON('/order/ready-status.json')
        .done(function (data) {
            const orders = (data && data.orders) || [];
            const already = seen();
            const fresh = orders.filter(function (o) { return already.indexOf(o.id) === -1; });
            if (fresh.length) {
                showPopup();
                markSeen(fresh.map(function (o) { return o.id; }));
            }
        });
}

$(function () {
    if (isStaff()) return; // customers only
    const page = $('body').data('page');
    if (!page || String(page).indexOf('admin') === 0 || page === 'kitchen') return;

    poll();
    setInterval(poll, POLL_MS);
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') poll();
    });
});

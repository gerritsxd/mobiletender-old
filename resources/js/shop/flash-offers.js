import $ from 'jquery';
import { updateBasketBadge } from './cart-total.js';

/**
 * Flash offers: polls /flash-offers/poll while a customer is on a shop page
 * and shows a live banner with countdown + one-tap "add to order".
 * Also fires a browser notification when the tab is hidden (if permitted).
 */
const POLL_INTERVAL_MS = 20000;
const DISMISSED_KEY = 'flashOffersDismissed';
const NOTIFIED_KEY = 'flashOffersNotified';

let pollTimer = null;

function getStored(key) {
    try {
        return JSON.parse(sessionStorage.getItem(key) || '[]');
    } catch (e) {
        return [];
    }
}

function addStored(key, id) {
    const list = getStored(key);
    if (!list.includes(id)) {
        list.push(id);
        sessionStorage.setItem(key, JSON.stringify(list.slice(-50)));
    }
}

function formatCountdown(secondsLeft) {
    const s = Math.max(0, secondsLeft);
    const m = Math.floor(s / 60);
    const sec = s % 60;
    if (m >= 60) {
        return Math.floor(m / 60) + 'h ' + (m % 60) + 'm';
    }
    return m + ':' + String(sec).padStart(2, '0');
}

function escapeHtml(text) {
    return $('<span>').text(text == null ? '' : String(text)).html();
}

function renderOffer(offer) {
    const deadline = Date.now() + offer.seconds_left * 1000;
    const priceHtml = offer.price != null
        ? '<span class="font-bold">' + Number(offer.price).toFixed(2) + '€</span>' +
          (offer.regular_price != null
              ? ' <span class="line-through opacity-70">' + Number(offer.regular_price).toFixed(2) + '€</span>'
              : '')
        : '';

    const boltSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="inline h-4 w-4 -mt-0.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/></svg>';
    const $el = $(
        '<div class="flash-offer-banner pointer-events-auto overflow-hidden rounded-xl bg-brand text-sea shadow-lg" role="alert">' +
            '<div class="flex items-start gap-3 p-3">' +
                '<div class="min-w-0 flex-1">' +
                    '<p class="text-sm font-bold">' + boltSvg + ' ' + escapeHtml(offer.title) + '</p>' +
                    (offer.message ? '<p class="mt-0.5 text-xs">' + escapeHtml(offer.message) + '</p>' : '') +
                    '<p class="mt-1 text-xs">' +
                        (offer.product_name ? escapeHtml(offer.product_name) + ' ' : '') + priceHtml +
                        ' · <span data-flash-countdown>' + formatCountdown(offer.seconds_left) + '</span>' +
                    '</p>' +
                '</div>' +
                '<button type="button" class="shrink-0 rounded-full p-1 text-sea hover:bg-brand-dark" data-flash-dismiss aria-label="Cerrar">✕</button>' +
            '</div>' +
            (offer.product_id
                ? '<button type="button" class="block w-full bg-sea py-2 text-center text-sm font-semibold text-brand" data-flash-add>' +
                      '¡Lo quiero! Añadir al pedido' +
                  '</button>'
                : '') +
        '</div>'
    );

    $el.data('offer', offer);
    $el.data('deadline', deadline);

    $el.on('click', '[data-flash-dismiss]', function () {
        addStored(DISMISSED_KEY, offer.id);
        $el.remove();
    });

    $el.on('click', '[data-flash-add]', function () {
        const $btn = $(this);
        $btn.prop('disabled', true).text('Añadiendo…');
        $.ajax({
            url: '/order/addflashoffer/' + offer.id,
            type: 'POST',
            dataType: 'json',
            success: function (data) {
                if (data && data.total != null) {
                    updateBasketBadge(data.total, data.lineCount);
                }
                $btn.text('¡Añadido!');
                setTimeout(function () {
                    addStored(DISMISSED_KEY, offer.id);
                    $el.remove();
                }, 1200);
            },
            error: function (xhr) {
                const msg = xhr.responseJSON && xhr.responseJSON.error
                    ? xhr.responseJSON.error
                    : 'No se pudo añadir la oferta.';
                $btn.prop('disabled', false).text(msg);
            },
        });
    });

    return $el;
}

function notifyIfHidden(offer) {
    if (document.visibilityState !== 'hidden') {
        return;
    }
    if (!('Notification' in window) || Notification.permission !== 'granted') {
        return;
    }
    if (getStored(NOTIFIED_KEY).includes(offer.id)) {
        return;
    }
    addStored(NOTIFIED_KEY, offer.id);
    try {
        const body = (offer.message ? offer.message + ' · ' : '') +
            (offer.price != null ? Number(offer.price).toFixed(2) + '€' : '');
        new Notification(offer.title, { body: body, tag: 'flash-offer-' + offer.id });
    } catch (e) {
        // Notification constructor unavailable (e.g. Android requires SW) — banner still shows.
    }
}

function refreshOffers() {
    $.getJSON('/flash-offers/poll')
        .done(function (data) {
            const offers = (data && data.offers) || [];
            const dismissed = getStored(DISMISSED_KEY);
            const $root = $('#flash-offer-root');
            const liveIds = [];

            offers.forEach(function (offer) {
                liveIds.push(offer.id);
                if (dismissed.includes(offer.id)) {
                    return;
                }
                const $existing = $root.children('[data-flash-id="' + offer.id + '"]');
                if ($existing.length) {
                    $existing.data('deadline', Date.now() + offer.seconds_left * 1000);
                    return;
                }
                const $el = renderOffer(offer).attr('data-flash-id', offer.id);
                $root.append($el);
                if (navigator.vibrate) {
                    navigator.vibrate(200);
                }
                notifyIfHidden(offer);
            });

            // Remove banners for offers that ended or were stopped.
            $root.children('[data-flash-id]').each(function () {
                const id = $(this).attr('data-flash-id');
                if (!liveIds.includes(id)) {
                    $(this).remove();
                }
            });
        });
}

function tickCountdowns() {
    $('#flash-offer-root [data-flash-id]').each(function () {
        const $el = $(this);
        const secondsLeft = Math.round(($el.data('deadline') - Date.now()) / 1000);
        if (secondsLeft <= 0) {
            $el.remove();
            return;
        }
        $el.find('[data-flash-countdown]').text(formatCountdown(secondsLeft));
    });
}

function requestNotificationPermission() {
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }
}

$(function () {
    const $root = $('#flash-offer-root');
    if (!$root.length) {
        return;
    }

    refreshOffers();
    pollTimer = setInterval(refreshOffers, POLL_INTERVAL_MS);
    setInterval(tickCountdowns, 1000);

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') {
            refreshOffers();
        }
    });

    // Ask for notification permission on the first real interaction (user gesture),
    // so hidden-tab flash offers can still reach the customer.
    $(document).one('click', '.add-to-cart, [data-flash-add]', requestNotificationPermission);
});

export { refreshOffers };

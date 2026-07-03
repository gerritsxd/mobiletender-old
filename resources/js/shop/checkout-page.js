import $ from 'jquery';
import { initPayPalCheckout } from './paypal-checkout.js';

$(function () {
    const cfgEl = document.getElementById('checkout-page-config');
    if (!cfgEl) return;
    let cfg;
    try {
        cfg = JSON.parse(cfgEl.textContent);
    } catch (e) {
        return;
    }

    const ticketId = cfg.ticketId;

    $('.add-to-cart').on('click', function () {
        $('#overlay').show();
    });
    $('.eatInButton').on('click', function () {
        $('#eatinrow').slideToggle('slow');
    });

    $('#pagarEfectivo').on('click', function () {
        window.location.href = '/checkout/printOrderEfectivo/' + ticketId;
    });
    $('#apuntarEnLaMesa').on('click', function () {
        window.location.href = '/checkout/printOrder/' + ticketId;
    });
    $('#pagarOnline').on('click', function () {
        window.location.href = '/checkout/pay';
    });
    $('#pagarTarjeta').on('click', function () {
        window.location.href = '/checkout/printOrderTarjeta/' + ticketId;
    });

    $('#eatin').on('click', function () {
        $('#scan-qr-instructions').slideToggle('slow');
    });
    $('#takeaway').on('click', function () {
        $('#div-takeaway').slideToggle('slow');
        window.location.href = '/checkout/pickup';
    });

    if (!cfg.paypalPrepay || !cfg.paypalClientId) {
        return;
    }

    const onApproveUrl = cfg.hasTableNumber
        ? '/checkout/printOrder/' + ticketId
        : '/checkout/confirmForTable/' + (document.getElementById('table_number')?.value || '');

    initPayPalCheckout({
        clientId: cfg.paypalClientId,
        amount: cfg.newLinesTotal,
        context: 'checkout',
        onApproveUrl,
        live: !!cfg.live,
        applePayContainer: document.getElementById('applepay-container'),
        googlePayContainer: document.getElementById('googlepay-container'),
        paypalContainer: document.getElementById('paypal-button-container'),
    });
});

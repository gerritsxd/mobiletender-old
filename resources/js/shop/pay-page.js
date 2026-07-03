import $ from 'jquery';
import { initPayPalCheckout } from './paypal-checkout.js';

$(function () {
    const cfgEl = document.getElementById('pay-page-config');
    if (!cfgEl) return;
    let cfg;
    try {
        cfg = JSON.parse(cfgEl.textContent);
    } catch (e) {
        return;
    }
    if (!cfg.paypalClientId) return;

    initPayPalCheckout({
        clientId: cfg.paypalClientId,
        amount: cfg.amount,
        context: 'pay',
        onApproveUrl: cfg.onApproveUrl,
        live: !!cfg.live,
        applePayContainer: document.getElementById('applepay-container'),
        googlePayContainer: document.getElementById('googlepay-container'),
        paypalContainer: document.getElementById('paypal-button-container'),
    });
});

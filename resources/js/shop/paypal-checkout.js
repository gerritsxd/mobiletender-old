import $ from 'jquery';

const SDK_BASE = 'https://www.paypal.com/sdk/js';
const GOOGLE_PAY_JS = 'https://pay.google.com/gp/p/js/pay.js';
const APPLE_PAY_JS = 'https://applepay.cdn-apple.com/jsapi/v1.latest/apple-pay-sdk.js';

let sdkPromise = null;

function loadScript(src) {
    return new Promise((resolve, reject) => {
        const existing = document.querySelector(`script[data-src="${src}"]`);
        if (existing) {
            existing.addEventListener('load', () => resolve());
            existing.addEventListener('error', () => reject(new Error('script load failed: ' + src)));
            if (existing.dataset.loaded === '1') {
                resolve();
            }
            return;
        }
        const s = document.createElement('script');
        s.src = src;
        s.async = true;
        s.dataset.src = src;
        s.onload = () => {
            s.dataset.loaded = '1';
            resolve();
        };
        s.onerror = () => reject(new Error('script load failed: ' + src));
        document.head.appendChild(s);
    });
}

function loadPayPalSdk(clientId) {
    if (sdkPromise) {
        return sdkPromise;
    }
    if (window.paypal && window.paypal.Applepay && window.paypal.Googlepay) {
        sdkPromise = Promise.resolve(window.paypal);
        return sdkPromise;
    }
    const params = new URLSearchParams({
        'client-id': clientId,
        'currency': 'EUR',
        'components': 'buttons,applepay,googlepay',
    });
    sdkPromise = loadScript(`${SDK_BASE}?${params.toString()}`).then(() => window.paypal);
    return sdkPromise;
}

async function createServerOrder({ amount, context, csrfToken }) {
    const res = await fetch('/paypal/create-order', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({ amount, context }),
    });
    if (!res.ok) {
        throw new Error('create order failed: ' + res.status);
    }
    return res.json();
}

async function captureServerOrder(orderId, csrfToken) {
    const res = await fetch(`/paypal/capture-order/${encodeURIComponent(orderId)}`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken,
        },
    });
    if (!res.ok) {
        const err = new Error('capture failed: ' + res.status);
        err.status = res.status;
        throw err;
    }
    return res.json();
}

function getCsrfToken() {
    const el = document.head.querySelector('meta[name="csrf-token"]');
    return el ? el.content : '';
}

function showOverlay() {
    $('#overlay').fadeIn(150);
}
function hideOverlay() {
    $('#overlay').fadeOut(150);
}

async function setupApplePay({ paypal, amount, context, onSuccess, onError, container }) {
    if (!container || typeof window.ApplePaySession === 'undefined') {
        return false;
    }
    if (!window.ApplePaySession.canMakePayments()) {
        return false;
    }
    try {
        await loadScript(APPLE_PAY_JS);
    } catch (e) {
        console.warn('Apple Pay SDK load failed', e);
        return false;
    }
    const applepay = paypal.Applepay();
    let config;
    try {
        config = await applepay.config();
    } catch (e) {
        console.warn('Apple Pay config error', e);
        return false;
    }
    if (!config || !config.isEligible) {
        return false;
    }
    const csrfToken = getCsrfToken();
    container.innerHTML = '';
    const btn = document.createElement('apple-pay-button');
    btn.setAttribute('buttonstyle', 'black');
    btn.setAttribute('type', 'pay');
    btn.setAttribute('locale', document.documentElement.lang || 'es-ES');
    btn.style.cssText = '--apple-pay-button-width:100%;--apple-pay-button-height:48px;--apple-pay-button-border-radius:10px;display:block;width:100%;';
    btn.addEventListener('click', async () => {
        try {
            const orderResult = await createServerOrder({ amount, context, csrfToken });
            if (!orderResult.id) {
                throw new Error('no order id');
            }

            const session = new window.ApplePaySession(4, {
                countryCode: config.countryCode || 'ES',
                merchantCapabilities: config.merchantCapabilities || ['supports3DS'],
                supportedNetworks: config.supportedNetworks || ['visa', 'masterCard', 'amex'],
                currencyCode: 'EUR',
                requiredBillingContactFields: ['name', 'postalAddress'],
                total: {
                    label: config.merchantName || document.title,
                    type: 'final',
                    amount: String(amount),
                },
            });

            session.onvalidatemerchant = async (event) => {
                try {
                    const merchantSession = await applepay.validateMerchant({
                        validationUrl: event.validationURL,
                        displayName: config.merchantName || document.title,
                    });
                    session.completeMerchantValidation(merchantSession.merchantSession);
                } catch (e) {
                    console.error('Apple Pay merchant validation failed', e);
                    session.abort();
                    onError && onError(e);
                }
            };

            session.onpaymentauthorized = async (event) => {
                try {
                    await applepay.confirmOrder({
                        orderId: orderResult.id,
                        token: event.payment.token,
                        billingContact: event.payment.billingContact,
                        shippingContact: event.payment.shippingContact,
                    });
                    const captured = await captureServerOrder(orderResult.id, csrfToken);
                    if (captured.status === 'COMPLETED') {
                        session.completePayment({ status: window.ApplePaySession.STATUS_SUCCESS });
                        onSuccess && onSuccess(captured);
                    } else {
                        session.completePayment({ status: window.ApplePaySession.STATUS_FAILURE });
                        onError && onError(new Error('capture not completed'));
                    }
                } catch (e) {
                    console.error('Apple Pay confirm/capture failed', e);
                    session.completePayment({ status: window.ApplePaySession.STATUS_FAILURE });
                    onError && onError(e);
                }
            };

            session.oncancel = () => {
                hideOverlay();
            };

            showOverlay();
            session.begin();
        } catch (e) {
            hideOverlay();
            console.error('Apple Pay click failed', e);
            onError && onError(e);
        }
    });
    container.appendChild(btn);
    return true;
}

async function setupGooglePay({ paypal, amount, context, onSuccess, onError, container }) {
    if (!container) {
        return false;
    }
    try {
        await loadScript(GOOGLE_PAY_JS);
    } catch (e) {
        console.warn('Google Pay JS load failed', e);
        return false;
    }
    if (!window.google || !window.google.payments || !window.google.payments.api) {
        return false;
    }
    const googlepay = paypal.Googlepay();
    let config;
    try {
        config = await googlepay.config();
    } catch (e) {
        console.warn('Google Pay config error', e);
        return false;
    }
    if (!config || !config.isEligible) {
        return false;
    }
    const csrfToken = getCsrfToken();
    const paymentsClient = new window.google.payments.api.PaymentsClient({
        environment: config.environment || 'TEST',
    });
    let isReady;
    try {
        isReady = await paymentsClient.isReadyToPay({
            apiVersion: config.apiVersion || 2,
            apiVersionMinor: config.apiVersionMinor || 0,
            allowedPaymentMethods: config.allowedPaymentMethods,
        });
    } catch (e) {
        console.warn('Google Pay isReadyToPay error', e);
        return false;
    }
    if (!isReady || !isReady.result) {
        return false;
    }

    const button = paymentsClient.createButton({
        buttonColor: 'black',
        buttonType: 'pay',
        buttonRadius: 10,
        buttonSizeMode: 'fill',
        onClick: async () => {
            try {
                const orderResult = await createServerOrder({ amount, context, csrfToken });
                if (!orderResult.id) {
                    throw new Error('no order id');
                }
                const paymentDataRequest = {
                    apiVersion: config.apiVersion || 2,
                    apiVersionMinor: config.apiVersionMinor || 0,
                    allowedPaymentMethods: config.allowedPaymentMethods,
                    merchantInfo: config.merchantInfo,
                    transactionInfo: {
                        countryCode: config.countryCode || 'ES',
                        currencyCode: 'EUR',
                        totalPriceStatus: 'FINAL',
                        totalPrice: String(amount),
                    },
                };
                showOverlay();
                const paymentData = await paymentsClient.loadPaymentData(paymentDataRequest);
                await googlepay.confirmOrder({
                    orderId: orderResult.id,
                    paymentMethodData: paymentData.paymentMethodData,
                });
                const captured = await captureServerOrder(orderResult.id, csrfToken);
                hideOverlay();
                if (captured.status === 'COMPLETED') {
                    onSuccess && onSuccess(captured);
                } else {
                    onError && onError(new Error('capture not completed'));
                }
            } catch (e) {
                hideOverlay();
                if (e && e.statusCode === 'CANCELED') {
                    return;
                }
                console.error('Google Pay flow failed', e);
                onError && onError(e);
            }
        },
    });
    container.innerHTML = '';
    container.appendChild(button);
    container.style.minHeight = '48px';
    return true;
}

function setupPayPalButtons({ paypal, amount, context, onSuccess, onError, container }) {
    if (!container) {
        return false;
    }
    const csrfToken = getCsrfToken();
    paypal
        .Buttons({
            style: { layout: 'vertical', label: 'paypal', shape: 'rect' },
            onClick: (data) => {
                if (data.fundingSource !== 'card') {
                    showOverlay();
                }
            },
            createOrder: async () => {
                const result = await createServerOrder({ amount, context, csrfToken });
                if (!result.id) {
                    throw new Error('no order id');
                }
                return result.id;
            },
            onApprove: async (data) => {
                try {
                    const captured = await captureServerOrder(data.orderID, csrfToken);
                    hideOverlay();
                    if (captured.status === 'COMPLETED') {
                        onSuccess && onSuccess(captured);
                    } else {
                        onError && onError(new Error('capture not completed'));
                    }
                } catch (e) {
                    hideOverlay();
                    onError && onError(e);
                }
            },
            onCancel: () => hideOverlay(),
            onError: (e) => {
                hideOverlay();
                onError && onError(e);
            },
        })
        .render(container);
    return true;
}

/**
 * Initialise checkout buttons for a given page.
 *
 * @param {Object} opts
 * @param {string} opts.clientId          PayPal client id
 * @param {number|string} opts.amount     Gross amount in EUR (e.g. 12.50)
 * @param {string} opts.context           'pay' | 'checkout'
 * @param {string} opts.onApproveUrl      URL to navigate to on success
 * @param {HTMLElement} [opts.applePayContainer]
 * @param {HTMLElement} [opts.googlePayContainer]
 * @param {HTMLElement} [opts.paypalContainer]
 * @param {HTMLElement} [opts.fallbackHint] Hide once any wallet is available
 */
export async function initPayPalCheckout(opts) {
    if (!opts || !opts.clientId || !opts.amount) {
        return;
    }
    const onSuccess = () => {
        if (opts.onApproveUrl) {
            window.location.href = opts.onApproveUrl;
        }
    };
    const onError = (err) => {
        console.error('Payment error', err);
        if (err && err.status === 409) {
            alert('Tu pedido ha cambiado desde que empezaste a pagar. Revisa el importe y vuelve a intentarlo.');
            window.location.reload();
            return;
        }
        alert('No se pudo completar el pago. Intenta otro método.');
    };

    let paypal;
    try {
        paypal = await loadPayPalSdk(String(opts.clientId));
    } catch (e) {
        console.error('PayPal SDK failed to load', e);
        return;
    }
    if (!paypal) {
        return;
    }

    const ctx = opts.context || 'pay';
    const amount = Number(opts.amount).toFixed(2);

    const [applePayOk, googlePayOk] = await Promise.all([
        setupApplePay({
            paypal,
            amount,
            context: ctx,
            container: opts.applePayContainer,
            onSuccess,
            onError,
        }),
        setupGooglePay({
            paypal,
            amount,
            context: ctx,
            container: opts.googlePayContainer,
            onSuccess,
            onError,
        }),
    ]);

    setupPayPalButtons({
        paypal,
        amount,
        context: ctx,
        container: opts.paypalContainer,
        onSuccess,
        onError,
    });

    if (opts.fallbackHint && (applePayOk || googlePayOk)) {
        opts.fallbackHint.style.display = '';
    }
    return { applePayOk, googlePayOk };
}

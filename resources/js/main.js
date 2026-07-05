/**
 * Tailwind bundle: admin, shop (order), auth layouts.
 */
import '../css/main.css';

import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();

import './shared/jquery-csrf.js';
import './shared/alerts.js';
import './shared/waiter-notify.js';
import axios from 'axios';
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import './shop/order.js';
import './shop/product-search.js';
import './shop/flash-offers.js';
import './shop/order-ready.js';
import './shop/basket-page.js';
import './shop/checkout-page.js';
import './shop/pay-page.js';
import './shop/final-page.js';

import './admin/products-index.js';
import './admin/categories.js';
import './admin/users.js';
import './admin/product-edit.js';
import './admin/stock.js';
import './admin/receipt-index.js';
import './admin/basket.js';
import './admin/timereport.js';
import './admin/offer-edit.js';
import './admin/flashoffers.js';
import './admin/tables.js';
import './admin/kitchen.js';
import './admin/orders-log.js';

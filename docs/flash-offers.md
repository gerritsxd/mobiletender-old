# Flash Offers (Ofertas Flash)

Time-limited offers that managers launch in seconds and that reach every customer
who is actively ordering (scanned a QR / has a shop page open), without any app install.

## How it works

- **Admin** (`/flashoffers`, role `manager`+): quick-launch form — title, optional message,
  optional product, optional flash price (VAT included), duration preset (15 min – 4 h).
  Live offers show a countdown and can be stopped, extended (+15 min) or relaunched.
- **Customers**: every shop page polls `GET /flash-offers/poll` every 20 s
  (`resources/js/shop/flash-offers.js`). New live offers slide in as a banner with a
  countdown and — when a product is attached — a one-tap **"¡Lo quiero!"** button.
  The phone vibrates on arrival; if the tab is hidden and the browser granted
  notification permission, a system notification is shown too (permission is requested
  on the customer's first add-to-cart tap, never on page load).
- **Pricing is server-side**: `POST /order/addflashoffer/{id}` re-validates that the
  offer is still live and adds the product to the session ticket at the flash price
  (net = flash price / 1.1). The client never sends a price.
- Dismissed banners stay dismissed for the browser session (`sessionStorage`).
- The poll response is cached for 10 s (`flash_offers_live` cache key), so even a full
  venue polling every 20 s produces negligible DB load.

## Files

| File | Purpose |
|------|---------|
| `app/Models/FlashOffer.php` | Model, `live()` scope |
| `app/Http/Controllers/FlashOfferController.php` | Admin CRUD + `poll` + `addToOrder` |
| `database/migrations/2026_07_02_100000_create_flash_offers_table.php` | `flash_offers` table |
| `resources/views/admin/flashoffers/index.blade.php` | Admin UI |
| `resources/js/shop/flash-offers.js` | Customer banner + polling + notifications |
| `resources/js/admin/flashoffers.js` | Admin live countdowns |

Routes live in `routes/web.php` (`flashoffers.*` names); admin routes are behind
`is_manager`, the poll endpoint is throttled (60/min per IP).

## Notes / future upgrades

- True Web Push (offers arriving with the page closed) needs a push subscription per
  device + VAPID keys (e.g. `laravel-notification-channels/webpush`). Note iOS Safari
  only delivers Web Push to PWAs added to the home screen, which QR walk-up customers
  won't have — the in-page banner is the reliable channel for this use case.
- Polling could be swapped for SSE/websockets later; the JS entry point is isolated in
  `refreshOffers()`.

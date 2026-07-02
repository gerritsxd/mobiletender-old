# Playa Alta POS (Mobile Tender)

Laravel application for restaurant ordering and POS workflows, integrated with a **uniCenta oPOS**–style MariaDB schema (shared tickets, receipts, thermal printers).

## Requirements

- PHP 8.1+ (see `composer.json`; Sail uses PHP **8.3** — see `docker-compose.yml`)
- Composer 2.x
- Node.js 18+ (for **Vite** frontend build)
- MariaDB/MySQL
- Optional: Redis (included in `docker-compose` for queue/cache)
- Optional: Node scripts `nodeprinterbridge.js` / `nodeprinterproxy.js` for ESC/POS printer bridging (Dockerfile: `infra/docker/printer-bridge/Dockerfile`)

## Local development (Sail)

```bash
cp .env.example .env
# Set APP_KEY, DB_*, PRINTER_*, PAYPAL_* as needed
php artisan key:generate

docker compose up -d
docker compose exec laravel.test composer install
docker compose exec laravel.test php artisan migrate
npm ci && npm run build   # Vite → public/build
```

**Database host inside Docker:** set `DB_HOST=mariadb` in `.env` (not `127.0.0.1`).

**Vite dev server:** `npm run dev` (with `laravel.test` running, use the forwarded Vite port).

**Stack:** Laravel **9.x** (upgrade path to 10/11 documented in the modernization plan). Assets built with **Vite** + `vite-plugin-pwa` (replaces Laravel Mix / Workbox).

### Frontend bundle

- **`resources/js/main.js` + `resources/css/main.css`** — **Tailwind CSS 3** + Alpine + jQuery (used by the AJAX helpers in `resources/js/shop/*` and `resources/js/admin/*`). One bundle covers the **admin** shell (`layouts.admin`), **shop / order** flow (`layouts.shop`), and **auth** (`layouts.auth`). The app is mobile-first and Bootstrap-free.

Run `npm run build` to compile into `public/build/`.

## Importing a production database dump

Dumps live under `../dump/` (sibling to this app folder). Before importing:

1. **Cross-database FKs:** production dumps may reference `` `pos3`.`attributeset` ``. Strip the catalog so FKs point at the same database:

   ```bash
   sed -i.bak 's/REFERENCES `pos3`\.`/REFERENCES `/g' /path/to/dump.sql
   ```

2. **Row size (InnoDB):** if you see `ERROR 1118 Row size too large`, convert `ROW_FORMAT=COMPACT` to `ROW_FORMAT=DYNAMIC`:

   ```bash
   sed -i.bak2 's/ROW_FORMAT=COMPACT/ROW_FORMAT=DYNAMIC/g' /path/to/dump.sql
   ```

3. Import:

   ```bash
   mariadb -h 127.0.0.1 -P 3306 -u USER -p DBNAME < /path/to/dump.sql
   ```

Or use the helper script: [`bin/import-dump.sh`](bin/import-dump.sh).

## Printer bridges (Node)

- `nodeprinterbridge.js` — TCP bridge (printers on 9100, app on 9101).
- `nodeprinterproxy.js` — proxy on 9100.

These are **not** started by Sail by default. In production, `nodeprinterbridge.js` is a Deployer [shared file](deploy.php). Run manually: `node nodeprinterbridge.js`.

Configure `PRINTER_IP` and `PRINTER_PORT` in `.env`.

## Deployment

[Deployer](https://deployer.org/) recipe in [`deploy.php`](deploy.php). Hosts include `staging`, `demo`, `bar`, `playaalta` (comer), `copas`, `latertulia`, `tertulia`, `horecalo`. Migrations run before `deploy:symlink`.

## Code quality

```bash
composer analyse   # PHPStan (Larastan)
composer lint        # PHP-CS-Fixer dry-run
composer fix         # PHP-CS-Fixer fix
composer test        # PHPUnit
```

## Project layout

| Path | Purpose |
|------|---------|
| `app/Http/Controllers` | Web controllers (order, admin, auth) |
| `app/Models/UnicentaModels` | Eloquent models for POS tables |
| `app/Traits` | Shared ticket, printing, payment logic (heavy raw SQL) |
| `routes/web.php` | Main HTTP routes |
| `config/customoptions.php` | Feature toggles (eat-in, takeaway, prepay, etc.) |
| `config/paypal.php` | PayPal client settings |

## Flash offers

Managers can launch time-limited offers from `/flashoffers` that appear live (with a
countdown and one-tap add-to-order) on the phone of every customer who is currently
ordering. See [`docs/flash-offers.md`](docs/flash-offers.md).

## License

MIT (Laravel skeleton).

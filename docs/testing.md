# Running the PHPUnit suite

The app runs on top of a production uniCenta oPOS MariaDB schema that is not
in the repo, so the tests need a database with those tables before they can
run at all. This harness provides one with Docker only — no local PHP needed.

## TL;DR

```bash
bin/test-db.sh                 # spin up DB, load schema, migrate, run phpunit, tear down
KEEP_DB=1 bin/test-db.sh       # same, but leave the mt-test-db container running
bin/test-db.sh down            # remove a leftover mt-test-db container
bin/test-db.sh tests/Unit/ModelsTest.php   # extra args are passed to phpunit
```

## What the harness does

1. Creates the `mt-test` Docker network (idempotent) and starts a throwaway
   `mariadb:10` container named `mt-test-db`
   (root password `secret`, database `testing`).
2. Builds a local `mt-test-php` image on first use: `php:8.4-cli` plus
   `pdo_mysql` (the stock image lacks the MySQL PDO driver — this is the
   "could not find driver" error you get otherwise).
3. Generates a throwaway `APP_KEY` with `php artisan key:generate --show`.
4. Loads `database/testing/unicenta-test-schema.sql` into the DB.
   **This must happen before the migrations**, because the migration
   `2020_05_07_110247_add_description_toproduct_table.php` ALTERs the
   uniCenta `products` table.
5. Runs `php artisan migrate --force` (users, offers, flash_offers,
   product_details, ... — the app-owned tables).
6. Runs `php vendor/bin/phpunit` (Unit + Feature suites) with
   `memory_limit=512M`, then removes the DB container (unless `KEEP_DB=1`).

## The test schema (`database/testing/unicenta-test-schema.sql`)

Minimal-but-column-complete CREATE TABLEs for every uniCenta table the code
touches: `sharedtickets`, `closedcash`, `receipts`, `tickets`, `ticketlines`,
`payments`, `taxlines`, `stockcurrent`, `stockdiary`, `ticketsnum`,
`lineremoved`, `pickup_number`, `places`, `categories`, `products`,
`products_cat`. Types are permissive (VARCHAR(255) ids, DOUBLE money,
MEDIUMTEXT content).

Two details are load-bearing:

- **Column names are lowercase.** PDO returns `SELECT *` / Eloquent attribute
  keys in the table-defined case, and the code reads lowercase properties
  everywhere (`$product->pricesell`, `$openticket->id`, `->content`, ...).
  An uppercase schema makes every attribute silently `null`.
- **Positional INSERTs fix the column count/order.**
  `sharedtickets` must have exactly 6 columns `(id, name, content, locked,
  hidden, appuser)` for `INSERT into sharedtickets VALUES (?, ?, ?, 0, 0, null)`,
  and `lineremoved` exactly `(removeddate, name, ticketid, productid,
  productname, units)` for the 6-placeholder insert in
  `SharedTicketTrait::addLineRemoved()`.

Seed rows required at boot / by the tests:

- one **open `closedcash` row** (`dateend IS NULL`) —
  `SharedTicketTrait::createEmptyTicket()` indexes `[0]` on it,
- `ticketsnum` = 0 and `pickup_number` = 100 (LAST_INSERT_ID counters),
- `places` ids 1..20,
- 2 `categories` (the first id is hardcoded in `tests/Unit/ModelsTest.php`),
- 3 `products`; the first is `tests/TestCase.php::PRODUCT_ID`
  (`037d4a31-...`). Ids are chosen so `Product::all()` (clustered PK order)
  yields a deterministic first/second/third, and prices are net values whose
  first+third sum × 1.1 rounds to 12.00 (asserted by
  `SharedTicketLineTest::testGetTicketLinesSum`).

## Why `DATABASE_URL=...?strict=false`

`config/database.php` hardcodes `'strict' => true`, but the unit tests pass
table numbers as PHP ints (`TestCase::TABLENUMBER = 111`). When the
`sharedtickets` table also contains UUID ids (the app creates them for
anonymous sessions), MariaDB must coerce every id to a number to evaluate
`WHERE id = 111`, and under `STRICT_TRANS_TABLES` the coercion warning on a
UUID row escalates to an error on UPDATE/DELETE. In production this never
happens because HTTP/session input arrives as strings. Real uniCenta
deployments run non-strict anyway, so the harness disables strict mode for
the test connection via the `DATABASE_URL` query string (Laravel merges URL
query params into the connection config) — no code changes needed.

## Suite status

As of 2026-07-02 the full suite is green: `Tests: 34, Assertions: 50,
Skipped: 2`. The two skipped tests (`PrinterTraitTest::testPrinterConnection`
and `::testPrinterCodePages`) need a physical ESC/POS network printer — set
`PRINTER_TEST=1` (plus `PRINTER_IP`/`PRINTER_PORT`) to run them.

Notable coverage:

- `tests/Feature/PaymentSecurityTest.php` — regression tests for the payment
  bypass fix: `/checkout/printOrderOnline/{id}` must reject requests without a
  server-side PayPal capture proof, and `/paypal/create-order` must reject
  when there is no active ticket to verify the amount against.
- `tests/Feature/FlashOfferTest.php` — flash offers: poll endpoint filters to
  live offers; add-to-order applies the flash price server-side; expired
  offers are rejected with 410.
- `CheckoutControllerTest::testSendOrderKeepsLinesPendingWhenPrintersUnreachable`
  — when no printer is reachable, order lines stay pending so they can be
  re-sent (they used to be flagged printed even when a printer failed).

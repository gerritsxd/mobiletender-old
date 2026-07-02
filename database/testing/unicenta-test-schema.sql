-- ---------------------------------------------------------------------------
-- Minimal-but-column-complete uniCenta oPOS test schema for the PHPUnit suite.
--
-- The production app runs on top of a real uniCenta dump that is NOT in the
-- repo. This file recreates every uniCenta table the codebase touches with
-- permissive types (VARCHAR(255) ids/strings, DOUBLE money, MEDIUMTEXT blobs)
-- plus the seed rows the code requires at boot.
--
-- Column names are deliberately LOWERCASE: PDO returns `SELECT *` (and
-- Eloquent attribute) keys in the table-defined case, and the app reads
-- lowercase properties everywhere ($product->pricesell, $openticket->id,
-- $closedcash->money, ...). Raw SQL that writes UPPERCASE names still works
-- because SQL identifiers are case-insensitive.
--
-- IMPORTANT: load this file BEFORE `php artisan migrate` — the migration
-- 2020_05_07_110247_add_description_toproduct_table.php ALTERs `products`.
--
-- Column sources (raw SQL in the codebase):
--   sharedtickets : SharedTicketTrait (positional INSERT ... VALUES (?,?,?,0,0,null) = 6 cols)
--   lineremoved   : SharedTicketTrait (positional INSERT 6 cols: date, name, ticketid,
--                   productid, productname, units) + UnicentaPayedTrait named insert
--                   (NAME, TICKETID, PRODUCTNAME, UNITS)
--   closedcash    : SharedTicketTrait / UnicentaPayedTrait (money, host, hostsequence,
--                   datestart, dateend, nosales)
--   receipts, tickets, ticketlines, payments, taxlines, stockcurrent, stockdiary,
--   ticketsnum  : UnicentaPayedTrait / AdminReceiptController / AdminStatsController /
--                 AdminStockController
--   places        : SharedTicketTrait::updateOpenTable + Admin panels (id, name,
--                   waiter, ticketid, occupied)
--   pickup_number : BasketController::setPickUpId
--   products, categories, products_cat : Eloquent models in app/Models/UnicentaModels
--                   + ProductController / OrderController / AdminStatsController
-- ---------------------------------------------------------------------------

SET NAMES utf8mb4;

-- ------------------------------------------------------------------ tickets

DROP TABLE IF EXISTS sharedtickets;
CREATE TABLE sharedtickets (
    id      VARCHAR(255) NOT NULL,
    name    VARCHAR(255) NULL,
    content MEDIUMTEXT   NULL,
    locked  INT          NULL DEFAULT 0,
    hidden  INT          NULL DEFAULT 0,
    appuser VARCHAR(255) NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS closedcash;
CREATE TABLE closedcash (
    money        VARCHAR(255) NOT NULL,
    host         VARCHAR(255) NULL,
    hostsequence INT          NULL,
    datestart    DATETIME     NULL,
    dateend      DATETIME     NULL,
    nosales      DOUBLE       NULL,
    PRIMARY KEY (money)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS receipts;
CREATE TABLE receipts (
    id         VARCHAR(255) NOT NULL,
    money      VARCHAR(255) NULL,
    datenew    DATETIME     NULL,
    attributes MEDIUMTEXT   NULL,
    person     VARCHAR(255) NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS tickets;
CREATE TABLE tickets (
    id         VARCHAR(255) NOT NULL,
    tickettype INT          NULL,
    ticketid   INT          NULL,
    person     VARCHAR(255) NULL,
    customer   VARCHAR(255) NULL,
    status     INT          NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS ticketlines;
CREATE TABLE ticketlines (
    ticket                   VARCHAR(255) NOT NULL,
    line                     INT          NOT NULL,
    product                  VARCHAR(255) NULL,
    attributesetinstance_id  VARCHAR(255) NULL,
    units                    DOUBLE       NULL,
    price                    DOUBLE       NULL,
    taxid                    VARCHAR(255) NULL,
    attributes               MEDIUMTEXT   NULL,
    PRIMARY KEY (ticket, line)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS payments;
CREATE TABLE payments (
    id        VARCHAR(255) NOT NULL,
    receipt   VARCHAR(255) NULL,
    payment   VARCHAR(255) NULL,
    total     DOUBLE       NULL,
    transid   VARCHAR(255) NULL,
    returnmsg MEDIUMTEXT   NULL,
    tendered  DOUBLE       NULL,
    cardname  VARCHAR(255) NULL,
    voucher   VARCHAR(255) NULL,
    notes     VARCHAR(255) NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS taxlines;
CREATE TABLE taxlines (
    id      VARCHAR(255) NOT NULL,
    receipt VARCHAR(255) NULL,
    taxid   VARCHAR(255) NULL,
    base    DOUBLE       NULL,
    amount  DOUBLE       NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------------- stock

DROP TABLE IF EXISTS stockcurrent;
CREATE TABLE stockcurrent (
    location                VARCHAR(255) NOT NULL,
    product                 VARCHAR(255) NOT NULL,
    attributesetinstance_id VARCHAR(255) NULL,
    units                   DOUBLE       NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS stockdiary;
CREATE TABLE stockdiary (
    id                      VARCHAR(255) NOT NULL,
    datenew                 DATETIME     NULL,
    reason                  INT          NULL,
    location                VARCHAR(255) NULL,
    product                 VARCHAR(255) NULL,
    attributesetinstance_id VARCHAR(255) NULL,
    units                   DOUBLE       NULL,
    price                   DOUBLE       NULL,
    appuser                 VARCHAR(255) NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------- counters

DROP TABLE IF EXISTS ticketsnum;
CREATE TABLE ticketsnum (
    id INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS pickup_number;
CREATE TABLE pickup_number (
    id INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------------- misc

-- Positional insert order in SharedTicketTrait::addLineRemoved:
--   (now, user, ticketid, productid, productname, units)
DROP TABLE IF EXISTS lineremoved;
CREATE TABLE lineremoved (
    removeddate DATETIME     NULL DEFAULT CURRENT_TIMESTAMP,
    name        VARCHAR(255) NULL,
    ticketid    VARCHAR(255) NULL,
    productid   VARCHAR(255) NULL,
    productname VARCHAR(255) NULL,
    units       DOUBLE       NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS places;
CREATE TABLE places (
    id         VARCHAR(255) NOT NULL,
    name       VARCHAR(255) NULL,
    x          INT          NULL DEFAULT 0,
    y          INT          NULL DEFAULT 0,
    floor      VARCHAR(255) NULL,
    customer   VARCHAR(255) NULL,
    waiter     VARCHAR(255) NULL,
    ticketid   VARCHAR(255) NULL,
    tablemoved SMALLINT     NULL DEFAULT 0,
    occupied   DATETIME     NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------- catalog

DROP TABLE IF EXISTS categories;
CREATE TABLE categories (
    id          VARCHAR(255) NOT NULL,
    name        VARCHAR(255) NULL,
    parentid    VARCHAR(255) NULL,
    image       LONGBLOB     NULL,
    catshowname INT          NULL DEFAULT 1,
    catorder    VARCHAR(255) NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- NOTE: `description` is intentionally absent; the Laravel migration
-- 2020_05_07_110247_add_description_toproduct_table.php adds it.
DROP TABLE IF EXISTS products;
CREATE TABLE products (
    id              VARCHAR(255) NOT NULL,
    reference       VARCHAR(255) NULL,
    code            VARCHAR(255) NULL,
    codetype        VARCHAR(255) NULL,
    name            VARCHAR(255) NULL,
    pricebuy        DOUBLE       NULL,
    pricesell       DOUBLE       NULL,
    category        VARCHAR(255) NULL,
    taxcat          VARCHAR(255) NULL,
    attributeset_id VARCHAR(255) NULL,
    stockcost       DOUBLE       NULL,
    stockvolume     DOUBLE       NULL,
    stockunits      DOUBLE       NULL,
    image           LONGBLOB     NULL,
    iscom           TINYINT      NULL DEFAULT 0,
    isscale         TINYINT      NULL DEFAULT 0,
    isservice       TINYINT      NULL DEFAULT 0,
    printkb         TINYINT      NULL DEFAULT 0,
    sendstatus      TINYINT      NULL DEFAULT 0,
    printto         VARCHAR(255) NULL,
    supplier        VARCHAR(255) NULL,
    uom             VARCHAR(255) NULL,
    memodate        DATETIME     NULL,
    attributes      MEDIUMTEXT   NULL,
    display         VARCHAR(255) NULL,
    detail          MEDIUMTEXT   NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS products_cat;
CREATE TABLE products_cat (
    product  VARCHAR(255) NOT NULL,
    catorder INT          NULL,
    PRIMARY KEY (product)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===========================================================================
-- Seed data required at boot
-- ===========================================================================

-- SharedTicketTrait::createEmptyTicket() does
--   SELECT money FROM closedcash WHERE dateend IS NULL   and indexes [0]
INSERT INTO closedcash (money, host, hostsequence, datestart, dateend)
VALUES ('5f7c9a4e-0000-4000-8000-testclosedca', 'TEST', 1, NOW(), NULL);

-- UnicentaPayedTrait: UPDATE ticketsnum SET ID = LAST_INSERT_ID(ID + 1)
INSERT INTO ticketsnum (id) VALUES (0);

-- BasketController::setPickUpId: UPDATE pickup_number SET id = LAST_INSERT_ID(id + 1)
INSERT INTO pickup_number (id) VALUES (100);

-- Tables 1..20 for paypanel / place selection views
INSERT INTO places (id, name, floor) VALUES
('1',  'Mesa 1',  '1'), ('2',  'Mesa 2',  '1'), ('3',  'Mesa 3',  '1'),
('4',  'Mesa 4',  '1'), ('5',  'Mesa 5',  '1'), ('6',  'Mesa 6',  '1'),
('7',  'Mesa 7',  '1'), ('8',  'Mesa 8',  '1'), ('9',  'Mesa 9',  '1'),
('10', 'Mesa 10', '1'), ('11', 'Mesa 11', '1'), ('12', 'Mesa 12', '1'),
('13', 'Mesa 13', '1'), ('14', 'Mesa 14', '1'), ('15', 'Mesa 15', '1'),
('16', 'Mesa 16', '1'), ('17', 'Mesa 17', '1'), ('18', 'Mesa 18', '1'),
('19', 'Mesa 19', '1'), ('20', 'Mesa 20', '1');

-- Two categories. The first id is referenced by tests/Unit/ModelsTest.php.
INSERT INTO categories (id, name, parentid, catshowname, catorder) VALUES
('0484675e-1baf-415a-b1a0-897fbb1fa14c', 'Bebidas', NULL, 1, '1'),
('9b2d1f60-2222-4222-8222-testcategory2', 'Comidas', NULL, 1, '2');

-- Three products. Product::all() returns rows in PRIMARY KEY (id string) order,
-- so ids are chosen to make first/second/third deterministic:
--   first  = 037d4a31-... (tests/TestCase.php PRODUCT_ID)
--   third  = 2ccccccc-...
-- tests/Unit/SharedTicketLineTest::testGetTicketLinesSum asserts
--   round((pricesell(first) + pricesell(third)) * 1.1, 2) == 12
-- so both carry a net price of 6.00/1.1.
INSERT INTO products
    (id, reference, code, name, pricebuy, pricesell, category, taxcat, stockunits, printto)
VALUES
('037d4a31-a464-403c-a2df-450773087096', 'cana',   'cana',   'Cana',
 1.0, 5.454545454545454,  '0484675e-1baf-415a-b1a0-897fbb1fa14c', '001', 1, '1'),
('1bbbbbbb-b464-403c-a2df-450773087096', 'agua',   'agua',   'Agua',
 0.5, 3.6363636363636362, '0484675e-1baf-415a-b1a0-897fbb1fa14c', '001', 1, '1'),
('2ccccccc-c464-403c-a2df-450773087096', 'bocado', 'bocado', 'Bocadillo',
 2.0, 5.454545454545454,  '9b2d1f60-2222-4222-8222-testcategory2', '001', 1, '2');

INSERT INTO products_cat (product, catorder) VALUES
('037d4a31-a464-403c-a2df-450773087096', 1),
('1bbbbbbb-b464-403c-a2df-450773087096', 2),
('2ccccccc-c464-403c-a2df-450773087096', 3);

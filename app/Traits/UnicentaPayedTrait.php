<?php

/**
 * Created by PhpStorm.
 * User: Gerrit
 * Date: 08/11/2020
 * Time: 15:06
 */

namespace App\Traits;

use App\Models\UnicentaModels\Product;
use App\Models\UnicentaModels\SharedTicketLines;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use function is_null;


trait UnicentaPayedTrait
{
    use SharedTicketTrait;

    public function setTicketPayed($tableNumber, $paymentType = 'cash', $linestoPrint = null)
    {
        /*
         *  UPDATE ticketsnum SET ID = LAST_INSERT_ID(ID + 1)
         *
         */


        $ticket = $this->getTicket($tableNumber);

        if (is_null($linestoPrint)) {
            $ticketLines = $this->getTicketLines($tableNumber);
        } else {
            $ticketLines = $linestoPrint;
        }

        // Guards against double-submit/refresh producing empty 0€ receipts.
        if (empty($ticketLines)) {
            return null;
        }

        return DB::transaction(function () use ($ticket, $ticketLines, $tableNumber, $paymentType, $linestoPrint) {
            return $this->writeTicketPayed($ticket, $ticketLines, $tableNumber, $paymentType, $linestoPrint);
        });
    }

    private function writeTicketPayed($ticket, $ticketLines, $tableNumber, $paymentType, $linestoPrint)
    {
        DB::update("UPDATE ticketsnum SET ID = LAST_INSERT_ID(ID + 1)");


        //$ticketid = DB::select("SELECT LAST_INSERT_ID()");
        $ticketid = DB::getPdo()->lastInsertId();

        /*
         *
         *
         *
         * INSERT INTO receipts (ID, MONEY, DATENEW, ATTRIBUTES, PERSON) VALUES ('fa06f234-d749-4801-a122-75fe6e006689', 'bfd6b036-6250-4e64-b7eb-bb028bcef5f1', '2020-11-08 14:56:27.808', _binary'<?xml version=\"1.0\" encoding=\"UTF-8\"?>\r\n<!DOCTYPE properties SYSTEM \"http://java.sun.com/dtd/properties.dtd\">\r\n<properties>\r\n<comment>uniCenta oPOS</comment>\r\n</properties>\r\n', null)
         */
        $id = $ticket->m_sId;
        $money = DB::select('SELECT money FROM closedcash where dateend is null')[0]->money;
        $datenew = Carbon::now()->format('Y-m-d H:i:s');
        $person = auth()->user()->name ?? "Guest";
        DB::insert(
            'INSERT INTO receipts (ID, MONEY, DATENEW, ATTRIBUTES, PERSON) VALUES (?, ?, ?, null, ?)',
            [$id, $money, $datenew, $person]
        );
        /*
         *
         * $insertSQL = "INSERT INTO receipts (ID, MONEY, DATENEW, ATTRIBUTES, PERSON) VALUES ('fa06f234-d749-4801-a122-75fe6e006689', 'bfd6b036-6250-4e64-b7eb-bb028bcef5f1', '2020-11-08 14:56:27.808', _binary'<?xml version=\"1.0\" encoding=\"UTF-8\"?>\r\n<!DOCTYPE properties SYSTEM \"http://java.sun.com/dtd/properties.dtd\">\r\n<properties>\r\n<comment>uniCenta oPOS</comment>\r\n</properties>\r\n', null)";
         * INSERT INTO tickets (ID, TICKETTYPE, TICKETID, PERSON, CUSTOMER, STATUS) VALUES ('fa06f234-d749-4801-a122-75fe6e006689', 0, 8, '0', null, 0)
         */
        DB::insert(
            'INSERT INTO tickets (ID, TICKETTYPE, TICKETID, PERSON, CUSTOMER, STATUS) VALUES (?, 0, ?, 0, null, 0)',
            [$id, $ticketid]
        );
        $ticketNr = DB::select('SELECT ticketid FROM tickets WHERE ID = ?', [$id])[0]->ticketid;
        /*
         * UPDATE tickets SET STATUS = 8 WHERE TICKETTYPE = 0 AND TICKETID = 0
         *
         *
         * INTO ticketlines (TICKET, LINE, PRODUCT, ATTRIBUTESETINSTANCE_ID, UNITS, PRICE, TAXID, ATTRIBUTES) VALUES ('fa06f234-d749-4801-a122-75fe6e006689', 0, '0d5a6cdd-3a5e-4365-9975-0eb173108198', null, 1.0, 2.2727272727272725, '001', _binary'<?xml version=\"1.0\" encoding=\"UTF-8\"?>\r\n<!DOCTYPE properties SYSTEM \"http://java.sun.com/dtd/properties.dtd\">\r\n<properties>\r\n<comment>uniCenta oPOS</comment>\r\n<entry key=\"product.taxcategoryid\">001</entry>\r\n<entry key=\"product.warranty\">false</entry>\r\n<entry key=\"product.memodate\">2018-01-01 00:00:01.0</entry>\r\n<entry key=\"product.verpatrib\">false</entry>\r\n<entry key=\"product.reference\">aguagas</entry>\r\n<entry key=\"product.name\">Agua Gas</entry>\r\n<entry key=\"product.service\">false</entry>\r\n<entry key=\"product.com\">false</entry>\r\n<entry key=\"product.code\">aguagas</entry>\r\n<entry key=\"product.constant\">false</entry>\r\n<entry key=\"ticket.updated\">true</entry>\r\n<entry key=\"product.printer\">1</entry>\r\n<entry key=\"product.categoryid\">4fabf8cc-c05c-492c-91cb-f0b751d41cee</entry>\r\n<entry key=\"product.vprice\">false</entry>\r\n</properties>\r\n')
         */
        /*
         * SELECT ID, PRODUCT, PRODUCT_BUNDLE, QUANTITY FROM products_bundle WHERE PRODUCT = '0d5a6cdd-3a5e-4365-9975-0eb173108198'
         *
         * UPDATE stockcurrent SET UNITS = (UNITS + -1.0) WHERE LOCATION = '0' AND PRODUCT = '0d5a6cdd-3a5e-4365-9975-0eb173108198' AND ATTRIBUTESETINSTANCE_ID IS NULL
         */


        /*
        *  INSERT INTO stockcurrent (LOCATION, PRODUCT, ATTRIBUTESETINSTANCE_ID, UNITS) VALUES ('0', '0d5a6cdd-3a5e-4365-9975-0eb173108198', null, -1.0)
        *
        * INSERT INTO stockdiary (ID, DATENEW, REASON, LOCATION, PRODUCT, ATTRIBUTESETINSTANCE_ID, UNITS, PRICE, AppUser) VALUES ('98c99c73-e90b-4549-bf11-04cfbb1c291f', '2020-11-08 14:56:27.808', -1, '0', '0d5a6cdd-3a5e-4365-9975-0eb173108198', null, -1.0, 2.2727272727272725, 'Administrator')
        */

        $linenumber = 0;
        foreach ($ticketLines as $ticketLine) {
            //$select = DB::select("SELECT stockunits from products where id = '$ticketLine->productid'");
            $productId = $ticketLine->productid;
            $linePrice = (float) $ticketLine->price;
            DB::insert(
                'INSERT INTO ticketlines (TICKET, LINE, PRODUCT, ATTRIBUTESETINSTANCE_ID, UNITS, PRICE, TAXID, ATTRIBUTES)
        VALUES (?, ?, ?, null, 1.0 , ?, \'001\',null)',
                [$id, $linenumber, $productId, $linePrice]
            );

            $foundStock = DB::select(
                'SELECT * FROM stockcurrent WHERE PRODUCT = ?',
                [$productId]
            );

            $productModel = Product::find($productId);
            $stockUnit = $productModel ? ($productModel->stockunits ?? 1) : 1;

            if (count($foundStock) > 0) {
                $stockUnit = $stockUnit > 0 ? $stockUnit : 1;
                DB::update(
                    'UPDATE stockcurrent SET UNITS = (UNITS + ?) WHERE LOCATION = \'0\' AND PRODUCT = ? AND ATTRIBUTESETINSTANCE_ID IS NULL',
                    [-1 * $stockUnit, $productId]
                );
            } else {
                DB::insert(
                    'INSERT INTO stockcurrent (LOCATION, PRODUCT, ATTRIBUTESETINSTANCE_ID, UNITS) VALUES (\'0\', ?, null, ?)',
                    [$productId, -1]
                );
            }

            $stockDiaryID = Str::uuid();
            DB::insert(
                'INSERT INTO stockdiary (ID, DATENEW, REASON, LOCATION, PRODUCT, ATTRIBUTESETINSTANCE_ID, UNITS, PRICE, AppUser) VALUES (?, ?, -1, \'0\', ?, null, ?, ?, ?)',
                [$stockDiaryID, $datenew, $productId, -$stockUnit, $linePrice, $person]
            );
            $linenumber++;
        }


        /*
        * INSERT INTO payments (ID, RECEIPT, PAYMENT, TOTAL, TRANSID, RETURNMSG, TENDERED, CARDNAME, VOUCHER) VALUES ('4f9b20f2-95ea-46e0-9ad2-974fef60a596', 'fa06f234-d749-4801-a122-75fe6e006689', 'bank', 2.4999999999999996, null, _binary'Aceptar', 0.0, null, null)
        */

        // Line prices are net (ex-VAT): base = net sum, tax = 10% of net, total = gross.
        $base = $this->getSumTicketPartialLines($ticketLines);
        $tax = 0.1 * $base;
        $total = $base * 1.1;

        $paymentID = Str::uuid();
        DB::insert(
            'INSERT INTO payments (ID, RECEIPT, PAYMENT, TOTAL, TRANSID, RETURNMSG, TENDERED, CARDNAME, VOUCHER) VALUES (?, ? , ?, ?, \'no ID\', null, ?, null, null)',
            [$paymentID, $id, $paymentType, $total, $total]
        );
        /*
        * INSERT INTO taxlines (ID, RECEIPT, TAXID, BASE, AMOUNT)  VALUES ('9c5f2533-74a2-40ee-a499-0cc71a299f05', 'fa06f234-d749-4801-a122-75fe6e006689', '001', 2.2727272727272725, 0.22727272727272727)
        */
        $taxid = Str::uuid();
        DB::insert(
            'INSERT INTO taxlines (ID, RECEIPT, TAXID, BASE, AMOUNT)  VALUES (?, ?, \'001\', ?, ?)',
            [$taxid, $id, $base, $tax]
        );

        /*
        * DELETE FROM sharedtickets WHERE ID = '1'
        */

        if (is_null($linestoPrint)) {
            $this->clearOpenTableTicket($tableNumber);
        } else {

            $this->removeTicketLines($tableNumber, $ticketLines);
        }


        //$this->saveEmptyTicket($this->createEmptyTicket(),$tableNumber);
        /* INSERT INTO lineremoved (NAME, TICKETID, PRODUCTNAME, UNITS) VALUES ('Administrator', 'Void', 'Ticket Deleted', 0.0)
        * */

        return $ticketNr;
    }

    /**
     * @param iterable<int, SharedTicketLines> $lines
     */
    public function setLineRemoved(iterable $lines): void
    {
        $person = auth()->user()->name ?? 'Guest';
        foreach ($lines as $line) {
            DB::insert(
                'INSERT INTO lineremoved (NAME, TICKETID, PRODUCTNAME, UNITS) VALUES (?, ?, ?, ?)',
                [$person, $line->m_sTicket, $line->product->name, $line->multiply]
            );
        }
    }

    public function getClosedCash()
    {
        $money = $this->getActiveClosedCashID();

        $totals = DB::select(
            'SELECT payment, sum(total)as total,notes  FROM payments where receipt in (select id from receipts where money = ?) group by payment,notes order by payment',
            [$money]
        );

        return $totals;
    }

    public function closeCashDB()
    {
        $now = Carbon::now()->format('Y-m-d H:i:s');
        $closedcash = DB::select('SELECT money,hostsequence,datestart FROM closedcash where dateend is null')[0];
        $money = $closedcash->money;
        $sequence = $closedcash->hostsequence + 1;
        $startdate = $closedcash->datestart;
        $this->footer = "From: " . $startdate . "\n To:" . Carbon::now();

        DB::update('UPDATE closedcash SET DATEEND = ?, NOSALES = 0 WHERE MONEY = ?', [$now, $money]);
        $money = Str::uuid();

        DB::insert(
            'INSERT INTO closedcash(MONEY, HOST, HOSTSEQUENCE, DATESTART, DATEEND) VALUES (?, \'HORECALO\', ?, ?, NULL)',
            [$money, $sequence, $now]
        );
    }

    public function getMovementsLines()
    {
        $closedcash = DB::select('SELECT money,hostsequence,datestart FROM closedcash where dateend is null')[0];
        $money = $closedcash->money;
        $movements = DB::select(
            'SELECT * FROM payments where receipt in (select id from receipts where money=?) AND (payment = \'cashin\' OR payment = \'cashout\')',
            [$money]
        );
        return $movements;
    }

    /**
     * @return mixed
     */
    private function getActiveClosedCashID()
    {
        $money = DB::select('SELECT money FROM closedcash where dateend is null')[0]->money;
        return $money;
    }

    public function addMovementFromForm($payment, $total, $notes)
    {
        $money = $this->getActiveClosedCashID();
        $receiptsID = Str::uuid();
        $now = Carbon::now()->format('Y-m-d H:i:s');
        DB::insert('INSERT INTO receipts (ID, MONEY, DATENEW) VALUES (?, ?, ?)', [$receiptsID, $money, $now]);
        $paymentsID = Str::uuid();
        DB::insert(
            'INSERT INTO payments (ID, RECEIPT, PAYMENT, TOTAL, NOTES) VALUES (?, ?, ?, ?, ?)',
            [$paymentsID, $receiptsID, $payment, $total, $notes]
        );
    }
}

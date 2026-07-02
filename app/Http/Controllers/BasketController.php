<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\AdminPaymentController;
use App\Traits\PrinterTrait;
use App\Traits\SharedTicketTrait;
use App\Models\UnicentaModels\SharedTicket;
use App\Traits\UnicentaPayedTrait;

use function config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Mockery\Exception;
use function redirect;


class BasketController extends Controller
{
    use SharedTicketTrait;
    use PrinterTrait;
    use UnicentaPayedTrait;
// Checkout profess integrated in basket handling
//    public function checkout()
//    {
//        $sharedTicketID = Session::get('ticketID');
//        $totalBasketPrice = $this->getSumTicketLines($sharedTicketID);
//        $newLinesPrice = $this->getSumNewTicketLines($sharedTicketID);
//        $tablenames = DB::select('select id,name from places order by id');
//        return view('order.checkout', compact(['totalBasketPrice', 'newLinesPrice', 'tablenames']));
//    }

    public function confirmForTable($table_number)
    {
        $ticketID = Session::get('ticketID');
        $this->moveTable($ticketID, $table_number);
        Session::put('tableNumber', $table_number);
        Session::put('ticketID', $table_number);
        return $this->sendOrder($table_number);
    }

    public function sendOrder($ticketID)
    {

        $ticket = $this->getTicket($ticketID);
        $unprintedTicetLines = $this->getUnprintedTicetLines($ticket);
        $printedLines = [];
        $anyPrinterFailed = false;

        if($ticketID > 100){
            $header = "NrPedido: " . $ticketID;
        }else{
            $header = "MESA: " . $ticketID;
        }
        $this->footer = "Pedido por:" .$ticket->m_User->m_sName;

        for($prinernr = 1 ;$prinernr <= config('app.nr-of-printers');$prinernr++){
            $toprint = collect($unprintedTicetLines)
                ->filter(function ($line) use ($prinernr) {
                    $printTarget = data_get($line, 'attributes.product.printto');
                    if ($printTarget === null) {
                        $printTarget = data_get($line, 'attributes.product.printer');
                    }
                    if ($printTarget === null && isset($line->attributes) && is_object($line->attributes) && isset($line->attributes->{'product.printer'})) {
                        $printTarget = $line->attributes->{'product.printer'};
                    }
                    return (int) $printTarget === (int) $prinernr;
                })
                ->values()
                ->all();

            if (empty($toprint)) {
                continue;
            }
            if ($this->sendLinesToSelectedPrinter($header, $toprint, $prinernr)) {
                $printedLines = array_merge($printedLines, $toprint);
            } else {
                $anyPrinterFailed = true;
            }
        }

        if (empty($printedLines)) {
            Session::flash('error', 'No se ha podido imprimir el ticket. Por favor avisa a nuestro personal.');
            Log::warning('Order print skipped: no lines matched printer mapping or all printer jobs failed.', [
                'ticket_id' => $ticketID,
                'nr_of_printers' => config('app.nr-of-printers'),
            ]);
            return redirect()->route('basket');
        }

        // Only lines whose own printer succeeded get flagged as printed;
        // the rest stay pending so they can be re-sent when the printer is back.
        $this->setTicketLinesAsPrinted($ticket, $ticketID, $printedLines);

        if ($anyPrinterFailed) {
            Session::flash('error', 'Parte del pedido no se pudo imprimir. Por favor avisa a nuestro personal.');
            Log::error('Order partially printed: at least one printer failed.', [
                'ticket_id' => $ticketID,
                'printed_lines' => count($printedLines),
                'unprinted_lines' => count($unprintedTicetLines) - count($printedLines),
            ]);
            return redirect()->route('basket');
        }

        $this->afterPrintOrderHandling($ticketID);
        return redirect()->route('order');
    }
    /**
     * @param $ticketID
     * @param $header
     * @param $toPrintLines
     */
    private function sendLinesToSelectedPrinter($header, $toPrintLines,$printerNumber)
    {

        try {
            $this->printOrder($header, $toPrintLines,$printerNumber);
            Session::flash('status', 'Su pedido se esta preparando');
            return true;
        } catch (\Exception $e) {
            Session::flash('error', 'No se ha podido imprimir el ticket. Por favor avisa a nuestro personal.');
            Log::error("Error Printing printerbridge error msg:" . $e);
            return false;
        }

    }



    /**
     * Marks only the given lines (same object instances from $ticket->m_aLines)
     * as printed, leaving failed printers' lines pending.
     */
    private function setTicketLinesAsPrinted(SharedTicket $ticket, $ticketID, array $printedLines)
    {
        foreach ($printedLines as $printedLine) {
            $printedLine->setPrinted();
        }
        $this->updateOpenTable($ticket, $ticketID);
    }

    public function printOrderEfectivo($ticketID)
    {
        $this->footer = 'Se pide pagar con EFECTIVO';
        $this->printOrderAndReceipt($ticketID);
        $totalBasketPrice = $this->getTotalBasketValue();
        Session::flash('status', 'Su cuenta esta pedida. ');
        return view('order.final',compact('totalBasketPrice'));
    }
    public function printOrderTarjeta($ticketID)
    {
        $this->footer = 'Se pide pagar con TARJETA';
        $this->printOrderAndReceipt($ticketID);
        $totalBasketPrice = $this->getTotalBasketValue();
        Session::flash('status', 'Su cuenta esta pedida');
        return view('order.final',compact('totalBasketPrice'));
    }

    public function printOrderOnline($ticketID)
    {
        // Only a PayPal capture verified server-side may mark a ticket paid online.
        $proof = Session::get('paypal_capture_proof');
        if (!is_array($proof) || (string) ($proof['ticket'] ?? '') !== (string) $ticketID) {
            Log::warning('printOrderOnline without capture proof', [
                'ticket_id' => $ticketID,
                'session_ticket' => Session::get('ticketID'),
            ]);
            Session::flash('error', 'No se ha encontrado un pago confirmado para este pedido.');
            return redirect()->route('pay');
        }

        $currentCents = (int) round($this->getSumTicketLines($ticketID) * 1.1 * 100);
        if ($currentCents !== (int) $proof['amount_cents']) {
            Log::error('printOrderOnline: ticket total no longer matches captured amount', [
                'ticket_id' => $ticketID,
                'ticket_cents' => $currentCents,
                'captured_cents' => $proof['amount_cents'],
            ]);
            Session::flash('error', 'El pedido ha cambiado desde el pago. Por favor avisa a nuestro personal.');
            return redirect()->route('pay');
        }

        Session::forget('paypal_capture_proof');

        // Record the sale first: the money is already captured, so a printer
        // failure must never prevent the receipt from being written.
        $lines = $this->getTicketLines($ticketID);
        $this->setTicketPayed($ticketID, 'online');

        $this->footer = 'PAGADO online';
        try {
            $this->printTicket('Mesa: ' . $ticketID, $lines);
        } catch (\Throwable $e) {
            Log::error('printOrderOnline: receipt print failed after payment: ' . $e->getMessage(), [
                'ticket_id' => $ticketID,
            ]);
            Session::flash('error', 'Pago recibido, pero no se pudo imprimir el ticket. Avisa a nuestro personal.');
        }

        Session::flash('status', 'Su cuenta esta pagado');
        return redirect()->route('order');
    }

    public function printOrderPagado($ticketID){
        $this->footer = 'La cuenta esta PAGADO Online';
        $this->printOrder($ticketID);
        Session::flash('status', 'Su numero de pedido es el: '. $ticketID );
        return redirect()->route('order');
    }

    public function printOrderAndReceipt($ticketID)
    {
        $ticket = $this->getTicket($ticketID);
        $header = "Mesa: " . $ticketID;

            $this->printTicket($header, $this->getTicketLines($ticketID));



        if(config('customoptions.clean_table_after_order') OR config( 'customoptions.clean_table_after_bill')) {
            $this->updateOpenTable($this->createEmptyTicket(), Session::get('ticketID'));

        }

    }


    public function printTicketfromPayment($ticketID)
    {
        $this->printOrderAndReceipt($ticketID);
        return redirect()->route('paypanel');
    }
    public function setPickUpId()
    {
        // Connection-scoped LAST_INSERT_ID keeps concurrent customers from
        // being handed the same pickup number (and merged into one ticket).
        DB::update('UPDATE pickup_number SET id = LAST_INSERT_ID(id + 1)');
        $pickup_ID = (int) DB::getPdo()->lastInsertId();
        $this->moveTable(Session::get('ticketID'), $pickup_ID);

        Session::put('tableNumber', $pickup_ID);
        Session::put('ticketID', $pickup_ID);
        Session::put('isPickup', true);
        return redirect()->route('pay');
    }

    public function pay()
    {
        $sharedTicketID = Session::get('ticketID');
        //dd($sharedTicketID);
        $totalBasketPrice = $this->getSumTicketLines($sharedTicketID);
        $newLinesPrice = $this->getSumNewTicketLines($sharedTicketID);
        $tablenames = DB::select('select id,name from places order by id');
        return view('order.pay', compact(['totalBasketPrice', 'newLinesPrice', 'tablenames']));
    }

    public function payed()
    {
        $sharedTicketID = Session::get('ticketID');
        return redirect()->route('deleteTable',$sharedTicketID);
    }



    /**
     * @param $ticketID
     */
    private function afterPrintOrderHandling($ticketID): void
    {
        if (config('customoptions.clean_table_after_order') or $ticketID > 100) {
            $this->updateOpenTable($this->createEmptyTicket(), Session::get('ticketID'));
            Session::forget('ticketID');
            Session::forget('tableNumber');
        }
    }

    private function getTotalBasketValue()
    {
        $totalBasket = $this->getSumTicketLines(Session::get('ticketID'));
        return $totalBasket;
    }


}

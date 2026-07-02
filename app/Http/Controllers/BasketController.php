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
        $printedAny = false;

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

            if (!empty($toprint) && $this->sendLinesToSelectedPrinter($header, $toprint, $prinernr)) {
                $printedAny = true;
            }
        }

        if (!$printedAny) {
            Session::flash('error', 'No se ha podido imprimir el ticket. Por favor avisa a nuestro personal.');
            Log::warning('Order print skipped: no lines matched printer mapping or all printer jobs failed.', [
                'ticket_id' => $ticketID,
                'nr_of_printers' => config('app.nr-of-printers'),
            ]);
            return redirect()->route('basket');
        }

        $this->setUnprintedTicketLinesAsPrinted($ticket, $ticketID);
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
     * @param $ticket_lines
     * @return array|null
     */
    private function setUnprintedTicketLinesAsPrinted(SharedTicket $ticket, $ticketID)
    {
        $lines_to_print = null;
        foreach ($ticket->m_aLines as $ticket_line) {
            if ($ticket_line->attributes->updated) {
                $lines_to_print[] = $ticket_line;
                $ticket_line->setPrinted();
            }
        }
        $this->updateOpenTable($ticket, $ticketID);
        return $lines_to_print;
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
        $this->footer = 'PAGADO online';
        $this->printOrderAndReceipt($ticketID);

        $this->setTicketPayed($ticketID, 'online');
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
        $pickup_ID = DB::table('pickup_number')->max('id');
        $pickup_ID = $pickup_ID + 1;
        DB::statement("UPDATE pickup_number set id = " . $pickup_ID . ";");
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

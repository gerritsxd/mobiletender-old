<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\PrinterTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Traits\UnicentaPayedTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use function openssl_decrypt;
use function redirect;
use function view;

class AdminPaymentController extends Controller
{
    //
    use PrinterTrait;
    use UnicentaPayedTrait;

    public function paypanel(){
        $places = DB::select('select id,name from places order by ABS(id)');
        $tickets = DB::select('select * from sharedtickets where length(id)<3');
        $openTicket = [];
        $openTicketSum= [];
        $ticketWithUnorderditems=[];

        foreach ( $tickets as $ticket) {
            $openTicket[] = $ticket->id;


        }
        foreach($places as $place){
            $openTicketSum[] = $this->getSumTicketLines($place->id);
            $ticketWithUnorderdItems[]= $this->hasUnprintedTicketLines($place->id);
        }


        return view ('admin.payment',compact(['places','openTicket','openTicketSum','ticketWithUnorderdItems']));

    }
    public function pay($tableId){
       $lines =  $this->getTicketLines($tableId);
       $totalBasketPrice = $this->getSumTicketLines($tableId);
       Session::put('tableID',$tableId) ;
       Session::put('ticketID',$tableId) ;
       return view ('admin.basket',compact(['lines','totalBasketPrice','tableId']));
    }

    public function payed($tableId,$mode){

        $lines =  $this->getTicketLines($tableId);

        $header = config('app.invoice_header');


        $header .= "\n\n Mesa:  ". $tableId;
        $header .= "\n Fecha: ".Carbon::now();



        $ticketNr = $this->setTicketPayed($tableId,$mode);
        $header .= "\n Ticket Nr: ".$ticketNr;
        $this->printInvoice($header,$lines);
        return redirect(route('paypanel'));
    }
    public function payedpost(Request $request){
        $tableId=$request->tableId;
        $mode = $request->submit;
        $lines =  $this->getTicketLines($tableId);
        $checked = $request->input('toPay');

        if (empty($checked)) {
            return redirect(route('paypanel'))->with('error', 'No hay líneas seleccionadas para cobrar.');
        }

        $linestoPrint = [];
        foreach ($checked as $check) {
            if (isset($lines[$check])) {
                $linestoPrint[] = $lines[$check];
            }
        }

        if (empty($linestoPrint)) {
            return redirect(route('paypanel'))->with('error', 'Las líneas seleccionadas ya no existen.');
        }

        $header = config('app.invoice_header');

        $header .= "\n\n Mesa:  ". $tableId;
        $header .= "\n Fecha: ".Carbon::now();



        $ticketNr = $this->setTicketPayed($tableId,$mode,$linestoPrint);
        if ($ticketNr === null) {
            return redirect(route('paypanel'))->with('error', 'El ticket ya estaba cobrado o vacío.');
        }
        $header .= "\n Ticket Nr: ".$ticketNr;

        try {
            $this->printInvoice($header,$linestoPrint);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('payedpost: invoice print failed after payment: ' . $e->getMessage());
        }
        return redirect(route('paypanel'));
    }

    public function closecash(){
        $totals = $this->getClosedCash();
        $this->justOpenDrawer();
        return view('admin.closecash',compact(['totals']));

    }

    public function printmoney(){
        $totals = $this->getClosedCash();

        return view('admin.closecash',compact(['totals']));
    }

    public function closemoney(){
        $totals = $this->getClosedCash();
        $this->closeCashDB();
        $this->printClosedCash($totals);
        $totals = $this->getClosedCash();
        return view('admin.closecash',compact(['totals']));
    }

    public function movementsIndex(){

        $movements = $this->getMovementsLines();
        return view('admin.movements',compact('movements'));
    }

    public function addmovement(Request $request){
        $payment = $request->payment;
        $total = str_replace(',','.',$request->total);
        if($payment != 'cashin'){
            $total = 0 - $total;
        }
        $notes = $request->notes;
        $this->addMovementFromForm($payment,$total,$notes);
        $this->justOpenDrawer(1);
        return $this->movementsIndex();
        }

        public function movefrom($fromID){
            $places = DB::select('select id,name from places order by ABS(id)');
            $tickets = DB::select('select * from sharedtickets where length(id)<3');
            $openTicket = [];
            $openTicketSum= [];

            foreach ( $tickets as $ticket) {
                $openTicket[] = $ticket->id;

            }
            foreach($places as $place){
                $openTicketSum[] = $this->getSumTicketLines($place->id);

            }
        return view ('admin.moveto',compact('places','openTicket','openTicketSum','fromID'));
        }

        public function moveto($from,$to){
            $this->moveTable($from,$to);
            return redirect()->route('paypanel');
        }

    public function openDrawer(){
            $this->justOpenDrawer(1);
        return redirect()->route('paypanel');
    }


}

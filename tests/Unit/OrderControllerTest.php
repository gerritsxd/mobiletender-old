<?php
/**
 * Created by PhpStorm.
 * User: Gerrit
 * Date: 22/10/2020
 * Time: 11:02
 */

namespace Tests\Unit;
use Tests\TestCase;

use App\Http\Controllers\OrderController;
use App\Http\Controllers\Unicenta\UnicentaSharedTicketController;
use App\Traits\SharedTicketTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;


class OrderControllerTest extends TestCase
{
    use SharedTicketTrait;

    public function testAddProduct(){
        $orderController = new OrderController();
        $orderController->saveEmptyTicket($orderController->createEmptyTicket(),'1234');
        Session::put('ticketID', '1234');
        self::assertEquals(0,count($orderController->getTicket('1234')->m_aLines));
        $orderController->addProduct(new Request(), self::PRODUCT_ID);
        self::assertEquals(1,count($orderController->getTicket('1234')->m_aLines));

    }

    public function testCleanTicketID()
{
    $ticketController = new UnicentaSharedTicketController();
    $ticketController->clearOpenTableTicket('1234');
    self::assertEmpty($ticketController->getTicketLines('1234'));

}

public function testCreateSession(){
    $orderController = new OrderController();
    $orderController->checkForSessionTicketId();
    self::assertNotNull(Session::get('ticketID'));
    // Clean up the ticket the session check created.
    $orderController->clearOpenTableTicket(Session::get('ticketID'));
}






}

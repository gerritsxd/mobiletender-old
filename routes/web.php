<?php


use App\Http\Controllers\Admin\AdminHomeController;
use App\Http\Controllers\Admin\AdminOrderController;

use App\Http\Controllers\Admin\AdminPaymentController;
use App\Http\Controllers\Admin\AdminReceiptController;
use App\Http\Controllers\Admin\AdminStatsController;
use App\Http\Controllers\Admin\AdminStockController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\BasketController;
use App\Http\Controllers\FlashOfferController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PayPalController;
use App\Http\Controllers\ProductController;

use App\Http\Controllers\ProductImageController;

use App\Http\Controllers\TimeReportController;
use App\Models\TimeReport;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register Web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "Web" middleware group. Now create something great!
|
*/

Auth::routes();
Route::group(['middleware' => ['web']], function () {
    Route::get('/', [OrderController::class, 'order']);
    Route::get('lang/{lang}', ['as' => 'lang.switch', 'uses' => 'App\Http\Controllers\LanguageController@switchLang']);
    Route::get('/menu', [OrderController::class, 'menu']);
    Route::get('/basket/', [OrderController::class, 'showBasket'])->name('basket');

    Route::get('/order/table/{id}', [OrderController::class, 'orderForTableNr']);
    //TODO temporary route for misprinted QR codes
    Route::get('/order/table/order/table/{id}', [OrderController::class, 'orderForTableNr']);
    // TODO END
    Route::get('/order/', [OrderController::class, 'order'])->name('order');
    Route::get('/order/catalog.json', [OrderController::class, 'catalogJson'])->middleware('throttle:30,1')->name('order.catalog');
    Route::get('/order/category/{id}', [OrderController::class, 'showProductsFromCategory']);
    Route::get('/menu/category/{id}', [OrderController::class, 'showProductsFromCategoryForMenu']);

    Route::post('/order/addproduct/{id}', [OrderController::class, 'addProduct']);
    Route::post('/order/cancelproduct/{id}', [OrderController::class, 'cancelProduct']);
    Route::post('/order/addAddonProduct', [OrderController::class, 'addAddOnProduct']);

    Route::post('/order/admincancelproduct/{id}', [OrderController::class, 'admincancelproduct']);


    // Checkout lives in the basket (BasketController::checkout was removed);
    // keep the URL working instead of 500ing.
    Route::get('/checkout/', fn () => redirect()->route('basket'))->name('checkout');
    Route::get('/checkout/pickup', [BasketController::class, 'setPickUpId']);
    Route::get('/checkout/pay', [BasketController::class, 'pay'])->name('pay');
    Route::get('/checkout/payed', [BasketController::class, 'payed'])->name('payed');
    Route::get('/checkout/confirmForTable/{id}', [BasketController::class, 'confirmForTable']);
    Route::get('/checkout/printOrder/{id}', [BasketController::class, 'sendOrder']);
    Route::get('/checkout/printOrderEfectivo/{id}', [BasketController::class, 'printOrderEfectivo']);
    Route::get('/checkout/printOrderTarjeta/{id}', [BasketController::class, 'printOrderTarjeta']);
    Route::get('/checkout/printOrderOnline/{id}', [BasketController::class, 'printOrderOnline']);
    Route::get('/checkout/printOrderPagado/{id}', [BasketController::class, 'printOrderPagado']);
    Route::get('/checkout/printOrderTicket/{id}', [BasketController::class, 'printTicketfromPayment']);

    Route::post('/paypal/create-order', [PayPalController::class, 'createOrder'])->name('paypal.createOrder');
    Route::post('/paypal/client-log', [PayPalController::class, 'clientLog'])->middleware('throttle:30,1')->name('paypal.clientLog');
    Route::post('/paypal/capture-order/{orderId}', [PayPalController::class, 'captureOrder'])
        ->where('orderId', '[A-Z0-9]+')
        ->name('paypal.captureOrder');

    Route::get('/timereport',[TimeReportController::class,'index'])->middleware('is_employee');
    Route::get('/timereport/enter',[TimeReportController::class,'enter'])->middleware('is_employee');
    Route::get('/timereport/exit',[TimeReportController::class,'exit'])->middleware('is_employee');





    Route::get('/admin', [AdminHomeController::class, 'index'])->name('admin')->middleware('is_employee');
    Route::get('/appconfig', [AdminHomeController::class, 'appconfig'])->middleware('is_admin');
    Route::post('/appconfig', [AdminHomeController::class, 'updateconfig'])->middleware('is_admin');
    Route::get('/openorders', [AdminOrderController::class, 'index'])->middleware('is_manager');
    Route::post('/openorders/delete/{id}', [AdminOrderController::class, 'delete'])->middleware('is_manager')->name('deleteorder');
    Route::post('/openorders/deleteTable/{id}', [AdminOrderController::class, 'deleteTable'])->middleware('is_manager')->name('deleteTable');
    Route::get('/showusers',[AdminOrderController::class, 'showusers'])->middleware('is_admin')->name('admin.users');
    Route::post('/deletuser/{id}',[AdminOrderController::class, 'deleteuser'])->middleware('is_admin');
    Route::post('/changeusertype/{id}/{type}',[AdminOrderController::class, 'changeUserType'])->middleware('is_admin');


    Route::get('/adminvertable/{id}', [AdminOrderController::class, 'admintable'])->middleware('is_manager');
    Route::get('/printbill/{id}', [AdminOrderController::class, 'send_bill'])->middleware('is_manager');
    Route::get('/selecttable',[AdminHomeController::class, 'selectTableNr'])->middleware('is_waiter');

    Route::resource('/products', ProductController::class)->middleware('is_manager');
    Route::get('/products/list/{id}',[ProductController::class,'getProductList'])->middleware('is_manager');
    Route::get('/products/index/{id?}', [ProductController::class, 'index'])->middleware('is_manager');
    Route::get('/crop-image/{id}', [ProductController::class, 'editImage'])->middleware('is_manager');
    Route::post('crop-image', [ProductController::class, 'imageCrop'])->middleware('is_manager');
    Route::post('/products/catalog', [ProductController::class, 'toggleCatalog'])->middleware('is_manager');
    Route::post('/addOnProduct/add', [ProductController::class, 'addOnProductAdd'])->middleware('is_manager');
    Route::post('/product/alergen', [ProductController::class, 'toggleAlergen'])->middleware('is_manager');
    Route::post('/addOnProduct/remove', [ProductController::class, 'removeAddOnProductAdd'])->middleware('is_manager');
    Route::get('/dbimage/{id}', [ProductImageController::class, 'getImage']);

    Route::resource('categories', App\Http\Controllers\CategoryController::class)->middleware('is_manager');

    Route::post('/categories/setparent',[CategoryController::class,'setParentId'])->middleware('is_manager');
    Route::post('/categories/toggleactive',[CategoryController::class,'toggleActive'])->middleware('is_manager');

    Route::get('/offers', [OfferController::class, 'index'])->middleware('is_manager')->name('offers.index');
    Route::get('/offers/create', [OfferController::class, 'create'])->middleware('is_manager')->name('offers.create');
    Route::post('/offers', [OfferController::class, 'store'])->middleware('is_manager')->name('offers.store');
    Route::get('/offers/{id}/edit', [OfferController::class, 'edit'])->middleware('is_manager')->name('offers.edit');
    Route::put('/offers/{id}', [OfferController::class, 'update'])->middleware('is_manager')->name('offers.update');
    Route::delete('/offers/{id}', [OfferController::class, 'destroy'])->middleware('is_manager')->name('offers.destroy');

    Route::get('/order/offers', [OfferController::class, 'showOffersForOrder'])->name('order.offers');
    Route::get('/order/addoffer/{id}', [OfferController::class, 'addOffer'])->name('order.addoffer');

    // Flash offers: public endpoints for ordering clients
    Route::get('/flash-offers/poll', [FlashOfferController::class, 'poll'])->middleware('throttle:60,1')->name('flashoffers.poll');
    Route::post('/order/addflashoffer/{id}', [FlashOfferController::class, 'addToOrder'])->name('flashoffers.add');

    // Flash offers: admin
    Route::get('/flashoffers', [FlashOfferController::class, 'index'])->middleware('is_manager')->name('flashoffers.index');
    Route::post('/flashoffers', [FlashOfferController::class, 'store'])->middleware('is_manager')->name('flashoffers.store');
    Route::post('/flashoffers/{id}/stop', [FlashOfferController::class, 'stop'])->middleware('is_manager')->name('flashoffers.stop');
    Route::post('/flashoffers/{id}/extend', [FlashOfferController::class, 'extend'])->middleware('is_manager')->name('flashoffers.extend');
    Route::delete('/flashoffers/{id}', [FlashOfferController::class, 'destroy'])->middleware('is_manager')->name('flashoffers.destroy');

    Route::get('/payments/{id}',[AdminPaymentController::class,'pay'])->middleware('is_manager');
    //Route::get('/payed/{id}/{mode}',[AdminPaymentController::class,'payed'])->middleware('is_manager');
    Route::post('/payed',[AdminPaymentController::class,'payedpost'])->middleware('is_manager')->name('cajapayed');
    Route::get('/paypanel',[AdminPaymentController::class,'paypanel'])->middleware('is_manager')->name('paypanel');
    Route::get('/moveto/{from}/{to}',[AdminPaymentController::class,'moveto'])->middleware('is_finance')->name('moveto');
    Route::get('/movefrom/{from}',[AdminPaymentController::class,'movefrom'])->middleware('is_finance')->name('movefrom');

    Route::get('/opendrawer',[AdminPaymentController::class,'openDrawer']) ->middleware('is_finance');

    Route::get('/receipts',[AdminReceiptController::class,'index']) ->middleware('is_finance');
    Route::post('/deletereceipt/{id}',[AdminReceiptController::class,'delete'])->middleware('is_admin');
    Route::get('/editreceipt/{id}',[AdminReceiptController::class,'edit'])->middleware('is_admin');
    Route::post('/deletereceiptline/{id}/{line}',[AdminReceiptController::class,'deletereceiptline'])->middleware('is_admin');
    Route::post('/changereceiptpaymenttype',[AdminReceiptController::class,'changepaymenttype'])->middleware('is_admin');

     Route::get('/closecash',[AdminPaymentController::class,'closecash'])->middleware('is_manager')->name('closecash');
    Route::get('/printmoney',[AdminPaymentController::class,'printmoney'])->middleware('is_manager')->name('printmoney');
    Route::get('/closemoney',[AdminPaymentController::class,'closemoney'])->middleware('is_manager')->name('closemoney');
    Route::get('/movements',[AdminPaymentController::class,'movementsIndex'])->middleware('is_manager')->name('movementsIndex');
    Route::post('/addmovement',[AdminPaymentController::class,'addmovement'])->middleware('is_manager')->name('addmovement');

    Route::get('/stockindex/{cat?}',[AdminStockController::class,'currentStockIndex'])->middleware('is_manager')->name('stockIndex');
    Route::post('/stock/add',[AdminStockController::class,'addStock'])->middleware('is_manager');

    Route::get ('/stats',[AdminStatsController::class,'index'])->middleware('is_admin');
    Route::get('/waiterstats', [App\Http\Controllers\Admin\WaiterStatsController::class, 'index'])->middleware('is_manager')->name('waiterstats');

    // Kitchen display (iPad)
    Route::get('/kitchen', [App\Http\Controllers\KitchenController::class, 'index'])->middleware('is_employee')->name('kitchen');
    Route::get('/kitchen/orders.json', [App\Http\Controllers\KitchenController::class, 'ordersJson'])->middleware('is_employee')->name('kitchen.orders');
    Route::get('/kitchen/ready-for-me.json', [App\Http\Controllers\KitchenController::class, 'readyForMe'])->middleware('is_employee')->name('kitchen.readyForMe');
    Route::post('/kitchen/orders/{id}/status', [App\Http\Controllers\KitchenController::class, 'setStatus'])->middleware('is_employee')->name('kitchen.status');


    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
});


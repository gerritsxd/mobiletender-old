<?php

namespace App\Http\Controllers;

use App\Models\ProductAdOn;
use App\Models\UnicentaModels\Category;
use App\Services\ShopBasketData;
use App\Traits\SharedTicketTrait;

use App\Models\UnicentaModels\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use App\Traits\ProductTrait;
use Illuminate\Support\Str;
use function json_encode;


class OrderController extends Controller
{
    use SharedTicketTrait;
    use ProductTrait;
    protected $categories;

    public function order()
    {
        $this->checkForSessionTicketId();
        $totalBasketPrice = $this->getTotalBasketValue();
        $categories = Category::where('catshowname',1 )->ordered()->get();
        $currentCategoryId = $categories[0]->id;
        $products = $this->getCategoryProducts($currentCategoryId);
        $basketItemCount = $this->countProductLinesInCurrentTicket();
        [$rootCategoryId, $subCategories] = $this->categoryChipContext($currentCategoryId);

        return view('order.order', compact(
            'categories',
            'products',
            'totalBasketPrice',
            'currentCategoryId',
            'basketItemCount',
            'rootCategoryId',
            'subCategories'
        ));
    }
    public function menu()
    {
        $this->checkForSessionTicketId();
        $totalBasketPrice = $this->getTotalBasketValue();
        $categories = Category::ordered()->get();
        $currentCategoryId = $categories[0]->id;
        $products = $this->getCategoryProducts($currentCategoryId);
        $basketItemCount = $this->countProductLinesInCurrentTicket();

        return view('order.menu', compact(
            'categories',
            'products',
            'totalBasketPrice',
            'currentCategoryId',
            'basketItemCount'
        ));
    }

    public function orderForTableNr($tablenumber)
    {
        $ticketID = Session::get('ticketID');
       // Log::debug('checkForSessionTicketId: Session TicketID: ' . $ticketID);
       // Log::debug('checkForSessionTicketId: is_null: ' . is_null($ticketID));
       // Log::debug('checkForSessionTicketId: hasTicket: ' . $this->hasTicket($ticketID));
        Session::put('ticketID',$tablenumber);
        Session::put('tableNumber',$tablenumber);
        $webadmin = Auth::check() && Auth::user()->isWaiter();
        //dd($webadmin);
        //TODO posible ver si mesa no esta vacio pero no tiene productos
        if (($this->hasTicket($tablenumber) < 1)) {
            if($this->hasTicket($ticketID)>0 && !$webadmin ){
                $this->moveTable($ticketID,$tablenumber);
                return redirect()->route('basket');
            }else {
                $this->saveEmptyTicket($this->createEmptyTicket(), $tablenumber);
            }
        }

        return $this->order();


    }

    public function showProductsFromCategoryForMenu($category){
        $this->checkForSessionTicketId();
        $currentCategoryId = (string) $category;
        $products = $this->getCategoryProducts($currentCategoryId);
        $totalBasketPrice = $this->getTotalBasketValue();
        $categories = Category::where('catshowname',1 )->ordered()->get();
        $basketItemCount = $this->countProductLinesInCurrentTicket();

        return view('order.menu', compact(
            'categories',
            'products',
            'totalBasketPrice',
            'currentCategoryId',
            'basketItemCount'
        ));
    }
    public function showProductsFromCategory($category){
        $this->checkForSessionTicketId();
        $currentCategoryId = (string) $category;
        $products = $this->getCategoryProducts($currentCategoryId);
        $totalBasketPrice = $this->getTotalBasketValue();
        $categories = Category::where('catshowname',1 )->ordered()->get();
        $basketItemCount = $this->countProductLinesInCurrentTicket();
        [$rootCategoryId, $subCategories] = $this->categoryChipContext($currentCategoryId);

        return view('order.order', compact(
            'categories',
            'products',
            'totalBasketPrice',
            'currentCategoryId',
            'basketItemCount',
            'rootCategoryId',
            'subCategories'
        ));
    }

    /**
     * Root chip + sub-chips for the active category: if the active category is
     * a child, its parent is the root; sub-chips are the root's children.
     *
     * @return array{0: string, 1: \Illuminate\Support\Collection}
     */
    private function categoryChipContext($currentCategoryId): array
    {
        $active = Category::find($currentCategoryId);
        $rootCategoryId = ($active && $active->parentid) ? (string) $active->parentid : (string) $currentCategoryId;
        $subCategories = Category::where('parentid', $rootCategoryId)->ordered()->get();

        return [$rootCategoryId, $subCategories];
    }

    /**
     * Catalog as lightweight JSON for the client-side smart search.
     */
    public function catalogJson()
    {
        $products = Cache::remember('catalog_products_json', 60, function () {
            // NB: $product->category is the raw id COLUMN (it shadows the
            // relation of the same name), so resolve names via a map.
            $categoryNames = Category::pluck('name', 'id');

            return Product::whereHas('product_cat')
                ->orderBy('name')
                ->get()
                ->map(function (Product $product) use ($categoryNames) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'price' => round(((float) $product->pricesell) * 1.1, 2),
                        'category' => $categoryNames[$product->category] ?? null,
                        'category_id' => $product->category,
                    ];
                })->values()->all();
        });

        return response()->json(['products' => $products]);
    }

    public function addProduct(Request $request, $productID){
        $productRow = Product::find($productID);
        if (!$productRow) {
            abort(404);
        }
        $product[] = $productRow;
        $this->addProductsToTicket($product,Session::get('ticketID'));
        $totalBasketValue = $this->getTotalBasketValue();
        $addOnProducts=ProductAdOn::where('product_id',$productID)->get();
        $addOnProductsName=[];
        foreach ($addOnProducts as $addOnProduct){
            $addon = Product::find($addOnProduct->adon_product_id);
            $addOnProductsName[]=[$addOnProduct->adon_product_id, $addon ? $addon->name : '', $addOnProduct->price];
        }

        $lineCount = $this->countProductLinesInCurrentTicket();
        $payload = [
            'total' => (float) $totalBasketValue,
            'addOns' => $addOnProductsName,
            'lineCount' => $lineCount,
        ];
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($payload);
        }

        // Legacy non-JSON: keep array of two stringified values for any old callers
        return [json_encode($totalBasketValue), json_encode($addOnProductsName)];
    }

    public function addAddOnProduct(){
        $productID = request()->get('product_id');
        $price = request()->get('price');
        $row = Product::find($productID);
        if (!$row) {
            abort(404);
        }
        $product[] = $row;
        $product[0]->pricesell=$price/1.1;
        $this->addProductsToTicket($product,Session::get('ticketID'));
        $totalBasketValue = $this->getTotalBasketValue();
        $lineCount = $this->countProductLinesInCurrentTicket();

        return response()->json([
            'total' => (float) $totalBasketValue,
            'lineCount' => $lineCount,
        ]);
    }

    public function cancelProduct(Request $request, $ticketLine){

        $this->removeTicketLine(Session::get('ticketID'),$ticketLine);
        if ($request->expectsJson() || $request->ajax()) {
            $total = $this->getTotalBasketValue();
            $lineCount = $this->countProductLinesInCurrentTicket();

            return response()->json([
                'total' => (float) $total,
                'lineCount' => $lineCount,
            ]);
        }
        return redirect()->route('basket');
    }

    public function admincancelproduct(Request $request, $ticketLine){
        $this->removeTicketLine(Session::get('ticketID'),$ticketLine);
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => true]);
        }
        return redirect()->route('paypanel');
    }
    public function showBasket(){
        $unprintedlines = false;
        $lines = [];
        $ticketLines = $this->getTicket(Session::get('ticketID'))->m_aLines;
        foreach ($ticketLines as $ticketLine){
            if ($ticketLine->attributes->updated == 'true') {
                $unprintedlines = true;
            }
            $lines[] = $ticketLine;
        }
        $totalBasketPrice = $this->getTotalBasketValue();
        $groupedLines = $this->groupBasketLines($lines);
        $basketItemCount = $this->countProductLinesInCurrentTicket();
        $basketIsEmpty = $basketItemCount < 1;

        return view('order.basket', compact(
            'lines',
            'groupedLines',
            'totalBasketPrice',
            'unprintedlines',
            'basketItemCount',
            'basketIsEmpty'
        ));
    }

    public function checkForSessionTicketId()
    {
        $ticketID = Session::get('ticketID');
//        Log::debug('checkForSessionTicketId: Session TicketID: '.$ticketID);
//        Log::debug('checkForSessionTicketId: is_null: '.is_null($ticketID));
//        Log::debug('checkForSessionTicketId: hasTicket: '.$this->hasTicket($ticketID));
        if (is_null($ticketID) or ($this->hasTicket($ticketID)<1)){
            $ticket = $this->createEmptyTicket();
            $newTicketID = Str::uuid()->toString();
            $this->saveEmptyTicket($ticket, $newTicketID);
            Session::put('ticketID',$newTicketID);
            Session::forget('tableNumber');
        }
    }





    private function getTotalBasketValue()
    {
        $totalBasket = $this->getSumTicketLines(Session::get('ticketID'));
        return $totalBasket;
    }

    private function countProductLinesInCurrentTicket(): int
    {
        return app(ShopBasketData::class)->lineCountWithProducts();
    }

    /**
     * @param  array<int, \App\Models\UnicentaModels\SharedTicketLines>  $lines
     * @return array<int, object>
     */
    private function groupBasketLines(array $lines): array
    {
        $user = Auth::user();
        $groups = [];
        foreach ($lines as $line) {
            if (empty($line->productid)) {
                continue;
            }
            $canCancel = $line->attributes->updated == 'true' || ($user && $user->isManager());
            $key = (string) $line->productid.'|'.(string) $line->price.'|'.($canCancel ? '1' : '0');
            if (! isset($groups[$key])) {
                $groups[$key] = (object) [
                    'productid' => $line->productid,
                    'name' => $line->attributes->product->name,
                    'unitPrice' => (float) $line->price,
                    'qty' => 0,
                    'total' => 0.0,
                    'canCancel' => $canCancel,
                    'lineIds' => [],
                ];
            }
            $groups[$key]->qty++;
            $groups[$key]->total += (float) $line->price;
            $groups[$key]->lineIds[] = $line->m_iLine;
        }
        $out = [];
        foreach ($groups as $g) {
            $ids = $g->lineIds;
            $g->lastLineId = $ids[array_key_last($ids)] ?? $ids[0] ?? 0;
            $out[] = $g;
        }

        return $out;
    }




}

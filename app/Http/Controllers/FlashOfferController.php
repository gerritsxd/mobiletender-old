<?php

namespace App\Http\Controllers;

use App\Models\FlashOffer;
use App\Models\UnicentaModels\Product;
use App\Models\UnicentaModels\SharedTicketLines;
use App\Models\UnicentaModels\SharedTicketProduct;
use App\Models\UnicentaModels\SharedTicketUser;
use App\Traits\SharedTicketTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class FlashOfferController extends Controller
{
    use SharedTicketTrait;

    public function index()
    {
        $liveOffers = FlashOffer::live()->orderBy('ends_at')->get();
        $pastOffers = FlashOffer::whereNotIn('id', $liveOffers->pluck('id'))
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();
        $products = Product::orderBy('name')->get();

        return view('admin.flashoffers.index', compact('liveOffers', 'pastOffers', 'products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:120',
            'message' => 'nullable|string|max:200',
            'product_id' => 'nullable|string',
            'flash_price' => 'nullable',
            'duration_minutes' => 'required|integer|min:5|max:720',
        ]);

        if ($request->filled('product_id') && !Product::find($request->input('product_id'))) {
            return redirect()->route('flashoffers.index')
                ->with('error', 'El producto seleccionado no existe.');
        }

        $offer = new FlashOffer([
            'id' => Str::uuid()->toString(),
            'title' => $request->input('title'),
            'message' => $request->input('message'),
            'product_id' => $request->input('product_id') ?: null,
            'flash_price' => $request->input('flash_price'),
            'starts_at' => Carbon::now(),
            'ends_at' => Carbon::now()->addMinutes((int) $request->input('duration_minutes')),
            'active' => true,
            'created_by' => auth()->user() ? auth()->user()->name : null,
        ]);
        $offer->save();

        Cache::forget('flash_offers_live');

        return redirect()->route('flashoffers.index')
            ->with('success', 'Oferta flash "' . $offer->title . '" lanzada. Los clientes la verán en unos segundos.');
    }

    public function stop($id)
    {
        $offer = FlashOffer::findOrFail($id);
        $offer->active = false;
        $offer->ends_at = Carbon::now();
        $offer->save();

        Cache::forget('flash_offers_live');

        return redirect()->route('flashoffers.index')
            ->with('success', 'Oferta flash detenida.');
    }

    public function extend($id)
    {
        $offer = FlashOffer::findOrFail($id);
        $base = $offer->ends_at > Carbon::now() ? $offer->ends_at : Carbon::now();
        $offer->ends_at = $base->copy()->addMinutes(15);
        $offer->active = true;
        $offer->save();

        Cache::forget('flash_offers_live');

        return redirect()->route('flashoffers.index')
            ->with('success', 'Oferta flash ampliada 15 minutos.');
    }

    public function destroy($id)
    {
        FlashOffer::findOrFail($id)->delete();
        Cache::forget('flash_offers_live');

        return redirect()->route('flashoffers.index')
            ->with('success', 'Oferta flash borrada.');
    }

    /**
     * Public JSON endpoint polled by ordering clients.
     */
    public function poll()
    {
        $offers = Cache::remember('flash_offers_live', 10, function () {
            return FlashOffer::live()->orderBy('ends_at')->get()->map(function (FlashOffer $offer) {
                $product = $offer->product_id ? Product::find($offer->product_id) : null;
                $regularPrice = $product ? round(((float) $product->pricesell) * 1.1, 2) : null;
                return [
                    'id' => $offer->id,
                    'title' => $offer->title,
                    'message' => $offer->message,
                    'product_id' => $offer->product_id,
                    'product_name' => $product ? $product->name : null,
                    'price' => $offer->flash_price,
                    'regular_price' => $regularPrice,
                    'ends_at' => $offer->ends_at->toIso8601String(),
                ];
            })->values()->all();
        });

        $now = Carbon::now();
        foreach ($offers as &$offer) {
            $offer['seconds_left'] = max(0, $now->diffInSeconds(Carbon::parse($offer['ends_at']), false));
        }
        unset($offer);
        $offers = array_values(array_filter($offers, function ($offer) {
            return $offer['seconds_left'] > 0;
        }));

        return response()->json(['offers' => $offers]);
    }

    /**
     * Adds the flash offer product to the client's ticket at the flash price
     * (price is applied server-side; the client cannot tamper with it).
     */
    public function addToOrder($id)
    {
        $offer = FlashOffer::find($id);
        if (!$offer || !$offer->isLive()) {
            return response()->json(['error' => 'La oferta flash ya no está activa.'], 410);
        }
        if (!$offer->product_id) {
            return response()->json(['error' => 'Esta oferta no tiene producto asociado.'], 422);
        }

        $product = Product::find($offer->product_id);
        if (!$product) {
            return response()->json(['error' => 'Producto no disponible.'], 422);
        }

        $this->ensureSessionTicket();
        $ticketID = Session::get('ticketID');

        // Flash price is entered tax-included; ticket lines store the net price.
        $priceSell = $offer->flash_price !== null
            ? ((float) $offer->flash_price) / 1.1
            : (float) $product->pricesell;

        $sharedTicketProduct = new SharedTicketProduct(
            $product->reference,
            $offer->flash_price !== null ? $product->name . ' (FLASH)' : $product->name,
            $product->code,
            $product->category,
            $product->printto,
            $priceSell,
            $product->id
        );

        $this->addProductsToTicket([$sharedTicketProduct], $ticketID);

        $total = $this->getSumTicketLines($ticketID);
        $lineCount = count($this->getTicketLines($ticketID));

        return response()->json([
            'success' => true,
            'total' => $total,
            'lineCount' => $lineCount,
        ]);
    }

    private function ensureSessionTicket()
    {
        $ticketID = Session::get('ticketID');
        if (is_null($ticketID) || !$this->hasTicket($ticketID)) {
            $ticket = $this->createEmptyTicket();
            $newTicketID = Str::uuid()->toString();
            $this->saveEmptyTicket($ticket, $newTicketID);
            Session::put('ticketID', $newTicketID);
            Session::forget('tableNumber');
        }
    }
}

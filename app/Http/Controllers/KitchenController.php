<?php

namespace App\Http\Controllers;

use App\Models\KitchenOrderLine;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class KitchenController extends Controller
{
    public function index($station = 'cocina')
    {
        $cfg = $this->stationConfig($station);
        abort_unless($cfg, 404);

        $feedUrl = $station === 'cocina'
            ? route('kitchen.orders')
            : url('/' . $station . '/orders.json');

        return view('station.index', [
            'station' => $station,
            'label' => $cfg['label'] ?? ucfirst($station),
            'feedUrl' => $feedUrl,
            'statusBase' => url('/kitchen/lines'),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function stationConfig(string $station): ?array
    {
        return config('customoptions.stations.' . $station);
    }

    /**
     * Printer numbers a given station serves.
     *
     * @return array<int, string>
     */
    private function stationPrinters(string $station): array
    {
        $cfg = $this->stationConfig($station);
        $printers = $cfg['printers'] ?? config('customoptions.kitchen_printers', '2');

        return array_values(array_filter(array_map(
            'trim',
            explode(',', (string) $printers)
        ), fn ($v) => $v !== ''));
    }

    /**
     * Polled by the kitchen iPad. Returns:
     *  - overview: the aggregate prep list (total still to cook, per product)
     *  - items: each dish as its own ticket, tracked individually (TODO/DOING/DONE)
     * Only lines routed to the kitchen printer(s) are included.
     */
    public function ordersJson($station = 'cocina')
    {
        $kitchenPrinters = $this->stationPrinters($station);

        $lines = KitchenOrderLine::with('order')
            ->where('status', '!=', KitchenOrderLine::STATUS_DELIVERED)
            ->whereHas('order', fn ($q) => $q->where('sent_at', '>=', Carbon::now()->subHours(12)))
            ->get()
            ->filter(fn ($l) => in_array((string) $l->printto, $kitchenPrinters, true))
            ->sortBy(fn ($l) => optional($l->order)->sent_at . '|' . $l->id)
            ->values();

        $now = Carbon::now();
        $items = [];
        $overview = [];

        foreach ($lines as $l) {
            $sentAt = optional($l->order)->sent_at ?? $now;
            $stageSince = $l->status === KitchenOrderLine::STATUS_PREPARING && $l->started_at
                ? $l->started_at
                : $sentAt;

            $items[] = [
                'id' => $l->id,
                'table' => optional($l->order)->table_number,
                'ordered_by' => optional($l->order)->ordered_by,
                'product' => $l->product_name,
                'qty' => (int) $l->quantity,
                'status' => $l->status,
                'elapsed_s' => max(0, Carbon::parse($sentAt)->diffInSeconds($now)),
                'stage_s' => max(0, Carbon::parse($stageSince)->diffInSeconds($now)),
            ];

            // Overview counts everything not yet ready (still to cook).
            if (in_array($l->status, [KitchenOrderLine::STATUS_PENDING, KitchenOrderLine::STATUS_PREPARING], true)) {
                $overview[$l->product_name] = ($overview[$l->product_name] ?? 0) + (int) $l->quantity;
            }
        }

        arsort($overview);
        $overviewList = [];
        foreach ($overview as $name => $qty) {
            $overviewList[] = ['product' => $name, 'qty' => $qty];
        }

        return response()->json([
            'now' => $now->toIso8601String(),
            'overview' => $overviewList,
            'items' => $items,
        ]);
    }

    public function setLineStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,preparing,ready,delivered',
        ]);

        $line = KitchenOrderLine::findOrFail($id);
        $line->status = $request->input('status');
        if ($line->status === KitchenOrderLine::STATUS_PREPARING && !$line->started_at) {
            $line->started_at = Carbon::now();
        }
        if ($line->status === KitchenOrderLine::STATUS_READY && !$line->ready_at) {
            $line->ready_at = Carbon::now();
        }
        if ($line->status === KitchenOrderLine::STATUS_DELIVERED && !$line->delivered_at) {
            $line->delivered_at = Carbon::now();
        }
        $line->save();

        return response()->json(['ok' => true, 'status' => $line->status]);
    }

    /**
     * Polled by each waiter's device: their items that just became ready (per
     * dish — they don't all leave the pass together), not yet delivered.
     */
    public function readyForMe(Request $request)
    {
        $name = auth()->user()->name ?? null;
        if (!$name) {
            return response()->json(['items' => []]);
        }

        $items = KitchenOrderLine::with('order')
            ->where('status', KitchenOrderLine::STATUS_READY)
            ->where('ready_at', '>=', Carbon::now()->subHours(2))
            ->whereHas('order', fn ($q) => $q->where('ordered_by', $name))
            ->get()
            ->map(fn ($l) => [
                'id' => $l->id,
                'table' => optional($l->order)->table_number,
                'product' => $l->product_name,
            ])->values();

        return response()->json(['items' => $items]);
    }

    /**
     * Polled by the customer's phone (session-based): their own dishes that are
     * ready — for the "your dish is on its way" popup, per item.
     */
    public function clientOrderStatus()
    {
        $ids = array_values(array_unique(array_filter([
            (string) Session::get('ticketID'),
            (string) Session::get('tableNumber'),
        ], fn ($v) => $v !== '')));

        if (empty($ids)) {
            return response()->json(['items' => []]);
        }

        $items = KitchenOrderLine::with('order')
            ->where('status', KitchenOrderLine::STATUS_READY)
            ->where('ready_at', '>=', Carbon::now()->subHour())
            ->whereHas('order', fn ($q) => $q->whereIn('table_number', $ids))
            ->get()
            ->map(fn ($l) => [
                'id' => $l->id,
                'table' => optional($l->order)->table_number,
                'product' => $l->product_name,
            ])->values();

        return response()->json(['items' => $items]);
    }
}

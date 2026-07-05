<?php

namespace App\Http\Controllers;

use App\Models\KitchenOrderLine;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class KitchenController extends Controller
{
    public function index()
    {
        return view('kitchen.index');
    }

    /**
     * Printer numbers the kitchen cooks for (config customoptions.kitchen_printers).
     *
     * @return array<int, string>
     */
    private function kitchenPrinters(): array
    {
        return array_values(array_filter(array_map(
            'trim',
            explode(',', (string) config('customoptions.kitchen_printers', '2'))
        ), fn ($v) => $v !== ''));
    }

    /**
     * Polled by the kitchen iPad. Returns:
     *  - overview: the aggregate prep list (total still to cook, per product)
     *  - items: each dish as its own ticket, tracked individually (TODO/DOING/DONE)
     * Only lines routed to the kitchen printer(s) are included.
     */
    public function ordersJson()
    {
        $kitchenPrinters = $this->kitchenPrinters();

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

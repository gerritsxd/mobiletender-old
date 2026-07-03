<?php

namespace App\Http\Controllers;

use App\Models\KitchenOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
     * Polled by the kitchen iPad. Oldest first — that IS the cooking order.
     * Only lines routed to the kitchen printer(s) are shown.
     */
    public function ordersJson()
    {
        $kitchenPrinters = $this->kitchenPrinters();

        $orders = KitchenOrder::with('lines')
            ->where('status', '!=', KitchenOrder::STATUS_DELIVERED)
            ->where('sent_at', '>=', Carbon::now()->subHours(12))
            ->orderBy('sent_at')
            ->get();

        $now = Carbon::now();
        $out = [];

        foreach ($orders as $order) {
            // Keep only the lines this kitchen is responsible for.
            $kitchenLines = $order->lines->filter(function ($line) use ($kitchenPrinters) {
                return in_array((string) $line->printto, $kitchenPrinters, true);
            });
            if ($kitchenLines->isEmpty()) {
                continue; // all drinks / bar — not a kitchen ticket
            }

            $grouped = [];
            foreach ($kitchenLines as $line) {
                $key = $line->product_name;
                if (!isset($grouped[$key])) {
                    $grouped[$key] = ['name' => $line->product_name, 'qty' => 0];
                }
                $grouped[$key]['qty']++;
            }

            $stageSince = $order->status === KitchenOrder::STATUS_PREPARING && $order->started_at
                ? $order->started_at
                : $order->sent_at;

            $out[] = [
                'id' => $order->id,
                'table' => $order->table_number,
                'ordered_by' => $order->ordered_by,
                'status' => $order->status,
                'sent_at' => $order->sent_at->toIso8601String(),
                'elapsed_s' => max(0, $order->sent_at->diffInSeconds($now)),
                'stage_s' => max(0, Carbon::parse($stageSince)->diffInSeconds($now)),
                'lines' => array_values($grouped),
            ];
        }

        return response()->json(['now' => $now->toIso8601String(), 'orders' => $out]);
    }

    public function setStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,preparing,ready,delivered',
        ]);

        $order = KitchenOrder::findOrFail($id);
        $order->status = $request->input('status');
        if ($order->status === KitchenOrder::STATUS_PREPARING && !$order->started_at) {
            $order->started_at = Carbon::now();
        }
        if ($order->status === KitchenOrder::STATUS_READY && !$order->ready_at) {
            $order->ready_at = Carbon::now();
        }
        if ($order->status === KitchenOrder::STATUS_DELIVERED && !$order->delivered_at) {
            $order->delivered_at = Carbon::now();
        }
        $order->save();

        return response()->json(['ok' => true, 'status' => $order->status]);
    }

    /**
     * Polled by each logged-in waiter's device: orders they sent that just
     * became ready and haven't been delivered — for the "your order is ready"
     * notification.
     */
    public function readyForMe(Request $request)
    {
        $name = auth()->user()->name ?? null;
        if (!$name) {
            return response()->json(['orders' => []]);
        }

        $orders = KitchenOrder::where('ordered_by', $name)
            ->where('status', KitchenOrder::STATUS_READY)
            ->where('ready_at', '>=', Carbon::now()->subHours(2))
            ->orderBy('ready_at', 'desc')
            ->get(['id', 'table_number', 'ready_at']);

        return response()->json([
            'orders' => $orders->map(fn ($o) => [
                'id' => $o->id,
                'table' => $o->table_number,
            ])->values()->all(),
        ]);
    }
}

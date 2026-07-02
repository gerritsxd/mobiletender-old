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
     * Polled by the kitchen iPad. Oldest first — that IS the cooking order.
     */
    public function ordersJson()
    {
        $orders = KitchenOrder::with('lines')
            ->where('status', '!=', KitchenOrder::STATUS_DELIVERED)
            ->where('sent_at', '>=', Carbon::now()->subHours(12))
            ->orderBy('sent_at')
            ->get();

        $now = Carbon::now();

        return response()->json([
            'now' => $now->toIso8601String(),
            'orders' => $orders->map(function (KitchenOrder $order) use ($now) {
                // Group identical products so "3× Tapa Boquerones" reads at a glance.
                $grouped = [];
                foreach ($order->lines as $line) {
                    $key = $line->product_name . '|' . ($line->printto ?? '');
                    if (!isset($grouped[$key])) {
                        $grouped[$key] = [
                            'name' => $line->product_name,
                            'qty' => 0,
                            'printto' => $line->printto,
                        ];
                    }
                    $grouped[$key]['qty']++;
                }

                return [
                    'id' => $order->id,
                    'table' => $order->table_number,
                    'ordered_by' => $order->ordered_by,
                    'status' => $order->status,
                    'sent_at' => $order->sent_at->toIso8601String(),
                    'elapsed_s' => max(0, $order->sent_at->diffInSeconds($now)),
                    'lines' => array_values($grouped),
                ];
            })->values()->all(),
        ]);
    }

    public function setStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,ready,delivered',
        ]);

        $order = KitchenOrder::findOrFail($id);
        $order->status = $request->input('status');
        if ($order->status === KitchenOrder::STATUS_READY && !$order->ready_at) {
            $order->ready_at = Carbon::now();
        }
        if ($order->status === KitchenOrder::STATUS_DELIVERED && !$order->delivered_at) {
            $order->delivered_at = Carbon::now();
        }
        $order->save();

        return response()->json(['ok' => true, 'status' => $order->status]);
    }
}

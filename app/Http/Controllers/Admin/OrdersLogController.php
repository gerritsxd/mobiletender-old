<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KitchenOrder;
use App\Models\KitchenOrderLine;
use Illuminate\Http\Request;

class OrdersLogController extends Controller
{
    public function index()
    {
        return view('admin.orderslog');
    }

    /**
     * Paginated order feed for the infinite-scroll log, newest first.
     */
    public function feed(Request $request)
    {
        $perPage = 20;
        $page = max(1, (int) $request->query('page', 1));
        // Optional station filter: only orders with items routed to this printer.
        $printer = $request->query('printer');
        $printer = ($printer === null || $printer === '' || $printer === 'all') ? null : (string) $printer;

        $query = KitchenOrder::with('lines')
            ->orderByDesc('sent_at')
            ->orderByDesc('id');

        if ($printer !== null) {
            $query->whereHas('lines', fn ($q) => $q->where('printto', $printer));
        }

        $orders = $query->paginate($perPage, ['*'], 'page', $page);

        $data = $orders->getCollection()->map(function (KitchenOrder $o) use ($printer) {
            // When filtering by station, show only that station's items + subtotal.
            $lines = $printer === null
                ? $o->lines
                : $o->lines->filter(fn (KitchenOrderLine $l) => (string) $l->printto === $printer)->values();

            return [
                'id' => $o->id,
                'table' => $o->table_number,
                'ordered_by' => $o->ordered_by ?: 'Cliente',
                'sent_at' => optional($o->sent_at)->toIso8601String(),
                'sent_label' => optional($o->sent_at)->format('d/m/Y · H:i'),
                'total' => round((float) $lines->sum('price') * 1.1, 2),
                'item_count' => (int) $lines->sum('quantity'),
                'items' => $lines->map(fn (KitchenOrderLine $l) => [
                    'name' => $l->product_name,
                    'qty' => (int) $l->quantity,
                    'printer' => $l->printto,
                ])->values(),
                'status' => $this->deriveStatus($lines),
            ];
        })->values();

        return response()->json([
            'data' => $data,
            'has_more' => $orders->hasMorePages(),
            'next_page' => $orders->currentPage() + 1,
        ]);
    }

    /**
     * Order-level status = the earliest stage still present among its items.
     *
     * @param  \Illuminate\Support\Collection<int, KitchenOrderLine>  $lines
     * @return array{key: string, label: string}
     */
    private function deriveStatus($lines): array
    {
        $statuses = $lines->pluck('status');
        if ($statuses->contains(KitchenOrderLine::STATUS_PENDING)) {
            return ['key' => 'pending', 'label' => 'Pendiente'];
        }
        if ($statuses->contains(KitchenOrderLine::STATUS_PREPARING)) {
            return ['key' => 'preparing', 'label' => 'En marcha'];
        }
        if ($statuses->contains(KitchenOrderLine::STATUS_READY)) {
            return ['key' => 'ready', 'label' => 'Listo'];
        }
        if ($lines->isNotEmpty()) {
            return ['key' => 'delivered', 'label' => 'Entregado'];
        }

        return ['key' => 'pending', 'label' => '—'];
    }
}

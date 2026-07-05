<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WaiterStatsController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->query('period', 'today');
        $start = match ($period) {
            '7d' => Carbon::today()->subDays(7),
            '30d' => Carbon::today()->subDays(30),
            default => Carbon::today(),
        };
        if (!in_array($period, ['today', '7d', '30d'], true)) {
            $period = 'today';
        }

        // Sold: order lines sent by each person (kitchen_orders log, net prices).
        $sold = DB::select(
            'SELECT ko.ordered_by AS person,
                    COUNT(DISTINCT ko.id) AS orders_count,
                    COALESCE(SUM(kol.quantity), 0) AS lines_count,
                    COALESCE(SUM(kol.price), 0) AS net
             FROM kitchen_orders ko
             JOIN kitchen_order_lines kol ON kol.kitchen_order_id = ko.id
             WHERE ko.sent_at >= ?
             GROUP BY ko.ordered_by
             ORDER BY net DESC',
            [$start->format('Y-m-d H:i:s')]
        );

        // Charged: receipts closed by each person (existing uniCenta data, gross).
        $charged = DB::select(
            'SELECT r.person AS person,
                    COUNT(DISTINCT r.id) AS receipts_count,
                    COALESCE(SUM(p.total), 0) AS total
             FROM payments p
             JOIN receipts r ON p.receipt = r.id
             WHERE r.datenew >= ?
               AND p.payment NOT IN (\'cashin\', \'cashout\')
             GROUP BY r.person
             ORDER BY total DESC',
            [$start->format('Y-m-d H:i:s')]
        );

        return view('admin.waiterstats', compact('sold', 'charged', 'period'));
    }
}

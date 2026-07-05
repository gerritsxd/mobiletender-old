<?php

namespace Tests\Feature;

use App\Models\KitchenOrder;
use App\Models\KitchenOrderLine;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrdersLogTest extends TestCase
{
    private function manager(): User
    {
        $u = new User([
            'name' => 'Mgr ' . Str::random(4),
            'email' => Str::random(10) . '@test.local',
            'password' => bcrypt('secret'),
        ]);
        $u->type = User::MANAGER_TYPE;
        $u->save();

        return $u;
    }

    private function makeOrder(array $lines): KitchenOrder
    {
        $order = KitchenOrder::create([
            'id' => Str::uuid()->toString(),
            'table_number' => '5',
            'ordered_by' => 'Ana',
            'status' => 'pending',
            'sent_at' => Carbon::now(),
        ]);
        foreach ($lines as $l) {
            KitchenOrderLine::create([
                'kitchen_order_id' => $order->id,
                'product_id' => self::PRODUCT_ID,
                'product_name' => $l['name'],
                'quantity' => $l['qty'],
                'price' => 5.0 * $l['qty'],
                'printto' => $l['printer'],
                'status' => 'pending',
            ]);
        }

        return $order;
    }

    public function test_orderslog_requires_manager(): void
    {
        $this->get('/orderslog')->assertRedirect();
        $this->get('/orderslog/feed.json')->assertRedirect();
    }

    public function test_feed_returns_orders_with_who_and_when(): void
    {
        $mgr = $this->manager();
        $order = $this->makeOrder([
            ['name' => 'Fries', 'qty' => 2, 'printer' => '2'],
            ['name' => 'Caña', 'qty' => 1, 'printer' => '1'],
        ]);

        $res = $this->actingAs($mgr)->getJson('/orderslog/feed.json');
        $res->assertStatus(200);
        $row = collect($res->json('data'))->firstWhere('id', $order->id);
        self::assertNotNull($row);
        self::assertEquals('Ana', $row['ordered_by']);
        self::assertNotEmpty($row['sent_label']);
        self::assertEquals(3, $row['item_count']); // 2 + 1
        self::assertArrayHasKey('has_more', $res->json());

        KitchenOrderLine::where('kitchen_order_id', $order->id)->delete();
        $order->delete();
        $mgr->delete();
    }

    public function test_printer_filter_limits_to_station_items(): void
    {
        $mgr = $this->manager();
        $order = $this->makeOrder([
            ['name' => 'Fries', 'qty' => 2, 'printer' => '2'],
            ['name' => 'Caña', 'qty' => 1, 'printer' => '1'],
        ]);

        // Filter to printer 2: only the kitchen item, subtotal for it only.
        $res = $this->actingAs($mgr)->getJson('/orderslog/feed.json?printer=2');
        $row = collect($res->json('data'))->firstWhere('id', $order->id);
        self::assertNotNull($row);
        self::assertEquals(2, $row['item_count']);
        self::assertEquals('Fries', $row['items'][0]['name']);
        self::assertEqualsWithDelta(round(10.0 * 1.1, 2), $row['total'], 0.001);

        // Filter to printer 3: this order has nothing there.
        $res3 = $this->actingAs($mgr)->getJson('/orderslog/feed.json?printer=3');
        self::assertNull(collect($res3->json('data'))->firstWhere('id', $order->id));

        KitchenOrderLine::where('kitchen_order_id', $order->id)->delete();
        $order->delete();
        $mgr->delete();
    }
}

<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\KitchenOrder;
use App\Models\KitchenOrderLine;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class KitchenTest extends TestCase
{
    private function makeEmployee(): User
    {
        $user = new User([
            'name' => 'TestCook ' . Str::random(4),
            'email' => Str::random(10) . '@test.local',
            'password' => bcrypt('secret'),
        ]);
        $user->type = User::EMPLOYEE_TYPE;
        $user->save();

        return $user;
    }

    /**
     * @return array{0: KitchenOrder, 1: KitchenOrderLine}
     */
    private function makeKitchenItem(int $qty = 1, string $product = 'Tapa Test', string $printto = '2'): array
    {
        $order = KitchenOrder::create([
            'id' => Str::uuid()->toString(),
            'table_number' => '7',
            'ordered_by' => 'TestWaiter',
            'status' => 'pending',
            'sent_at' => Carbon::now()->subMinutes(3),
        ]);
        $line = KitchenOrderLine::create([
            'kitchen_order_id' => $order->id,
            'product_id' => self::PRODUCT_ID,
            'product_name' => $product,
            'quantity' => $qty,
            'price' => 4.55 * $qty,
            'printto' => $printto, // 2 = kitchen station
            'status' => 'pending',
        ]);

        return [$order, $line];
    }

    private function cleanup(KitchenOrder $order): void
    {
        KitchenOrderLine::where('kitchen_order_id', $order->id)->delete();
        $order->delete();
    }

    public function test_kitchen_requires_employee_login(): void
    {
        $this->get('/kitchen')->assertRedirect();
        $this->get('/kitchen/orders.json')->assertRedirect();
    }

    public function test_board_shows_items_and_aggregate_overview(): void
    {
        $user = $this->makeEmployee();
        [$o1, $l1] = $this->makeKitchenItem(2, 'Atún Tomate');
        [$o2, $l2] = $this->makeKitchenItem(3, 'Fries');

        $response = $this->actingAs($user)->getJson('/kitchen/orders.json');
        $response->assertStatus(200);

        // Individual item tickets.
        $item = collect($response->json('items'))->firstWhere('id', $l1->id);
        self::assertNotNull($item);
        self::assertEquals('Atún Tomate', $item['product']);
        self::assertEquals(2, $item['qty']);
        self::assertEquals('7', $item['table']);
        self::assertEquals('pending', $item['status']);

        // Aggregate prep list.
        $overview = collect($response->json('overview'));
        self::assertEquals(2, $overview->firstWhere('product', 'Atún Tomate')['qty']);
        self::assertEquals(3, $overview->firstWhere('product', 'Fries')['qty']);

        $this->cleanup($o1);
        $this->cleanup($o2);
        $user->delete();
    }

    public function test_each_item_marked_ready_independently(): void
    {
        $user = $this->makeEmployee();
        [$o1, $l1] = $this->makeKitchenItem(1, 'Atún Tomate');
        [$o2, $l2] = $this->makeKitchenItem(1, 'Fries');
        // same table, but separate items — they don't leave the pass together.
        $o2->table_number = $o1->table_number;
        $o2->save();

        $mark = fn ($id, $s) => $this->actingAs($user)->withoutMiddleware(VerifyCsrfToken::class)
            ->postJson('/kitchen/lines/' . $id . '/status', ['status' => $s]);

        // Mark only the Atún ready; Fries stays cooking.
        $mark($l1->id, 'ready')->assertStatus(200)->assertJsonPath('status', 'ready');
        $l1->refresh();
        $l2->refresh();
        self::assertNotNull($l1->ready_at);
        self::assertEquals('pending', $l2->status);

        // Board reflects one item in each of ready / pending.
        $data = $this->actingAs($user)->getJson('/kitchen/orders.json')->json('items');
        self::assertEquals('ready', collect($data)->firstWhere('id', $l1->id)['status']);
        self::assertEquals('pending', collect($data)->firstWhere('id', $l2->id)['status']);

        $this->cleanup($o1);
        $this->cleanup($o2);
        $user->delete();
    }

    public function test_bar_only_item_not_shown_on_kitchen_board(): void
    {
        $user = $this->makeEmployee();
        [$order, $line] = $this->makeKitchenItem(1, 'Caña', '1'); // printto 1 = bar

        $items = $this->actingAs($user)->getJson('/kitchen/orders.json')->json('items');
        self::assertNull(collect($items)->firstWhere('id', $line->id));

        $this->cleanup($order);
        $user->delete();
    }

    public function test_waiter_notified_per_ready_item(): void
    {
        $user = $this->makeEmployee();
        [$order, $line] = $this->makeKitchenItem(1, 'Atún Tomate'); // ordered_by TestWaiter
        $line->status = 'ready';
        $line->ready_at = Carbon::now();
        $line->save();

        $waiter = $this->makeEmployee();
        $waiter->name = 'TestWaiter';
        $waiter->save();
        $ready = $this->actingAs($waiter)->getJson('/kitchen/ready-for-me.json');
        $ready->assertStatus(200);
        self::assertEquals('Atún Tomate', $ready->json('items.0.product'));
        self::assertEquals('7', $ready->json('items.0.table'));

        $other = $this->makeEmployee();
        self::assertCount(0, $this->actingAs($other)->getJson('/kitchen/ready-for-me.json')->json('items'));

        $this->cleanup($order);
        $user->delete();
        $waiter->delete();
        $other->delete();
    }

    public function test_client_sees_own_table_ready_item(): void
    {
        [$order, $line] = $this->makeKitchenItem(1, 'Atún Tomate'); // table 7
        $line->status = 'ready';
        $line->ready_at = Carbon::now();
        $line->save();

        $response = $this->withSession(['ticketID' => '7', 'tableNumber' => '7'])
            ->getJson('/order/ready-status.json');
        $response->assertStatus(200);
        self::assertEquals('Atún Tomate', $response->json('items.0.product'));

        $other = $this->withSession(['ticketID' => '99', 'tableNumber' => '99'])
            ->getJson('/order/ready-status.json');
        self::assertCount(0, $other->json('items'));

        $this->cleanup($order);
    }

    public function test_waiterstats_requires_manager(): void
    {
        $employee = $this->makeEmployee();
        $this->actingAs($employee)->get('/waiterstats')->assertStatus(302);
        $employee->delete();
    }
}

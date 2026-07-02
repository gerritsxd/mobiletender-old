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

    private function makeKitchenOrder(string $status = 'pending'): KitchenOrder
    {
        $order = KitchenOrder::create([
            'id' => Str::uuid()->toString(),
            'table_number' => '7',
            'ordered_by' => 'TestWaiter',
            'status' => $status,
            'sent_at' => Carbon::now()->subMinutes(3),
        ]);
        KitchenOrderLine::create([
            'kitchen_order_id' => $order->id,
            'product_id' => self::PRODUCT_ID,
            'product_name' => 'Tapa Test',
            'price' => 4.55,
            'printto' => '1',
        ]);

        return $order;
    }

    public function test_kitchen_requires_employee_login(): void
    {
        $this->get('/kitchen')->assertRedirect();
        $this->get('/kitchen/orders.json')->assertRedirect();
    }

    public function test_kitchen_shows_pending_orders_fifo_with_lines(): void
    {
        $user = $this->makeEmployee();
        $order = $this->makeKitchenOrder();

        $response = $this->actingAs($user)->getJson('/kitchen/orders.json');

        $response->assertStatus(200);
        $found = collect($response->json('orders'))->firstWhere('id', $order->id);
        self::assertNotNull($found);
        self::assertEquals('7', $found['table']);
        self::assertEquals('TestWaiter', $found['ordered_by']);
        self::assertEquals('pending', $found['status']);
        self::assertEquals('Tapa Test', $found['lines'][0]['name']);
        self::assertEquals(1, $found['lines'][0]['qty']);
        self::assertGreaterThan(0, $found['elapsed_s']);

        KitchenOrderLine::where('kitchen_order_id', $order->id)->delete();
        $order->delete();
        $user->delete();
    }

    public function test_order_can_be_marked_ready_then_delivered_and_disappears(): void
    {
        $user = $this->makeEmployee();
        $order = $this->makeKitchenOrder();

        $this->actingAs($user)->withoutMiddleware(VerifyCsrfToken::class)
            ->postJson('/kitchen/orders/' . $order->id . '/status', ['status' => 'ready'])
            ->assertStatus(200)->assertJsonPath('status', 'ready');

        $this->actingAs($user)->withoutMiddleware(VerifyCsrfToken::class)
            ->postJson('/kitchen/orders/' . $order->id . '/status', ['status' => 'delivered'])
            ->assertStatus(200)->assertJsonPath('status', 'delivered');

        $response = $this->actingAs($user)->getJson('/kitchen/orders.json');
        self::assertNull(collect($response->json('orders'))->firstWhere('id', $order->id));

        $order->refresh();
        self::assertNotNull($order->ready_at);
        self::assertNotNull($order->delivered_at);

        KitchenOrderLine::where('kitchen_order_id', $order->id)->delete();
        $order->delete();
        $user->delete();
    }

    public function test_waiterstats_requires_manager(): void
    {
        $employee = $this->makeEmployee();
        $this->actingAs($employee)->get('/waiterstats')->assertStatus(302);
        $employee->delete();
    }
}

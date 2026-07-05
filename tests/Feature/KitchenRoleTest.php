<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class KitchenRoleTest extends TestCase
{
    private function userOfType(string $type): User
    {
        $u = new User([
            'name' => 'U ' . Str::random(4),
            'email' => Str::random(10) . '@test.local',
            'password' => bcrypt('secret'),
        ]);
        $u->type = $type;
        $u->save();

        return $u;
    }

    public function test_kitchen_role_reaches_private_area_and_kitchen(): void
    {
        $cook = $this->userOfType(User::KITCHEN_TYPE);

        self::assertTrue($cook->isKitchen());
        self::assertTrue($cook->isEmployee());   // passes is_employee (área privada, kitchen)
        self::assertFalse($cook->isWaiter());
        self::assertFalse($cook->isManager());

        $this->actingAs($cook)->get('/admin')->assertStatus(200);
        $this->actingAs($cook)->get('/kitchen')->assertStatus(200);
        $this->actingAs($cook)->getJson('/kitchen/orders.json')->assertStatus(200);

        $cook->delete();
    }

    public function test_kitchen_role_cannot_reach_manager_pages(): void
    {
        $cook = $this->userOfType(User::KITCHEN_TYPE);

        // Manager-only areas redirect to login (blocked by is_manager).
        $this->actingAs($cook)->get('/orderslog')->assertStatus(302);
        $this->actingAs($cook)->get('/flashoffers')->assertStatus(302);
        $this->actingAs($cook)->get('/products')->assertStatus(302);

        $cook->delete();
    }
}

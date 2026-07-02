<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaymentSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // PayPalClient's constructor requires credentials; both endpoints under
        // test reject the request before any PayPal API call is made.
        config(['paypal.client_id' => 'test-client', 'paypal.secret' => 'test-secret']);
    }

    /**
     * Regression test: visiting the online-payment finalize URL directly must
     * never mark a ticket paid — a verified PayPal capture proof is required.
     */
    public function test_print_order_online_requires_capture_proof(): void
    {
        $receiptsBefore = DB::table('receipts')->count();

        $response = $this->get('/checkout/printOrderOnline/5');

        $response->assertRedirect(route('pay'));
        self::assertEquals($receiptsBefore, DB::table('receipts')->count());
    }

    public function test_paypal_create_order_rejected_without_active_ticket(): void
    {
        $response = $this->withoutMiddleware(VerifyCsrfToken::class)
            ->postJson('/paypal/create-order', ['amount' => 0.01]);

        $response->assertStatus(422);
    }

    public function test_paypal_capture_rejected_for_unknown_order(): void
    {
        $response = $this->withoutMiddleware(VerifyCsrfToken::class)
            ->postJson('/paypal/capture-order/UNKNOWNORDER123');

        $response->assertStatus(404);
    }
}

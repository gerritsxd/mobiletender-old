<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\FlashOffer;
use App\Traits\SharedTicketTrait;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class FlashOfferTest extends TestCase
{
    use SharedTicketTrait;

    private function makeOffer(array $overrides = []): FlashOffer
    {
        return FlashOffer::create(array_merge([
            'id' => Str::uuid()->toString(),
            'title' => 'Test flash',
            'message' => 'Solo hoy',
            'product_id' => self::PRODUCT_ID,
            'flash_price' => 5.5,
            'starts_at' => Carbon::now()->subMinute(),
            'ends_at' => Carbon::now()->addHour(),
            'active' => true,
        ], $overrides));
    }

    public function test_poll_returns_live_offers(): void
    {
        $offer = $this->makeOffer();

        $response = $this->getJson('/flash-offers/poll');

        $response->assertStatus(200)
            ->assertJsonPath('offers.0.id', $offer->id)
            ->assertJsonPath('offers.0.title', 'Test flash');
        self::assertGreaterThan(0, $response->json('offers.0.seconds_left'));

        $offer->delete();
    }

    public function test_poll_excludes_expired_and_inactive_offers(): void
    {
        $expired = $this->makeOffer([
            'starts_at' => Carbon::now()->subHours(2),
            'ends_at' => Carbon::now()->subHour(),
        ]);
        $inactive = $this->makeOffer(['active' => false]);

        $response = $this->getJson('/flash-offers/poll');

        $response->assertStatus(200)->assertJsonCount(0, 'offers');

        $expired->delete();
        $inactive->delete();
    }

    public function test_add_flash_offer_applies_server_side_flash_price(): void
    {
        $offer = $this->makeOffer(['flash_price' => 5.5]);

        $response = $this->withoutMiddleware(VerifyCsrfToken::class)
            ->postJson('/order/addflashoffer/' . $offer->id);

        $response->assertStatus(200)->assertJsonPath('success', true);
        self::assertEquals(1, $response->json('lineCount'));
        // Flash price is gross (VAT incl.); ticket lines store net = gross / 1.1.
        self::assertEqualsWithDelta(5.5 / 1.1, (float) $response->json('total'), 0.001);

        $ticketID = session('ticketID');
        self::assertNotNull($ticketID);
        $this->clearOpenTableTicket($ticketID);
        $offer->delete();
    }

    public function test_add_expired_flash_offer_is_rejected(): void
    {
        $offer = $this->makeOffer([
            'starts_at' => Carbon::now()->subHours(2),
            'ends_at' => Carbon::now()->subHour(),
        ]);

        $response = $this->withoutMiddleware(VerifyCsrfToken::class)
            ->postJson('/order/addflashoffer/' . $offer->id);

        $response->assertStatus(410);

        $offer->delete();
    }
}

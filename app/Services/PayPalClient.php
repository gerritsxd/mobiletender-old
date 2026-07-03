<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PayPalClient
{
    private string $clientId;
    private string $secret;
    private string $apiBase;

    public function __construct()
    {
        $this->clientId = (string) config('paypal.client_id');
        $this->secret = (string) config('paypal.secret');
        $mode = (string) config('paypal.settings.mode', 'sandbox');
        $this->apiBase = $mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        if ($this->clientId === '' || $this->secret === '') {
            throw new RuntimeException('PayPal credentials not configured (PAYPAL_CLIENT_ID / PAYPAL_SECRET).');
        }
    }

    public function getAccessToken(): string
    {
        $cacheKey = 'paypal:access_token:' . md5($this->clientId . '|' . $this->apiBase);

        return Cache::remember($cacheKey, now()->addMinutes(480), function () {
            $response = Http::asForm()
                ->withBasicAuth($this->clientId, $this->secret)
                ->acceptJson()
                ->post($this->apiBase . '/v1/oauth2/token', [
                    'grant_type' => 'client_credentials',
                ]);

            if (! $response->successful()) {
                Log::error('PayPal OAuth failed', ['status' => $response->status(), 'body' => $response->body()]);
                throw new RuntimeException('PayPal OAuth failed.');
            }

            return (string) $response->json('access_token');
        });
    }

    public function createOrder(float $amount, string $currency = 'EUR', array $extra = []): array
    {
        $payload = array_replace_recursive([
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => $currency,
                        'value' => number_format($amount, 2, '.', ''),
                    ],
                ],
            ],
        ], $extra);

        $response = Http::withToken($this->getAccessToken())
            ->acceptJson()
            ->withHeaders(['PayPal-Request-Id' => (string) bin2hex(random_bytes(16))])
            ->post($this->apiBase . '/v2/checkout/orders', $payload);

        $this->throwIfBadResponse($response, 'createOrder');
        return $response->json();
    }

    public function captureOrder(string $orderId): array
    {
        // PayPal's capture endpoint takes no body, but Laravel's default JSON
        // body format sends an empty string with Content-Type: application/json,
        // which PayPal rejects as MALFORMED_REQUEST_JSON. Send an explicit {}.
        $response = Http::withToken($this->getAccessToken())
            ->acceptJson()
            ->withHeaders(['PayPal-Request-Id' => (string) bin2hex(random_bytes(16))])
            ->withBody('{}', 'application/json')
            ->post($this->apiBase . '/v2/checkout/orders/' . urlencode($orderId) . '/capture');

        $this->throwIfBadResponse($response, 'captureOrder');
        return $response->json();
    }

    public function getOrder(string $orderId): array
    {
        $response = Http::withToken($this->getAccessToken())
            ->acceptJson()
            ->get($this->apiBase . '/v2/checkout/orders/' . urlencode($orderId));

        $this->throwIfBadResponse($response, 'getOrder');
        return $response->json();
    }

    private function throwIfBadResponse($response, string $op): void
    {
        if ($response->successful()) {
            return;
        }
        Log::error('PayPal ' . $op . ' failed', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);
        try {
            $response->throw();
        } catch (RequestException $e) {
            throw new RuntimeException('PayPal ' . $op . ' failed: ' . $e->getMessage(), 0, $e);
        }
    }
}

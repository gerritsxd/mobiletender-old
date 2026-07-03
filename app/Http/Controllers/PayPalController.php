<?php

namespace App\Http\Controllers;

use App\Services\PayPalClient;
use App\Traits\SharedTicketTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class PayPalController extends Controller
{
    use SharedTicketTrait;

    public function __construct(private readonly PayPalClient $client)
    {
    }

    public function createOrder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'currency' => ['nullable', 'string', 'size:3'],
            'context' => ['nullable', 'string', 'in:pay,checkout'],
        ]);

        $expected = $this->expectedAmountCents($data['context'] ?? 'pay');
        if ($expected === null || $expected <= 0) {
            Log::warning('PayPal create order without verifiable ticket total', [
                'ticket' => Session::get('ticketID'),
                'context' => $data['context'] ?? null,
            ]);
            throw ValidationException::withMessages(['amount' => 'No hay pedido activo para pagar.']);
        }
        $submittedCents = (int) round(((float) $data['amount']) * 100);
        if ($submittedCents !== $expected) {
            Log::warning('PayPal create order amount mismatch', [
                'submitted_cents' => $submittedCents,
                'expected_cents' => $expected,
                'context' => $data['context'] ?? null,
                'ticket' => Session::get('ticketID'),
            ]);
            throw ValidationException::withMessages(['amount' => 'Importe inválido.']);
        }

        try {
            $order = $this->client->createOrder(
                amount: (float) $data['amount'],
                currency: strtoupper($data['currency'] ?? 'EUR'),
            );
        } catch (RuntimeException $e) {
            return response()->json(['error' => 'create_order_failed'], 502);
        }

        Session::put('paypal_pending_order', [
            'id' => $order['id'] ?? null,
            'amount_cents' => $submittedCents,
            'context' => $data['context'] ?? 'pay',
            'created_at' => now()->toIso8601String(),
        ]);

        return response()->json([
            'id' => $order['id'] ?? null,
            'status' => $order['status'] ?? null,
        ]);
    }

    public function captureOrder(Request $request, string $orderId): JsonResponse
    {
        $pending = Session::get('paypal_pending_order');
        if (! is_array($pending) || ($pending['id'] ?? null) !== $orderId) {
            Log::warning('PayPal capture: orderId not in session', [
                'orderId' => $orderId,
                'pending' => $pending,
            ]);
            return response()->json(['error' => 'order_not_found'], 404);
        }

        // The basket must still match the amount the PayPal order was created for.
        // Otherwise items added after create-order would ride along unpaid.
        $currentExpected = $this->expectedAmountCents($pending['context'] ?? 'pay');
        if ($currentExpected === null || $currentExpected !== (int) $pending['amount_cents']) {
            Log::warning('PayPal capture: basket changed since order creation', [
                'orderId' => $orderId,
                'pending_cents' => $pending['amount_cents'] ?? null,
                'current_cents' => $currentExpected,
            ]);
            Session::forget('paypal_pending_order');
            return response()->json(['error' => 'basket_changed'], 409);
        }

        try {
            $captured = $this->client->captureOrder($orderId);
        } catch (RuntimeException $e) {
            return response()->json(['error' => 'capture_failed'], 502);
        }

        $status = $captured['status'] ?? null;
        if ($status !== 'COMPLETED') {
            Log::warning('PayPal capture not completed', ['orderId' => $orderId, 'status' => $status]);
            return response()->json(['error' => 'not_completed', 'status' => $status], 422);
        }

        $capturedCents = $this->capturedAmountCents($captured);
        if ($capturedCents !== null && $capturedCents !== (int) $pending['amount_cents']) {
            Log::error('PayPal capture: captured amount differs from expected', [
                'orderId' => $orderId,
                'captured_cents' => $capturedCents,
                'expected_cents' => $pending['amount_cents'] ?? null,
            ]);
            return response()->json(['error' => 'amount_mismatch'], 422);
        }

        Session::forget('paypal_pending_order');

        // Server-side proof consumed by the finalize step (printOrderOnline);
        // without it a ticket can never be marked paid 'online'.
        Session::put('paypal_capture_proof', [
            'ticket' => Session::get('ticketID'),
            'amount_cents' => (int) $pending['amount_cents'],
            'orderId' => $orderId,
            'captured_at' => now()->toIso8601String(),
        ]);

        return response()->json([
            'status' => $status,
            'orderId' => $orderId,
        ]);
    }

    private function capturedAmountCents(array $captured): ?int
    {
        $capture = $captured['purchase_units'][0]['payments']['captures'][0] ?? null;
        $value = $capture['amount']['value'] ?? null;
        if ($value === null || !is_numeric($value)) {
            return null;
        }
        return (int) round(((float) $value) * 100);
    }

    /**
     * Receives client-side payment events so wallet failures on customer
     * phones are visible in laravel.log.
     */
    public function clientLog(Request $request): JsonResponse
    {
        $data = $request->validate([
            'stage' => 'required|string|max:60',
            'detail' => 'nullable|string|max:500',
        ]);

        Log::warning('PayPal client event', [
            'stage' => $data['stage'],
            'detail' => $data['detail'] ?? null,
            'ticket' => Session::get('ticketID'),
            'ua' => substr((string) $request->userAgent(), 0, 120),
        ]);

        return response()->json(['ok' => true]);
    }

    private function expectedAmountCents(string $context): ?int
    {
        $ticketId = Session::get('ticketID');
        if (! $ticketId) {
            return null;
        }
        try {
            $newLinesPrice = (float) $this->getSumNewTicketLines($ticketId);
        } catch (\Throwable $e) {
            Log::warning('PayPal expectedAmount: cannot read newLinesPrice', ['error' => $e->getMessage()]);
            return null;
        }
        $gross = $newLinesPrice * 1.1;
        return (int) round($gross * 100);
    }
}

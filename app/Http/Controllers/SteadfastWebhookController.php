<?php

namespace App\Http\Controllers;

use App\Exceptions\OrderException;
use App\Models\CourierEvent;
use App\Models\CourierService;
use App\Models\Order;
use App\Services\Couriers\SteadfastService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Steadfast calls this address when a parcel changes status.
 * Dashboard > Webhook > Callback URL = https://YOUR-DOMAIN/steadfast/webhook, then "Generate" an auth token
 * and paste the same token into Courier services > Steadfast > Webhook auth token.
 */
class SteadfastWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $courier = SteadfastService::activeCourier() ?? CourierService::where('driver', 'steadfast')->first();
        $token   = (string) (($courier?->credentials ?? [])['webhook_token'] ?? '');

        if ($token === '' || ! $this->authentic($request, $token)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $data = $request->json()->all() ?: $request->all();
        $key  = (string) ($request->header('Idempotency-Key') ?: sha1($request->getContent()));

        if (CourierEvent::where('event_key', $key)->exists()) {
            return $this->ok();                      // Steadfast sent the same message again
        }

        $order = $this->findOrder($data);
        if (! $order) {
            Log::info('Steadfast webhook for an unknown parcel', ['payload' => $data]);

            return $this->ok();                      // not ours (or deleted), nothing to do, no point in a retry
        }

        $service = new SteadfastService($courier);

        try {
            match ($data['notification_type'] ?? '') {
                'delivery_status' => ! empty($data['status'])
                    ? $service->applyStatus($order, (string) $data['status'], $data['tracking_message'] ?? null, 'webhook', $key)
                    : null,
                'tracking_update' => $service->addTracking($order, (string) ($data['tracking_message'] ?? 'Tracking updated'), $data, $key),
                default           => null,
            };
        } catch (OrderException $e) {
            Log::warning('Steadfast webhook: order not changed', ['order' => $order->order_number, 'reason' => $e->getMessage()]);
        } catch (\Throwable $e) {
            Log::error('Steadfast webhook failed', ['order' => $order->order_number, 'error' => $e->getMessage()]);

            return response()->json(['status' => 'error', 'message' => 'Server error.'], 500);   // Steadfast tries again later
        }

        return $this->ok();
    }

    /** The auth token as "Authorization: Bearer ...", or a valid HMAC-SHA256 signature of the body made with the token. */
    private function authentic(Request $request, string $token): bool
    {
        $bearer = trim(Str::after((string) $request->header('Authorization'), 'Bearer '));
        if ($bearer !== '' && hash_equals($token, $bearer)) {
            return true;
        }

        $signature = trim((string) $request->header('X-Signature'));
        if ($signature === '') {
            return false;
        }

        $raw = $request->getContent();

        return hash_equals(hash_hmac('sha256', $raw, $token), strtolower($signature))
            || hash_equals(base64_encode(hash_hmac('sha256', $raw, $token, true)), $signature);
    }

    private function findOrder(array $data): ?Order
    {
        if (! empty($data['consignment_id']) && $order = Order::where('courier_consignment_id', (string) $data['consignment_id'])->first()) {
            return $order;
        }

        // invoice = order number, with -R2 / -R3 added when a cancelled parcel was sent again
        $invoice = preg_replace('/-R\d+$/', '', (string) ($data['invoice'] ?? ''));

        return $invoice !== '' ? Order::where('order_number', $invoice)->first() : null;
    }

    private function ok(): JsonResponse
    {
        return response()->json(['status' => 'success', 'message' => 'Webhook received successfully.']);
    }
}

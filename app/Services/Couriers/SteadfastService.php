<?php

namespace App\Services\Couriers;

use App\Events\OrderNotification;
use App\Exceptions\OrderException;
use App\Models\CourierEvent;
use App\Models\CourierService;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Steadfast Courier (portal.packzy.com API v1).
 * The API key, secret key and webhook token come from the admin panel (Courier services > Steadfast).
 *
 *   book()          sends an order to Steadfast and saves the consignment id and tracking code
 *   refresh()       asks Steadfast for the latest status of one parcel
 *   applyStatus()   turns a Steadfast status into order changes (used by the webhook and by refresh)
 */
class SteadfastService
{
    public const BASE = 'https://portal.packzy.com/api/v1';

    /** Steadfast status => words for the shop owner and the customer */
    public const LABELS = [
        'pending'                           => 'Waiting for pickup',
        'in_review'                         => 'Being reviewed by the courier',
        'hold'                              => 'On hold',
        'delivered'                         => 'Delivered',
        'delivered_approval_pending'        => 'Delivered, being confirmed',
        'partial_delivered'                 => 'Partly delivered',
        'partial_delivered_approval_pending' => 'Partly delivered, being confirmed',
        'cancelled'                         => 'Cancelled',
        'cancelled_approval_pending'        => 'Cancelled, being confirmed',
        'unknown'                           => 'Status unknown',
        'unknown_approval_pending'          => 'Status unknown, being confirmed',
    ];

    /** The courier will not change these any more */
    public const FINAL = ['delivered', 'partial_delivered', 'cancelled'];

    public function __construct(private CourierService $courier)
    {
    }

    public static function label(?string $status): string
    {
        return self::LABELS[$status] ?? Str::headline((string) $status);
    }

    /** The switched-on Steadfast courier that has its API key and secret key. */
    public static function activeCourier(): ?CourierService
    {
        return CourierService::active()->where('driver', 'steadfast')->get()->first(fn ($c) => $c->isConfigured());
    }

    /** @throws OrderException */
    public static function make(): self
    {
        return new self(static::activeCourier()
            ?? throw new OrderException('Steadfast is not set up. Add the API key and secret key under Courier services and switch it on.'));
    }

    /* ---------------------------------------------------------------
     |  BOOK A PARCEL
     * --------------------------------------------------------------*/

    /**
     * @param array{delivery_type?: int, note?: ?string, mark_shipped?: bool} $options
     * @throws OrderException
     */
    public function book(Order $order, array $options = [], ?User $actor = null): Order
    {
        $markShipped = $options['mark_shipped'] ?? (bool) config('shop.steadfast.mark_shipped_on_booking', true);

        return DB::transaction(function () use ($order, $options, $actor, $markShipped) {
            $order = Order::whereKey($order->id)->lockForUpdate()->with(['status', 'items'])->firstOrFail();

            if ($order->status->restock_items) {
                throw new OrderException('This order is cancelled or returned, so it cannot be sent to the courier.');
            }
            if ($order->courier_consignment_id && ! in_array($order->courier_status, ['cancelled'], true)) {
                throw new OrderException("Already sent to Steadfast (consignment {$order->courier_consignment_id}).");
            }

            $phone = $this->phone($order->shipping_phone) ?? $this->phone($order->customer_phone)
                ?? throw new OrderException('Steadfast needs an 11 digit mobile number (01XXXXXXXXX). Fix the delivery phone number on the order first.');

            // A parcel that was cancelled and is sent again needs a new invoice number
            $tries   = CourierEvent::where('order_id', $order->id)->where('notification_type', 'booked')->count();
            $invoice = $order->order_number . ($tries > 0 ? '-R' . ($tries + 1) : '');

            $note = trim(implode(' | ', array_filter([$options['note'] ?? null, $order->customer_note])));

            $payload = [
                'invoice'           => $invoice,
                'recipient_name'    => $this->clean($order->shipping_name, 100),
                'recipient_phone'   => $phone,
                'recipient_address' => $this->clean("{$order->shipping_address}, {$order->shipping_area}, {$order->shipping_city}", 250),
                'cod_amount'        => max(round($order->due_amount, 2), 0),     // what the rider must collect, 0 when already paid
                'note'              => $this->clean($note, 480),
                'item_description'  => $this->clean($order->items->map(fn ($i) => "{$i->name} x{$i->quantity}")->implode(', '), 255),
                'total_lot'         => max((int) $order->items->sum('quantity'), 1),
                'delivery_type'     => (int) ($options['delivery_type'] ?? 0) === 1 ? 1 : 0,   // 0 home delivery, 1 pick up from the hub
            ];
            if ($order->customer_email) {
                $payload['recipient_email'] = $order->customer_email;
            }

            $json = $this->request('POST', '/create_order', $payload);
            $c    = $json['consignment'] ?? [];

            if ((int) ($json['status'] ?? 0) !== 200 || empty($c['consignment_id'])) {
                Log::warning('Steadfast did not accept the parcel', ['order' => $order->order_number, 'response' => $json]);
                throw new OrderException('Steadfast did not accept the parcel: ' . $this->reason($json));
            }

            $status = $c['status'] ?? 'in_review';
            $cid    = (string) $c['consignment_id'];

            $order->update([
                'courier_service_id'     => $this->courier->id,
                'tracking_code'          => $c['tracking_code'] ?? $order->tracking_code,
                'courier_consignment_id' => $cid,
                'courier_status'         => $status,
                'courier_booked_at'      => now(),
            ]);

            CourierEvent::create([
                'order_id' => $order->id, 'notification_type' => 'booked', 'status' => $status,
                'message'  => "Sent to Steadfast. Consignment {$cid}, tracking code " . ($c['tracking_code'] ?? '-') . ', cash to collect ' . $payload['cod_amount'] . '.',
                'payload'  => $json, 'event_key' => 'booked-' . $cid,
            ]);

            if ($markShipped) {
                $this->moveTo($order, 'shipped', "Sent to Steadfast (consignment {$cid}).", $actor);
            }

            DB::afterCommit(fn () => event(new OrderNotification('courier.booked', $order->fresh(), ['consignment_id' => $cid])));

            return $order->fresh(['status', 'courier']);
        });
    }

    /* ---------------------------------------------------------------
     |  STATUS
     * --------------------------------------------------------------*/

    /** Asks Steadfast for the latest status of the parcel. @throws OrderException */
    public function refresh(Order $order): Order
    {
        if (! $order->courier_consignment_id) {
            throw new OrderException('This order has not been sent to Steadfast yet.');
        }

        $json   = $this->request('GET', '/status_by_cid/' . $order->courier_consignment_id);
        $status = $json['delivery_status'] ?? null;

        if (! $status) {
            Log::warning('Steadfast returned no status', ['order' => $order->order_number, 'response' => $json]);
            throw new OrderException('Steadfast did not return a status: ' . $this->reason($json));
        }

        $this->applyStatus($order, $status, null, 'status check');

        return $order->fresh(['status']);
    }

    /**
     * A new parcel status arrived (webhook or status check). Only a real change does anything,
     * so the same message coming twice is harmless.
     */
    public function applyStatus(Order $order, string $status, ?string $message = null, string $source = 'webhook', ?string $eventKey = null): void
    {
        DB::transaction(function () use ($order, $status, $message, $source, $eventKey) {
            $order = Order::whereKey($order->id)->lockForUpdate()->with('status')->firstOrFail();

            if ($order->courier_status === $status) {
                return;
            }

            $order->update(['courier_status' => $status]);

            CourierEvent::create([
                'order_id' => $order->id, 'notification_type' => 'status', 'status' => $status,
                'message'  => ($message ?: self::label($status)) . " ({$source})", 'event_key' => $eventKey,
            ]);

            switch ($status) {
                case 'delivered':
                    // OrderService also records the cash collected for cash-on-delivery orders
                    $this->moveTo($order, 'delivered', 'Steadfast delivered the parcel.');
                    break;

                case 'partial_delivered':
                    $this->note($order, 'Steadfast says the parcel was only partly delivered. Check it and start a return for the items that came back.');
                    break;

                case 'cancelled':
                    if (config('shop.steadfast.cancel_order_when_cancelled')) {
                        $this->moveTo($order, 'cancelled', 'Steadfast cancelled the parcel (the customer did not take it).');
                    } else {
                        $this->note($order, 'Steadfast cancelled the parcel (the customer may not have taken it). When it is back with you, set the order to Cancelled so the items go back in stock.');
                    }
                    break;
            }

            DB::afterCommit(fn () => event(new OrderNotification('courier.status_changed', $order->fresh(), ['status' => $status])));
        });
    }

    /** A tracking step from the webhook, shown in the order's courier list. */
    public function addTracking(Order $order, string $message, array $payload = [], ?string $eventKey = null): void
    {
        CourierEvent::create([
            'order_id' => $order->id, 'notification_type' => 'tracking', 'status' => $order->courier_status,
            'message'  => Str::limit($message, 480, ''), 'payload' => $payload ?: null, 'event_key' => $eventKey,
        ]);
    }

    /** Money Steadfast holds for the shop. Cached for 5 minutes. */
    public function balance(): ?float
    {
        return Cache::remember('steadfast_balance_' . $this->courier->id, 300, function () {
            try {
                $json = $this->request('GET', '/get_balance');
            } catch (\Throwable $e) {
                return null;
            }

            return isset($json['current_balance']) ? (float) $json['current_balance'] : null;
        }) ;
    }

    /* ------------------------------ internals ------------------------------ */

    /** @throws OrderException */
    private function request(string $method, string $path, array $body = []): array
    {
        $creds = $this->courier->credentials ?? [];

        try {
            $http = Http::withHeaders([
                'Api-Key' => $creds['api_key'] ?? '', 'Secret-Key' => $creds['secret_key'] ?? '',
                'Content-Type' => 'application/json', 'Accept' => 'application/json',
            ])->timeout(30);

            $response = $method === 'GET' ? $http->get(self::BASE . $path) : $http->post(self::BASE . $path, $body);
        } catch (\Throwable $e) {
            Log::error('Steadfast: could not reach the API', ['path' => $path, 'error' => $e->getMessage()]);
            throw new OrderException('Could not reach Steadfast. Please try again in a moment.');
        }

        if ($response->status() === 401 || $response->status() === 403) {
            throw new OrderException('Steadfast refused the API key or secret key. Check them under Courier services.');
        }

        return $response->json() ?? [];
    }

    /** One readable sentence out of a Steadfast error reply. */
    private function reason(array $json): string
    {
        if (! empty($json['errors']) && is_array($json['errors'])) {
            return collect($json['errors'])->flatten()->take(3)->implode(' ');
        }

        return (string) ($json['message'] ?? 'unknown reason');
    }

    /** 8801712345678, +8801712345678, 1712345678 -> 01712345678. Null when it is not a mobile number. */
    private function phone(?string $value): ?string
    {
        $d = preg_replace('/\D+/', '', (string) $value);

        if (str_starts_with($d, '880')) {
            $d = substr($d, 2);
        }
        if (strlen($d) === 10 && str_starts_with($d, '1')) {
            $d = '0' . $d;
        }

        return preg_match('/^01\d{9}$/', $d) ? $d : null;
    }

    /** Steadfast does not accept { } ; < > $ in text and limits the length. */
    private function clean(?string $text, int $max): string
    {
        return Str::limit(trim(preg_replace('/[{};<>$]/', '', (string) $text)), $max, '');
    }

    private function moveTo(Order $order, string $slug, string $note, ?User $actor = null): bool
    {
        $to = OrderStatus::active()->where('slug', $slug)->first();
        $order = Order::with('status')->find($order->id);

        if (! $to || ! $order || $order->status->is_final || $order->order_status_id === $to->id) {
            return false;
        }

        try {
            app(OrderService::class)->changeStatus($order, $to, $note, $actor);

            return true;
        } catch (OrderException $e) {
            Log::warning('Steadfast: could not move the order', ['order' => $order->order_number, 'to' => $slug, 'reason' => $e->getMessage()]);

            return false;
        }
    }

    /** A line in the order timeline without changing its status. */
    private function note(Order $order, string $text): void
    {
        $order->histories()->create(['order_status_id' => $order->order_status_id, 'user_id' => null, 'note' => $text, 'notified' => false]);
    }
}

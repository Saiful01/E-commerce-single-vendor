<?php

namespace App\Support;

use App\Models\Order;
use App\Models\PaymentGateway;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

/**
 * The payment methods where the customer leaves the shop to pay (SSLCommerz, bKash API, Nagad API).
 * One place to ask "is this an online method?" so checkout, order page and jobs agree.
 */
class OnlineGateways
{
    public const DRIVERS = ['sslcommerz' => 'SSLCommerz', 'bkash' => 'bKash', 'nagad' => 'Nagad'];

    public static function isOnline(?string $driver): bool
    {
        return $driver !== null && array_key_exists($driver, self::DRIVERS);
    }

    public static function label(?string $driver): string
    {
        return self::DRIVERS[$driver] ?? (string) $driver;
    }

    /** What the customer reads at checkout under the method. */
    public static function note(?string $driver): string
    {
        return match ($driver) {
            'sslcommerz' => 'After you place the order you will be taken to SSLCommerz to pay safely with card, bKash, Nagad, Rocket or internet banking.',
            'bkash'      => 'After you place the order you will be taken to bKash to pay with your bKash account.',
            'nagad'      => 'After you place the order you will be taken to Nagad to pay with your Nagad account.',
            default      => '',
        };
    }

    /** Switched-on online methods that have all their credentials filled in. */
    public static function activeGateways(): Collection
    {
        return PaymentGateway::active()->whereIn('driver', array_keys(self::DRIVERS))->ordered()->get()->filter->isConfigured()->values();
    }

    /**
     * The address bKash / Nagad send the customer back to.
     * Normally the shop's own address. When PAYMENT_CALLBACK_BASE is set (testing only), that address is used instead.
     */
    public static function callbackUrl(string $route, array $params = []): string
    {
        $base = rtrim((string) config('shop.payment_callback_base'), '/');

        return $base !== '' ? $base . route($route, $params, false) : route($route, $params);
    }

    /** Signed link that sends the customer to the gateway to pay what is still due. */
    public static function startUrl(Order $order, ?string $driver = null, int $minutes = 30): string
    {
        $driver ??= $order->paymentGateway?->driver;

        return URL::temporarySignedRoute("payment.{$driver}.pay", now()->addMinutes($minutes), ['number' => $order->order_number]);
    }
}

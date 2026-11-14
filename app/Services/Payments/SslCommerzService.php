<?php

namespace App\Services\Payments;

use App\Exceptions\OrderException;
use App\Models\OnlinePayment;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\SiteSetting;
use App\Services\OrderPaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * SSLCommerz (hosted checkout, API v4).
 * The Store ID, Store Password and sandbox / live switch come from the admin panel
 * (Payment gateways > SSLCommerz), nothing is kept in .env.
 *
 *   start()    -> asks SSLCommerz for a payment page and returns its URL
 *   handle()   -> called by the browser return and the IPN. Never trusts what the browser says:
 *                 the payment is checked again with SSLCommerz' validation API.
 */
class SslCommerzService
{
    public function __construct(private PaymentGateway $gateway)
    {
    }

    /** The switched-on SSLCommerz method that has its credentials filled in. */
    public static function activeGateway(): ?PaymentGateway
    {
        return PaymentGateway::active()->where('driver', 'sslcommerz')->get()->first(fn ($g) => $g->isConfigured());
    }

    /** @throws OrderException */
    public static function make(): self
    {
        return new self(static::activeGateway() ?? throw new OrderException('Online payment is not available right now. Please choose another method.'));
    }

    /* ---------------------------------------------------------------
     |  START A PAYMENT
     * --------------------------------------------------------------*/

    /**
     * Pays what is still due on the order. Returns the SSLCommerz page the customer must be sent to.
     *
     * @throws OrderException
     */
    public function start(Order $order): string
    {
        $order->loadMissing(['status', 'items']);

        if ($order->status->restock_items) {
            throw new OrderException('This order is cancelled, so it cannot be paid.');
        }

        $amount = round($order->due_amount, 2);
        if ($amount <= 0) {
            throw new OrderException('Nothing is due on this order.');
        }
        if ($amount < 10) {
            throw new OrderException('Online payment needs at least 10 in one go. Please pay the small rest by cash or send money.');
        }

        $currency = strtoupper(SiteSetting::current()->currency_code ?: 'BDT');

        $attempt = OnlinePayment::create([
            'order_id'           => $order->id,
            'payment_gateway_id' => $this->gateway->id,
            'tran_id'            => $order->order_number . '-' . Str::upper(Str::random(6)),   // SSLCommerz allows 30 characters
            'amount'             => $amount,
            'currency'           => $currency,
            'status'             => 'initiated',
        ]);

        try {
            $response = Http::asForm()->timeout(30)->post($this->base() . '/gwprocess/v4/api.php', $this->payload($order, $attempt));
            $json     = $response->json() ?? [];
        } catch (\Throwable $e) {
            $attempt->update(['status' => 'failed', 'response' => ['error' => $e->getMessage()]]);
            Log::error('SSLCommerz: could not reach the gateway', ['order' => $order->order_number, 'error' => $e->getMessage()]);

            throw new OrderException('We could not reach the payment service. Please try again in a moment.');
        }

        if (($json['status'] ?? '') !== 'SUCCESS' || empty($json['GatewayPageURL'])) {
            $attempt->update(['status' => 'failed', 'response' => $json]);
            // the real reason (wrong store id, wrong password ...) is for the shop owner, not the customer
            Log::warning('SSLCommerz refused the payment request', ['order' => $order->order_number, 'reason' => $json['failedreason'] ?? $json]);

            throw new OrderException('The payment service did not accept this payment. Please try again or choose another method.');
        }

        return $json['GatewayPageURL'];
    }

    /* ---------------------------------------------------------------
     |  RETURN FROM SSLCOMMERZ (success / fail / cancel / IPN)
     * --------------------------------------------------------------*/

    /** $input is whatever SSLCommerz posted. Safe to call many times for the same payment. */
    public function handle(OnlinePayment $attempt, array $input): OnlinePayment
    {
        $status = strtoupper((string) ($input['status'] ?? ''));

        if (in_array($status, ['VALID', 'VALIDATED'], true)) {
            return $this->settle($attempt, (string) ($input['val_id'] ?? ''), $input);
        }

        if ($attempt->status === 'initiated') {
            $attempt->update([
                'status'   => $status === 'CANCELLED' ? 'cancelled' : 'failed',
                'response' => $input,
            ]);
        }

        return $attempt->fresh();
    }

    private function settle(OnlinePayment $attempt, string $valId, array $input): OnlinePayment
    {
        return DB::transaction(function () use ($attempt, $valId, $input) {
            $attempt = OnlinePayment::whereKey($attempt->id)->lockForUpdate()->firstOrFail();

            // already handled, for example the IPN came after the browser return
            if (in_array($attempt->status, ['paid', 'review'], true)) {
                return $attempt;
            }
            if ($valId === '') {
                $attempt->update(['status' => 'failed', 'response' => $input]);
                return $attempt;
            }

            // Ask SSLCommerz again. The browser could have been tampered with, this answer cannot.
            $v = $this->validateWithGateway($valId);

            $paidStatus = in_array(strtoupper((string) ($v['status'] ?? '')), ['VALID', 'VALIDATED'], true);
            $sameTran   = ($v['tran_id'] ?? '') === $attempt->tran_id;
            $sameAmount = abs((float) ($v['currency_amount'] ?? $v['amount'] ?? 0) - $attempt->amount) < 0.01;

            if (! $paidStatus || ! $sameTran || ! $sameAmount) {
                $attempt->update(['status' => 'failed', 'val_id' => $valId, 'response' => $v]);
                Log::warning('SSLCommerz validation did not match', ['tran_id' => $attempt->tran_id, 'validation' => $v]);

                return $attempt;
            }

            $order  = Order::findOrFail($attempt->order_id);
            $risky  = (string) ($v['risk_level'] ?? '0') === '1';
            $card   = $v['card_type'] ?? null;
            $note   = 'SSLCommerz' . ($card ? " ({$card})" : '') . ($risky ? '. Flagged as risky by SSLCommerz, check it before sending the goods.' : '');
            $update = ['val_id' => $valId, 'bank_tran_id' => $v['bank_tran_id'] ?? null, 'card_type' => $card, 'response' => $v];

            try {
                // A risky payment is only a claim until an admin confirms it
                app(OrderPaymentService::class)->record(
                    $order, $attempt->amount, $attempt->payment_gateway_id, $v['bank_tran_id'] ?? $valId, $note, null, ! $risky, 'gateway'
                );
                $attempt->update($update + ['status' => $risky ? 'review' : 'paid']);
            } catch (OrderException $e) {
                // The money arrived but the order cannot take it (cancelled meanwhile, already paid ...). Never lose that silently.
                $attempt->update($update + ['status' => 'review']);
                $order->update(['admin_note' => trim(($order->admin_note ? $order->admin_note . "\n" : '')
                    . "SSLCommerz payment {$attempt->tran_id} of {$attempt->amount} was received but could not be added: {$e->getMessage()}")]);
                Log::error('SSLCommerz money received but not applied', ['tran_id' => $attempt->tran_id, 'reason' => $e->getMessage()]);
            }

            return $attempt;
        });
    }

    /** @throws OrderException */
    private function validateWithGateway(string $valId): array
    {
        $creds = $this->gateway->credentials ?? [];

        try {
            $json = Http::timeout(30)->get($this->base() . '/validator/api/validationserverAPI.php', [
                'val_id' => $valId, 'store_id' => $creds['store_id'] ?? '', 'store_passwd' => $creds['store_password'] ?? '', 'format' => 'json',
            ])->json();
        } catch (\Throwable $e) {
            Log::error('SSLCommerz validation call failed', ['error' => $e->getMessage()]);
            throw new OrderException('We could not confirm the payment with the gateway yet.');
        }

        return is_array($json) ? $json : [];
    }

    /* ------------------------------ internals ------------------------------ */

    private function base(): string
    {
        return $this->gateway->sandbox_mode ? 'https://sandbox.sslcommerz.com' : 'https://securepay.sslcommerz.com';
    }

    private function payload(Order $order, OnlinePayment $attempt): array
    {
        $creds = $this->gateway->credentials ?? [];
        $host  = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'example.com';

        return [
            'store_id'     => $creds['store_id'] ?? '',
            'store_passwd' => $creds['store_password'] ?? '',
            'total_amount' => number_format($attempt->amount, 2, '.', ''),
            'currency'     => $attempt->currency,
            'tran_id'      => $attempt->tran_id,

            'success_url'  => route('payment.sslcommerz.success'),
            'fail_url'     => route('payment.sslcommerz.fail'),
            'cancel_url'   => route('payment.sslcommerz.cancel'),
            'ipn_url'      => route('payment.sslcommerz.ipn'),

            'cus_name'     => Str::limit($order->customer_name, 50, ''),
            'cus_email'    => $order->customer_email ?: 'noreply@' . $host,     // SSLCommerz insists on an email
            'cus_add1'     => Str::limit($order->shipping_address, 50, ''),
            'cus_city'     => Str::limit($order->shipping_city, 50, ''),
            'cus_postcode' => '1000',
            'cus_country'  => 'Bangladesh',
            'cus_phone'    => $order->customer_phone,

            'shipping_method'  => 'Courier',
            'num_of_item'      => max((int) $order->items->sum('quantity'), 1),
            'product_name'     => 'Order ' . $order->order_number,
            'product_category' => 'General',
            'product_profile'  => 'physical-goods',

            'ship_name'     => Str::limit($order->shipping_name, 50, ''),
            'ship_add1'     => Str::limit($order->shipping_address, 50, ''),
            'ship_city'     => Str::limit($order->shipping_city, 50, ''),
            'ship_postcode' => '1000',
            'ship_country'  => 'Bangladesh',

            'value_a' => $order->order_number,
        ];
    }
}

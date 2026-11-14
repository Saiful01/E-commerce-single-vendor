<?php

namespace App\Services\Payments;

use App\Exceptions\OrderException;
use App\Models\OnlinePayment;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Services\Payments\Concerns\RecordsOnlinePayment;
use App\Support\OnlineGateways;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * bKash Tokenized Checkout (v1.2.0-beta), one-time payment (mode 0011).
 * App key, app secret, username, password and the sandbox / live switch all come from the admin panel.
 *
 *   start()          grant token -> create payment -> returns the bKash page for the customer
 *   handleCallback() bKash sends the customer back with ?paymentID=&status=. The payment is executed
 *                    (that is the moment the money moves) and checked against what we asked for.
 *                    Called with an empty $input it only asks bKash for the payment status (used by the reconcile job).
 */
class BkashService
{
    use RecordsOnlinePayment;

    public function __construct(private PaymentGateway $gateway)
    {
    }

    public static function activeGateway(): ?PaymentGateway
    {
        return PaymentGateway::active()->where('driver', 'bkash')->get()->first(fn ($g) => $g->isConfigured());
    }

    /** @throws OrderException */
    public static function make(): self
    {
        return new self(static::activeGateway() ?? throw new OrderException('bKash payment is not available right now. Please choose another method.'));
    }

    /* ------------------------------ start ------------------------------ */

    /** @throws OrderException */
    public function start(Order $order): string
    {
        $order->loadMissing('status');

        if ($order->status->restock_items) {
            throw new OrderException('This order is cancelled, so it cannot be paid.');
        }

        $amount = round($order->due_amount, 2);
        if ($amount < 1) {
            throw new OrderException('Nothing is due on this order.');
        }

        $attempt = OnlinePayment::create([
            'order_id'           => $order->id,
            'payment_gateway_id' => $this->gateway->id,
            'tran_id'            => 'BK' . now()->format('ymd') . Str::upper(Str::random(8)),
            'amount'             => $amount,
            'currency'           => 'BDT',
            'status'             => 'initiated',
        ]);

        $callback = OnlineGateways::callbackUrl('payment.bkash.callback', ['tran' => $attempt->tran_id]);

        try {
            $json = $this->call('/checkout/create', [
                'mode'                  => '0011',
                'payerReference'        => $order->customer_phone ?: $order->order_number,
                'callbackURL'           => $callback,
                'amount'                => number_format($amount, 2, '.', ''),
                'currency'              => 'BDT',
                'intent'                => 'sale',
                'merchantInvoiceNumber' => $attempt->tran_id,
            ]);
        } catch (\Throwable $e) {
            $this->failAttempt($attempt, 'failed', ['error' => $e->getMessage()]);
            Log::error('bKash: could not create the payment', ['order' => $order->order_number, 'error' => $e->getMessage()]);

            throw new OrderException('We could not reach bKash. Please try again in a moment.');
        }

        if (($json['statusCode'] ?? '') !== '0000' || empty($json['bkashURL']) || empty($json['paymentID'])) {
            $this->failAttempt($attempt, 'failed', $json);
            // the real reason (wrong app key, wrong password ...) is for the shop owner, not the customer
            Log::warning('bKash refused the payment request', ['order' => $order->order_number, 'callbackURL' => $callback, 'response' => $json]);

            throw new OrderException('bKash did not accept this payment. Please try again or choose another method.');
        }

        $attempt->update(['provider_ref' => $json['paymentID']]);

        return $json['bkashURL'];
    }

    /* ------------------------------ callback / reconcile ------------------------------ */

    public function handleCallback(OnlinePayment $attempt, array $input): OnlinePayment
    {
        return DB::transaction(function () use ($attempt, $input) {
            $attempt = OnlinePayment::whereKey($attempt->id)->lockForUpdate()->firstOrFail();

            if (in_array($attempt->status, ['paid', 'review'], true)) {
                return $attempt;            // already done, for example the customer reloaded the return page
            }

            $status    = strtolower((string) ($input['status'] ?? ''));
            $paymentId = (string) ($input['paymentID'] ?? $attempt->provider_ref);

            if ($attempt->provider_ref && $paymentId !== $attempt->provider_ref) {
                return $this->failAttempt($attempt, 'failed', $input + ['note' => 'paymentID did not match']);
            }
            if (in_array($status, ['failure', 'cancel'], true)) {
                return $this->failAttempt($attempt, $status === 'cancel' ? 'cancelled' : 'failed', $input);
            }
            if ($paymentId === '') {
                return $attempt;
            }

            try {
                $result = $status === 'success' ? $this->call('/checkout/execute', ['paymentID' => $paymentId]) : null;

                // Execute fails when it was already executed (callback hit twice) and is skipped on a reconcile run:
                // in both cases ask bKash what happened to the payment
                if (! $this->completed($result)) {
                    $result = $this->call('/checkout/payment/status', ['paymentID' => $paymentId]);
                }
            } catch (\Throwable $e) {
                Log::error('bKash: could not confirm the payment', ['tran_id' => $attempt->tran_id, 'error' => $e->getMessage()]);

                return $attempt;            // stays "initiated", the reconcile job tries again
            }

            if (! $this->completed($result)) {
                return $status === 'success' ? $this->failAttempt($attempt, 'failed', $result ?? []) : $attempt;
            }

            $sameInvoice = ($result['merchantInvoiceNumber'] ?? '') === $attempt->tran_id;
            $sameAmount  = abs((float) ($result['amount'] ?? 0) - $attempt->amount) < 0.01;

            if (! $sameInvoice || ! $sameAmount) {
                Log::warning('bKash result did not match the order', ['tran_id' => $attempt->tran_id, 'result' => $result]);

                return $this->failAttempt($attempt, 'failed', $result);
            }

            return $this->recordPaid($attempt, ['bank_tran_id' => $result['trxID'] ?? null, 'response' => $result], $result['trxID'] ?? $paymentId, 'bKash payment');
        });
    }

    /* ------------------------------ internals ------------------------------ */

    private function completed(?array $r): bool
    {
        return $r !== null && ($r['statusCode'] ?? '') === '0000' && ($r['transactionStatus'] ?? '') === 'Completed';
    }

    private function creds(): array
    {
        return $this->gateway->credentials ?? [];
    }

    private function base(): string
    {
        return $this->gateway->sandbox_mode
            ? 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized'
            : 'https://tokenized.pay.bka.sh/v1.2.0-beta/tokenized';
    }

    /** One call to bKash with the access token. */
    private function call(string $path, array $body): array
    {
        $response = Http::withHeaders(['Authorization' => $this->token(), 'X-APP-Key' => $this->creds()['app_key'] ?? ''])
            ->acceptJson()->timeout(30)->post($this->base() . $path, $body);

        return $response->json() ?? [];
    }

    /** The access token lasts an hour, so it is kept for 50 minutes instead of asking for a new one every time. */
    private function token(): string
    {
        $c   = $this->creds();
        $key = 'bkash_token_' . md5($this->base() . ($c['app_key'] ?? '') . ($c['username'] ?? ''));

        return Cache::remember($key, 3000, function () use ($c) {
            $json = Http::withHeaders(['username' => $c['username'] ?? '', 'password' => $c['password'] ?? ''])
                ->acceptJson()->timeout(30)
                ->post($this->base() . '/checkout/token/grant', ['app_key' => $c['app_key'] ?? '', 'app_secret' => $c['app_secret'] ?? ''])
                ->json();

            if (empty($json['id_token'])) {
                Log::warning('bKash refused the credentials', ['response' => $json]);
                throw new \RuntimeException('bKash token request failed: ' . ($json['statusMessage'] ?? 'unknown reason'));
            }

            return $json['id_token'];
        });
    }
}

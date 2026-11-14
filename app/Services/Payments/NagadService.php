<?php

namespace App\Services\Payments;

use App\Exceptions\OrderException;
use App\Models\OnlinePayment;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Services\Payments\Concerns\RecordsOnlinePayment;
use App\Support\OnlineGateways;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Nagad payment gateway (API v-0.2.0).
 * Merchant ID, merchant number, Nagad's public key, the merchant's own private key and the
 * sandbox / live switch all come from the admin panel.
 *
 *   start()          initialize -> complete -> returns the Nagad page for the customer
 *   handleCallback() Nagad sends the customer back with ?payment_ref_id=&status=. Whatever it says,
 *                    the payment is asked from Nagad again (verify API) before it is trusted.
 *                    Called with an empty $input it only verifies (used by the reconcile job).
 */
class NagadService
{
    use RecordsOnlinePayment;

    public function __construct(private PaymentGateway $gateway)
    {
    }

    public static function activeGateway(): ?PaymentGateway
    {
        return PaymentGateway::active()->where('driver', 'nagad')->get()->first(fn ($g) => $g->isConfigured());
    }

    /** @throws OrderException */
    public static function make(): self
    {
        return new self(static::activeGateway() ?? throw new OrderException('Nagad payment is not available right now. Please choose another method.'));
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

        $merchantId = (string) $this->c('merchant_id');
        $attempt = OnlinePayment::create([
            'order_id'           => $order->id,
            'payment_gateway_id' => $this->gateway->id,
            'tran_id'            => 'N' . now()->format('ymd') . Str::upper(Str::random(8)),   // Nagad order id: letters and numbers only
            'amount'             => $amount,
            'currency'           => 'BDT',
            'status'             => 'initiated',
        ]);

        try {
            // 1) Initialize: ask Nagad for a payment reference and a challenge
            $dateTime  = now('Asia/Dhaka')->format('YmdHis');
            $sensitive = json_encode([
                'merchantId' => $merchantId, 'datetime' => $dateTime, 'orderId' => $attempt->tran_id, 'challenge' => Str::random(40),
            ]);

            $init = $this->post("/check-out/initialize/{$merchantId}/{$attempt->tran_id}", [
                'accountNumber' => (string) $this->c('merchant_number'),
                'dateTime'      => $dateTime,
                'sensitiveData' => $this->encrypt($sensitive),
                'signature'     => $this->sign($sensitive),
            ]);

            if (empty($init['sensitiveData'])) {
                throw new \RuntimeException('Initialize failed: ' . json_encode($init));
            }

            $reply = json_decode($this->decrypt($init['sensitiveData']), true);
            if (empty($reply['paymentReferenceId']) || empty($reply['challenge'])) {
                throw new \RuntimeException('Initialize reply had no payment reference.');
            }

            // 2) Complete: say how much, get the page the customer pays on
            $sensitive = json_encode([
                'merchantId' => $merchantId, 'orderId' => $attempt->tran_id, 'currencyCode' => '050',
                'amount' => number_format($amount, 2, '.', ''), 'challenge' => $reply['challenge'],
            ]);

            $complete = $this->post("/check-out/complete/{$reply['paymentReferenceId']}", [
                'sensitiveData'         => $this->encrypt($sensitive),
                'signature'             => $this->sign($sensitive),
                'merchantCallbackURL'   => OnlineGateways::callbackUrl('payment.nagad.callback', ['tran' => $attempt->tran_id]),
                'additionalMerchantInfo' => ['order' => $order->order_number],
            ]);

            if (($complete['status'] ?? '') !== 'Success' || empty($complete['callBackUrl'])) {
                throw new \RuntimeException('Complete failed: ' . json_encode($complete));
            }
        } catch (\Throwable $e) {
            $this->failAttempt($attempt, 'failed', ['error' => $e->getMessage()]);
            // the real reason (wrong merchant id, bad key ...) is for the shop owner, not the customer
            Log::error('Nagad: could not start the payment', ['order' => $order->order_number, 'error' => $e->getMessage()]);

            throw new OrderException('Nagad did not accept this payment. Please try again or choose another method.');
        }

        $attempt->update(['provider_ref' => $reply['paymentReferenceId']]);

        return $complete['callBackUrl'];
    }

    /* ------------------------------ callback / reconcile ------------------------------ */

    public function handleCallback(OnlinePayment $attempt, array $input): OnlinePayment
    {
        return DB::transaction(function () use ($attempt, $input) {
            $attempt = OnlinePayment::whereKey($attempt->id)->lockForUpdate()->firstOrFail();

            if (in_array($attempt->status, ['paid', 'review'], true)) {
                return $attempt;
            }

            $ref = $attempt->provider_ref ?: (string) ($input['payment_ref_id'] ?? '');
            if ($ref === '') {
                return $attempt;
            }

            try {
                $verify = $this->get("/verify/payment/{$ref}");
            } catch (\Throwable $e) {
                Log::error('Nagad: could not verify the payment', ['tran_id' => $attempt->tran_id, 'error' => $e->getMessage()]);

                return $attempt;            // stays "initiated", the reconcile job tries again
            }

            $paid = ($verify['status'] ?? '') === 'Success';

            if (! $paid) {
                // Only a clear "not paid" from the callback closes the attempt. An empty / unclear answer waits.
                $said = strtolower((string) ($input['status'] ?? ''));

                return in_array($said, ['aborted', 'cancelled', 'canceled', 'failed', 'fraud', 'expired'], true)
                    ? $this->failAttempt($attempt, in_array($said, ['aborted', 'cancelled', 'canceled'], true) ? 'cancelled' : 'failed', $verify + ['callback' => $input])
                    : $attempt;
            }

            $sameOrder  = ($verify['orderId'] ?? '') === $attempt->tran_id;
            $sameAmount = abs((float) ($verify['amount'] ?? 0) - $attempt->amount) < 0.01;

            if (! $sameOrder || ! $sameAmount) {
                Log::warning('Nagad result did not match the order', ['tran_id' => $attempt->tran_id, 'verify' => $verify]);

                return $this->failAttempt($attempt, 'failed', $verify);
            }

            return $this->recordPaid(
                $attempt, ['bank_tran_id' => $verify['issuerPaymentRefNo'] ?? null, 'response' => $verify],
                $verify['issuerPaymentRefNo'] ?? $ref, 'Nagad payment'
            );
        });
    }

    /* ------------------------------ internals ------------------------------ */

    private function c(string $key): ?string
    {
        return ($this->gateway->credentials ?? [])[$key] ?? null;
    }

    private function base(): string
    {
        return $this->gateway->sandbox_mode
            ? 'http://sandbox.mynagad.com:10080/remote-payment-gateway-1.0/api/dfs'
            : 'https://api.mynagad.com/api/dfs';
    }

    private function headers(): array
    {
        $ip = request()->ip();

        return [
            'X-KM-IP-V4'       => filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? $ip : '127.0.0.1',
            'X-KM-MC-Id'       => (string) $this->c('merchant_id'),
            'X-KM-Client-Type' => 'PC_WEB',
            'X-KM-Api-Version' => 'v-0.2.0',
            'Content-Type'     => 'application/json',
            'Accept'           => 'application/json',
        ];
    }

    private function post(string $path, array $body): array
    {
        return Http::withHeaders($this->headers())->timeout(30)->post($this->base() . $path, $body)->json() ?? [];
    }

    private function get(string $path): array
    {
        return Http::withHeaders($this->headers())->timeout(30)->get($this->base() . $path)->json() ?? [];
    }

    /* ----- RSA helpers: keys can be pasted with or without the BEGIN / END lines, on one line or many ----- */

    private function pem(string $key, string $type): string
    {
        $body = preg_replace('/-----(BEGIN|END) [A-Z ]+-----|\s+/', '', $key);

        return "-----BEGIN {$type}-----\n" . chunk_split($body, 64, "\n") . "-----END {$type}-----\n";
    }

    private function publicKey()
    {
        return openssl_pkey_get_public($this->pem((string) $this->c('public_key'), 'PUBLIC KEY'))
            ?: throw new \RuntimeException('Nagad public key could not be read.');
    }

    private function privateKey()
    {
        $raw = (string) $this->c('private_key');

        return openssl_pkey_get_private($this->pem($raw, 'PRIVATE KEY'))
            ?: openssl_pkey_get_private($this->pem($raw, 'RSA PRIVATE KEY'))
            ?: throw new \RuntimeException('Merchant private key could not be read.');
    }

    private function encrypt(string $plain): string
    {
        if (! openssl_public_encrypt($plain, $encrypted, $this->publicKey(), OPENSSL_PKCS1_PADDING)) {
            throw new \RuntimeException('Could not encrypt for Nagad.');
        }

        return base64_encode($encrypted);
    }

    private function decrypt(string $encoded): string
    {
        if (! openssl_private_decrypt(base64_decode($encoded), $plain, $this->privateKey(), OPENSSL_PKCS1_PADDING)) {
            throw new \RuntimeException('Could not read the Nagad reply. Check the private key.');
        }

        return $plain;
    }

    private function sign(string $plain): string
    {
        if (! openssl_sign($plain, $signature, $this->privateKey(), OPENSSL_ALGO_SHA256)) {
            throw new \RuntimeException('Could not sign the request.');
        }

        return base64_encode($signature);
    }
}

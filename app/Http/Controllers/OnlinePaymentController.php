<?php

namespace App\Http\Controllers;

use App\Exceptions\OrderException;
use App\Models\OnlinePayment;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Services\Payments\BkashService;
use App\Services\Payments\NagadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

/** bKash and Nagad: sending the customer off to pay, and the return from the gateway. */
class OnlinePaymentController extends Controller
{
    /**
     * Allowed with a signed link (checkout, order page) or for the logged-in owner of the order.
     * $provider comes from the route (bkash | nagad).
     */
    public function pay(Request $request, string $number, string $provider)
    {
        $order = Order::where('order_number', $number)->firstOrFail();

        $owner = $request->user() && $request->user()->id === $order->user_id;
        abort_unless($request->hasValidSignature() || $owner, 403);

        try {
            $url = $this->service($provider)->start($order);
        } catch (OrderException $e) {
            return redirect()->to($this->back($order, 'error'))->with('error', $e->getMessage());
        }

        return redirect()->away($url);
    }

    /** The customer comes back from the gateway. Nothing the browser says is trusted, the service re-checks with the gateway. */
    public function callback(Request $request, string $tran, string $provider)
    {
        $attempt = OnlinePayment::with(['gateway', 'order'])->where('tran_id', $tran)->first();
        abort_unless($attempt && $attempt->gateway && $attempt->gateway->driver === $provider, 404);

        try {
            $attempt = $this->service($provider, $attempt->gateway)->handleCallback($attempt, $request->query());
        } catch (\Throwable $e) {
            Log::error('Online payment return failed', ['provider' => $provider, 'tran_id' => $tran, 'error' => $e->getMessage()]);
        }

        $flag = ['paid' => 'paid', 'review' => 'review', 'cancelled' => 'cancelled'][$attempt->fresh()->status] ?? 'failed';

        return redirect()->to($this->back($attempt->order, $flag));
    }

    /* ------------------------------ internals ------------------------------ */

    private function service(string $provider, ?PaymentGateway $gateway = null): BkashService|NagadService
    {
        return match ($provider) {
            'bkash' => $gateway ? new BkashService($gateway) : BkashService::make(),
            'nagad' => $gateway ? new NagadService($gateway) : NagadService::make(),
            default => abort(404),
        };
    }

    private function back(Order $order, string $flag): string
    {
        return URL::temporarySignedRoute('order.success', now()->addHours(3), ['number' => $order->order_number, 'payment' => $flag]);
    }
}

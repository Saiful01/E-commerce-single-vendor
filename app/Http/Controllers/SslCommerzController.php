<?php

namespace App\Http\Controllers;

use App\Exceptions\OrderException;
use App\Models\OnlinePayment;
use App\Models\Order;
use App\Services\Payments\SslCommerzService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class SslCommerzController extends Controller
{
    /**
     * Send the customer to SSLCommerz to pay what is due.
     * Allowed with a signed link (checkout, order page) or for the logged-in owner of the order.
     */
    public function pay(Request $request, string $number)
    {
        $order = Order::where('order_number', $number)->firstOrFail();

        $owner = $request->user() && $request->user()->id === $order->user_id;
        abort_unless($request->hasValidSignature() || $owner, 403);

        try {
            $url = SslCommerzService::make()->start($order);
        } catch (OrderException $e) {
            return redirect()->to($this->back($order, 'error'))->with('error', $e->getMessage());
        }

        return redirect()->away($url);
    }

    public function success(Request $request) { return $this->finish($request, 'VALID'); }
    public function fail(Request $request)    { return $this->finish($request, 'FAILED'); }
    public function cancel(Request $request)  { return $this->finish($request, 'CANCELLED'); }

    /** Server to server call from SSLCommerz. Same checks as the browser return, answer is just "OK". */
    public function ipn(Request $request)
    {
        $attempt = OnlinePayment::with('gateway')->where('tran_id', (string) $request->input('tran_id'))->first();

        if ($attempt?->gateway) {
            try {
                (new SslCommerzService($attempt->gateway))->handle($attempt, $request->all());
            } catch (\Throwable $e) {
                Log::error('SSLCommerz IPN failed', ['tran_id' => $attempt->tran_id, 'error' => $e->getMessage()]);
            }
        }

        return response('OK');
    }

    /* ------------------------------ internals ------------------------------ */

    private function finish(Request $request, string $defaultStatus)
    {
        $attempt = OnlinePayment::with(['gateway', 'order'])->where('tran_id', (string) $request->input('tran_id'))->first();
        abort_unless($attempt && $attempt->gateway, 404);

        try {
            // a plain return to the "success" url without a status is treated as VALID and then checked with the gateway
            $attempt = (new SslCommerzService($attempt->gateway))->handle($attempt, $request->all() + ['status' => $defaultStatus]);
        } catch (\Throwable $e) {
            Log::error('SSLCommerz return failed', ['tran_id' => $attempt->tran_id, 'error' => $e->getMessage()]);
        }

        $flag = ['paid' => 'paid', 'review' => 'review', 'cancelled' => 'cancelled'][$attempt->fresh()->status] ?? 'failed';

        return redirect()->to($this->back($attempt->order, $flag));
    }

    /** The order page, as a signed link so it opens even if the browser dropped the session on the way back. */
    private function back(Order $order, string $flag): string
    {
        return URL::temporarySignedRoute('order.success', now()->addHours(3), ['number' => $order->order_number, 'payment' => $flag]);
    }
}

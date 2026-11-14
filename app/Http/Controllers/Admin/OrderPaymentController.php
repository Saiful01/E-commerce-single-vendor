<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\OrderException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Services\OrderPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderPaymentController extends Controller
{
    public function __construct(private OrderPaymentService $payments)
    {
    }

    /** Add a payment. An order can be paid in as many parts as needed. */
    public function store(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'amount'             => ['required', 'numeric', 'min:0.01'],
            'payment_gateway_id' => ['nullable', 'integer', 'exists:payment_gateways,id'],
            'transaction_id'     => ['nullable', 'string', 'max:100'],
            'note'               => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->payments->record(
                $order, (float) $data['amount'], $data['payment_gateway_id'] ?? null,
                $data['transaction_id'] ?? null, $data['note'] ?? null, $request->user()
            );
        } catch (OrderException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Payment recorded.');
    }

    /** A customer said they paid. Check your bKash / bank, then confirm (the amount can be corrected). */
    public function confirm(Request $request, OrderPayment $payment): RedirectResponse
    {
        $data = $request->validate(['amount' => ['nullable', 'numeric', 'min:0.01']]);

        try {
            $this->payments->confirm($payment, isset($data['amount']) ? (float) $data['amount'] : null, $request->user());
        } catch (OrderException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Payment confirmed.');
    }

    public function reject(Request $request, OrderPayment $payment): RedirectResponse
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:300']]);

        try {
            $this->payments->reject($payment, $data['reason'] ?? null, $request->user());
        } catch (OrderException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Payment rejected.');
    }
}

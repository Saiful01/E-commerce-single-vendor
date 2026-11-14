<?php

namespace App\Http\Controllers;

use App\Exceptions\OrderException;
use App\Services\CartService;
use App\Services\OrderPaymentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountPaymentController extends Controller
{
    /**
     * The customer pays the remaining amount (all at once or in parts) and tells us the transaction id.
     * It stays "waiting for check" until an admin confirms it.
     */
    public function store(Request $request, string $number, OrderPaymentService $payments, CartService $cart)
    {
        $order = $request->user()->orders()->where('order_number', $number)->firstOrFail();

        $sendMoneyIds = $cart->gateways()->reject(fn ($g) => $g->driver === 'cod' || \App\Support\OnlineGateways::isOnline($g->driver))->pluck('id')->all();

        $data = $request->validate([
            'amount'             => ['required', 'numeric', 'min:1'],
            'payment_gateway_id' => ['required', 'integer', Rule::in($sendMoneyIds)],
            'transaction_id'     => ['required', 'string', 'max:100'],
        ], [
            'transaction_id.required'     => 'Enter the transaction ID so we can find your payment.',
            'payment_gateway_id.required' => 'Choose how you paid.',
        ]);

        try {
            $payments->record($order, (float) $data['amount'], $data['payment_gateway_id'], $data['transaction_id'], null, $request->user(), false, 'customer');
        } catch (OrderException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Thanks! We will check your payment and update the order.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Exceptions\OrderException;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Http\Request;

class AccountOrderController extends Controller
{
    public function index(Request $request)
    {
        $statusId = $request->query('status');

        return view('account.orders.index', [
            'orders'   => $request->user()->orders()->with(['status', 'items'])
                ->when($statusId, fn ($q) => $q->where('order_status_id', $statusId))
                ->latest()->paginate(8)->withQueryString(),
            'statuses' => OrderStatus::active()->ordered()->get(),
            'statusId' => $statusId,
        ]);
    }

    public function show(Request $request, string $number)
    {
        $order = $this->find($request, $number)->load(['status', 'items', 'histories.status', 'paymentGateway', 'courier', 'coupon']);

        return view('account.orders.show', [
            'order'     => $order,
            'canCancel' => $order->status->is_default && ! $order->status->is_final,
        ]);
    }

    public function cancel(Request $request, string $number, OrderService $orders)
    {
        $order = $this->find($request, $number)->load('status');

        if (! ($order->status->is_default && ! $order->status->is_final)) {
            return back()->with('error', 'This order is already being processed. Please contact us to cancel it.');
        }

        $to = OrderStatus::where('slug', 'cancelled')->first();
        if (! $to) {
            return back()->with('error', 'Cancelling is not available right now. Please contact us.');
        }

        try {
            $orders->changeStatus($order, $to, 'Cancelled by the customer.', $request->user());
        } catch (OrderException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Your order has been cancelled.');
    }

    public function reorder(Request $request, string $number, CartService $cart)
    {
        $order = $this->find($request, $number)->load('items');
        $added = 0;
        $skipped = [];

        foreach ($order->items as $item) {
            try {
                $cart->add((int) $item->product_id, $item->product_variant_id, (int) $item->quantity);
                $added++;
            } catch (OrderException $e) {
                $skipped[] = $item->name;
            }
        }

        if (! $added) {
            return back()->with('error', 'None of these items are available right now.');
        }

        $msg = "{$added} item(s) added to your cart.";
        if ($skipped) {
            $msg .= ' Not available: ' . implode(', ', $skipped) . '.';
        }

        return redirect()->route('cart')->with('success', $msg);
    }

    private function find(Request $request, string $number): Order
    {
        return $request->user()->orders()->where('order_number', $number)->firstOrFail();
    }
}

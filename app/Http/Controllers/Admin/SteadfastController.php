<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\OrderException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Couriers\SteadfastService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SteadfastController extends Controller
{
    /** Send one order to Steadfast. */
    public function book(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'delivery_type' => ['nullable', 'in:0,1'],
            'note'          => ['nullable', 'string', 'max:300'],
            'mark_shipped'  => ['nullable', 'boolean'],
        ]);

        try {
            $booked = SteadfastService::make()->book($order, [
                'delivery_type' => (int) ($data['delivery_type'] ?? 0),
                'note'          => $data['note'] ?? null,
                'mark_shipped'  => $request->boolean('mark_shipped'),
            ], $request->user());
        } catch (OrderException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', "Sent to Steadfast. Tracking code {$booked->tracking_code}.");
    }

    public function refresh(Order $order): RedirectResponse
    {
        try {
            $fresh = SteadfastService::make()->refresh($order);
        } catch (OrderException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Courier status: ' . $fresh->courier_status_label . '.');
    }

    /** Several orders at once, from the order list. Orders that cannot be sent are skipped and listed. */
    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate(['order_ids' => ['required', 'array', 'min:1', 'max:100'], 'order_ids.*' => ['integer']]);

        try {
            $service = SteadfastService::make();
        } catch (OrderException $e) {
            return back()->with('error', $e->getMessage());
        }

        $sent = 0;
        $problems = [];

        foreach (Order::with('status')->whereIn('id', $data['order_ids'])->orderBy('id')->get() as $order) {
            try {
                $service->book($order, [], $request->user());
                $sent++;
            } catch (OrderException $e) {
                $problems[] = "{$order->order_number}: {$e->getMessage()}";
            }
        }

        $redirect = back();
        if ($sent) {
            $redirect->with('success', "Sent {$sent} " . ($sent === 1 ? 'order' : 'orders') . ' to Steadfast.');
        }
        if ($problems) {
            $redirect->with('error', count($problems) . ' not sent. ' . implode(' ', array_slice($problems, 0, 3)) . (count($problems) > 3 ? ' ...' : ''));
        }

        return $redirect;
    }
}

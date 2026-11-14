<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class TrackOrderController extends Controller
{
    public function index(Request $request)
    {
        $number   = strtoupper(trim((string) $request->query('order')));
        $phone    = (string) $request->query('phone');
        $searched = $number !== '' && $phone !== '';
        $order    = null;

        if ($searched) {
            $last10 = fn ($p) => substr(preg_replace('/\D+/', '', (string) $p), -10);

            $found = Order::with(['status', 'items', 'histories.status', 'paymentGateway', 'courier'])
                ->where('order_number', $number)->first();

            if ($found && $last10($found->customer_phone) === $last10($phone)) {
                $order = $found;
            }
        }

        return view('orders.track', compact('order', 'searched', 'number', 'phone'));
    }
}

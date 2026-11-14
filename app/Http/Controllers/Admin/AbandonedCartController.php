<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AbandonedCart;
use App\Models\MessageTemplate;
use App\Models\SiteSetting;
use App\Services\MessageSender;
use Illuminate\Http\Request;
use Throwable;

class AbandonedCartController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->query('filter', 'open');

        $carts = AbandonedCart::with('user')
            ->when($filter === 'open', fn ($q) => $q->whereNull('recovered_at'))
            ->when($filter === 'recovered', fn ($q) => $q->whereNotNull('recovered_at'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'open' => AbandonedCart::open()->count(),
            'open_value' => AbandonedCart::open()->sum('total'),
            'recovered' => AbandonedCart::whereNotNull('recovered_at')->count(),
        ];

        return view('admin.abandoned-carts.index', compact('carts', 'stats', 'filter'));
    }

    public function show(AbandonedCart $abandonedCart)
    {
        $abandonedCart->load('user');

        return view('admin.abandoned-carts.show', ['cart' => $abandonedCart]);
    }

    public function remind(Request $request, AbandonedCart $abandonedCart, MessageSender $sender)
    {
        $data = $request->validate(['channel' => 'required|in:email,sms']);
        $channel = $data['channel'];

        $to = $channel === 'email' ? $abandonedCart->contact_email : $abandonedCart->contact_phone;

        if (! $to) {
            return back()->with('error', 'Ei customer-er ' . ($channel === 'email' ? 'email' : 'phone') . ' nai.');
        }

        $vars = [
            'customer_name' => $abandonedCart->customer_name,
            'cart_total' => number_format($abandonedCart->total, 2),
            'items_count' => $abandonedCart->items_count,
            'cart_url' => url('/cart'),
            'site_name' => SiteSetting::current()->site_name ?? config('app.name'),
        ];

        // Template slug: abandoned-cart-email / abandoned-cart-sms
        $template = MessageTemplate::findActive('abandoned-cart-' . $channel);

        if ($template) {
            $msg = $template->render($vars);
        } else {
            $msg = [
                'subject' => 'You left something in your cart',
                'body' => MessageTemplate::replace(
                    "Hi {customer_name}, you left {items_count} item(s) worth {cart_total} in your cart at {site_name}. Complete your order: {cart_url}",
                    $vars
                ),
            ];
        }

        try {
            $sender->send($channel, $to, $msg['subject'], $msg['body']);
        } catch (Throwable $e) {
            return back()->with('error', 'Reminder pathano jayni: ' . $e->getMessage());
        }

        $abandonedCart->increment('reminder_count');
        $abandonedCart->update(['last_reminded_at' => now()]);

        return back()->with('success', 'Reminder sent via ' . $channel . '.');
    }
}

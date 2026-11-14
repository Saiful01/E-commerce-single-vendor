<?php

namespace App\Http\Controllers;

use App\Exceptions\OrderException;
use App\Models\Order;
use App\Models\SiteSetting;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Support\OnlineGateways;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CheckoutController extends Controller
{
    public function __construct(private CartService $cart, private OrderService $orders)
    {
    }

    public function index(Request $request)
    {
        $payload = $this->cart->payload();
        if (! $payload['count']) {
            return redirect()->route('cart')->with('error', 'Your cart is empty.');
        }
        if (! $payload['ok']) {
            return redirect()->route('cart')->with('error', 'Remove the out-of-stock items to continue.');
        }

        $user      = $request->user();
        $addresses = $user ? $user->addresses()->where('address_type', 'shipping')->orderByDesc('is_default')->get() : collect();
        $default   = $addresses->first();
        $gateways  = $this->cart->gateways();

        $zone = old('delivery_zone', $default
            ? (Str::contains(Str::lower($default->city), 'dhaka') ? 'inside_dhaka' : 'outside_dhaka')
            : 'inside_dhaka');
        $gatewayId = (int) old('payment_gateway_id', $gateways->first()?->id);

        $fields = [
            'customer_name'    => old('customer_name', $user?->name),
            'customer_phone'   => old('customer_phone', $user?->phone ?? $default?->phone),
            'customer_email'   => old('customer_email', $user?->email),
            'shipping_name'    => old('shipping_name', $default?->full_name),
            'shipping_phone'   => old('shipping_phone', $default?->phone),
            'shipping_city'    => old('shipping_city', $default?->city),
            'shipping_area'    => old('shipping_area', $default?->area),
            'shipping_address' => old('shipping_address', $default?->address_line),
        ];

        $quote = $this->cart->quote($zone, old('coupon_code'), $gatewayId, $user, $fields['customer_phone']);

        return view('checkout.index', [
            'cart'      => $payload,
            'quote'     => $quote,
            'gateways'  => $gateways,
            'addresses' => $addresses,
            'fields'    => $fields,
            'zone'      => $zone,
            'gatewayId' => $gatewayId,
            'coupon'    => old('coupon_code'),
            'other'     => (bool) old('ship_other'),
        ]);
    }

    /** AJAX: live totals */
    public function quote(Request $request): JsonResponse
    {
        $data = $request->validate([
            'delivery_zone'      => ['required', Rule::in(array_keys(Order::ZONES))],
            'coupon_code'        => ['nullable', 'string', 'max:50'],
            'payment_gateway_id' => ['nullable', 'integer'],
            'phone'              => ['nullable', 'string', 'max:20'],
        ]);

        return response()->json($this->cart->quote(
            $data['delivery_zone'], $data['coupon_code'] ?? null, $data['payment_gateway_id'] ?? null, $request->user(), $data['phone'] ?? null
        ));
    }

    public function store(Request $request)
    {
        $phone = ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'];

        $data = $request->validate([
            'customer_name'      => ['required', 'string', 'max:100'],
            'customer_phone'     => $phone,
            'customer_email'     => ['nullable', 'email', 'max:100'],
            'ship_other'         => ['nullable', 'boolean'],
            'shipping_name'      => ['nullable', 'required_if:ship_other,1', 'string', 'max:100'],
            'shipping_phone'     => ['nullable', 'required_if:ship_other,1', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
            'shipping_city'      => ['required', 'string', 'max:100'],
            'shipping_area'      => ['required', 'string', 'max:100'],
            'shipping_address'   => ['required', 'string', 'max:1000'],
            'delivery_zone'      => ['required', Rule::in(array_keys(Order::ZONES))],
            'payment_gateway_id' => ['required', 'integer', Rule::in($this->cart->gateways()->pluck('id')->all())],
            'transaction_id'     => ['nullable', 'string', 'max:100'],
            'coupon_code'        => ['nullable', 'string', 'max:50'],
            'customer_note'      => ['nullable', 'string', 'max:1000'],
            'save_address'       => ['nullable', 'boolean'],
        ], [
            'payment_gateway_id.required' => 'Choose a payment method.',
            'payment_gateway_id.in'       => 'Choose a payment method.',
        ]);

        $lines = $this->cart->lines();
        if ($lines->isEmpty()) {
            return redirect()->route('cart')->with('error', 'Your cart is empty.');
        }

        $other = (bool) ($data['ship_other'] ?? false);

        $payload = [
            'items' => $lines->map(fn ($l) => [
                'product_id' => $l['product']->id, 'variant_id' => $l['variant']?->id, 'quantity' => $l['qty'],
            ])->all(),
            'user_id'            => $request->user()?->id,
            'customer_name'      => $data['customer_name'],
            'customer_phone'     => $data['customer_phone'],
            'customer_email'     => $data['customer_email'] ?? null,
            'shipping_name'      => $other ? $data['shipping_name'] : $data['customer_name'],
            'shipping_phone'     => $other ? $data['shipping_phone'] : $data['customer_phone'],
            'shipping_city'      => $data['shipping_city'],
            'shipping_area'      => $data['shipping_area'],
            'shipping_address'   => $data['shipping_address'],
            'delivery_zone'      => $data['delivery_zone'],
            'payment_gateway_id' => $data['payment_gateway_id'],
            'coupon_code'        => $data['coupon_code'] ?? null,
            'customer_note'      => $data['customer_note'] ?? null,
            'placed_by'          => 'customer',
        ];

        try {
            $order = $this->orders->place($payload, $request->user());
        } catch (OrderException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        // Customer typed the bKash / bank transaction id: store it, admin verifies and marks the order paid
        if (! empty($data['transaction_id']) && $order->paymentGateway?->driver !== 'cod') {
            $order->update(['transaction_id' => $data['transaction_id']]);
        }

        if ($request->user() && ! empty($data['save_address'])) {
            $user   = $request->user();
            $exists = $user->addresses()->where('address_type', 'shipping')
                ->where('address_line', $payload['shipping_address'])->where('area', $payload['shipping_area'])->exists();

            if (! $exists) {
                $user->addresses()->create([
                    'address_type' => 'shipping',
                    'full_name'    => $payload['shipping_name'],
                    'phone'        => $payload['shipping_phone'],
                    'city'         => $payload['shipping_city'],
                    'area'         => $payload['shipping_area'],
                    'address_line' => $payload['shipping_address'],
                    'is_default'   => ! $user->addresses()->where('address_type', 'shipping')->exists(),
                ]);
            }
        }

        $this->cart->clear(recovered: true);
        $request->session()->push('placed_orders', $order->order_number);

        // Online payment (SSLCommerz, bKash, Nagad): the order is saved and unpaid, now the customer goes to pay for it
        if (OnlineGateways::isOnline($order->paymentGateway?->driver)) {
            return redirect()->to(OnlineGateways::startUrl($order));
        }

        return redirect()->route('order.success', $order->order_number);
    }

    public function success(Request $request, string $number)
    {
        $order = Order::with(['items', 'status', 'paymentGateway'])->where('order_number', $number)->firstOrFail();

        $mine = in_array($number, session('placed_orders', []), true)
            || ($request->user() && $request->user()->id === $order->user_id)
            || $request->hasValidSignature();          // link coming back from the payment gateway
        abort_unless($mine, 404);

        return view('checkout.success', ['order' => $order]);
    }
}

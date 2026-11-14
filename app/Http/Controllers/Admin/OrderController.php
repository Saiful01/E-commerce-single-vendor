<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\OrderException;
use App\Http\Controllers\Controller;
use App\Models\CourierService;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private OrderService $orders)
    {
    }

    /* ---------------------------------------------------------------
     |  INDEX
     * --------------------------------------------------------------*/
    public function index(Request $request): View
    {
        $q         = trim((string) $request->query('q', ''));
        $statusId  = $request->query('status');
        $payment   = array_key_exists($request->query('payment'), Order::PAYMENT_STATUSES) ? $request->query('payment') : null;
        $courierId = $request->query('courier');
        $from      = $request->query('from');
        $to        = $request->query('to');

        $orders = Order::query()
            ->with(['status', 'paymentGateway:id,name', 'courier:id,name'])
            ->withCount('items')
            ->when($q !== '', fn ($query) => $query->search($q))
            ->when($statusId, fn ($query) => $query->where('order_status_id', $statusId))
            ->when($payment, fn ($query) => $query->where('payment_status', $payment))
            ->when($courierId, fn ($query) => $query->where('courier_service_id', $courierId))
            ->when($from, fn ($query) => $query->whereDate('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('created_at', '<=', $to))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $statuses = OrderStatus::ordered()->get();
        $counts   = Order::query()->selectRaw('order_status_id, count(*) as total')->groupBy('order_status_id')->pluck('total', 'order_status_id');

        // Money from orders that were cancelled or returned does not count as revenue
        $countable = fn ($query) => $query->whereHas('status', fn ($s) => $s->where('restock_items', false));

        return view('admin.orders.index', [
            'orders'    => $orders,
            'statuses'  => $statuses,
            'counts'    => $counts,
            'total'     => $counts->sum(),
            'couriers'  => CourierService::orderBy('name')->get(['id', 'name']),
            'stats'     => [
                'today'    => Order::whereDate('created_at', today())->count(),
                'revenue'  => $countable(Order::whereDate('created_at', today()))->sum('total'),
                'waiting'  => $counts[OrderStatus::default()?->id] ?? 0,
            ],
            'filters'   => compact('q', 'statusId', 'payment', 'courierId', 'from', 'to'),
            'filtering' => $q !== '' || $statusId || $payment || $courierId || $from || $to,
        ]);
    }

    /* ---------------------------------------------------------------
     |  SHOW
     * --------------------------------------------------------------*/
    public function show(Order $order): View
    {
        $order->load(['status', 'items', 'histories.status', 'histories.user', 'paymentGateway', 'courier', 'coupon', 'user']);

        return view('admin.orders.show', [
            'order'    => $order,
            'statuses' => OrderStatus::active()->ordered()->get(),
            'couriers' => CourierService::active()->orderByDesc('is_default')->orderBy('name')->get(),
        ]);
    }

    /* ---------------------------------------------------------------
     |  UPDATES FROM THE ORDER PAGE
     * --------------------------------------------------------------*/
    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'order_status_id' => ['required', 'integer', 'exists:order_statuses,id'],
            'note'            => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->orders->changeStatus($order, OrderStatus::findOrFail($data['order_status_id']), $data['note'] ?? null, $request->user());
        } catch (OrderException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Order status updated.');
    }

    public function updateShipping(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'courier_service_id' => ['nullable', 'integer', 'exists:courier_services,id'],
            'tracking_code'      => ['nullable', 'string', 'max:100'],
        ]);

        $order->update([
            'courier_service_id' => $data['courier_service_id'] ?? null,
            'tracking_code'      => $data['tracking_code'] ?? null,
        ]);

        return back()->with('success', 'Shipping details saved.');
    }

    public function updatePayment(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'payment_status' => ['required', Rule::in(array_keys(Order::PAYMENT_STATUSES))],
            'paid_amount'    => ['nullable', 'numeric', 'min:0', 'max:' . $order->total],
            'transaction_id' => ['nullable', 'string', 'max:100'],
        ]);

        $paid = match ($data['payment_status']) {
            'paid'     => $order->total,                     // fully paid: the amount is the total
            'unpaid'   => 0,
            'refunded' => $order->paid_amount,               // keep what was paid, for the record
            default    => (float) ($data['paid_amount'] ?? 0),
        };

        $order->update([
            'payment_status' => $data['payment_status'],
            'paid_amount'    => $paid,
            'transaction_id' => $data['transaction_id'] ?? null,
        ]);

        return back()->with('success', 'Payment details saved.');
    }

    public function updateNote(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate(['admin_note' => ['nullable', 'string', 'max:2000']]);

        $order->update(['admin_note' => $data['admin_note'] ?? null]);

        return back()->with('success', 'Note saved.');
    }

    /* ---------------------------------------------------------------
     |  INVOICE (print page)
     * --------------------------------------------------------------*/
    public function invoice(Order $order): View
    {
        $order->load(['items', 'status', 'paymentGateway']);

        return view('admin.orders.invoice', [
            'order'    => $order,
            'settings' => SiteSetting::current(),
        ]);
    }

    /* ---------------------------------------------------------------
     |  CREATE ORDER BY HAND (phone / Facebook orders)
     * --------------------------------------------------------------*/
    public function create(): View
    {
        // Rebuild the cart lines after a failed save
        $oldLines = collect(old('items', []))->map(function ($item) {
            $product = Product::with('variants')->find($item['product_id'] ?? 0);

            return $product ? $this->productPayload($product) + [
                    'variant_id' => ! empty($item['variant_id']) ? (int) $item['variant_id'] : null,
                    'quantity'   => max((int) ($item['quantity'] ?? 1), 1),
                ] : null;
        })->filter()->values();

        return view('admin.orders.create', [
            'gateways' => PaymentGateway::active()->ordered()->get()->filter->isConfigured()->values(),
            'settings' => SiteSetting::current(),
            'oldLines' => $oldLines,
            'oldCustomer' => old('user_id') && ($u = User::with('addresses')->withCount('orders')->find(old('user_id')))
                ? $this->customerPayload($u)
                : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $phone = ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'];

        $data = $request->validate([
            'customer_name'      => ['required', 'string', 'max:100'],
            'customer_phone'     => $phone,
            'customer_email'     => ['nullable', 'email', 'max:100'],
            'user_id'            => ['nullable', 'integer', 'exists:users,id'],

            'shipping_name'      => ['required', 'string', 'max:100'],
            'shipping_phone'     => $phone,
            'shipping_city'      => ['required', 'string', 'max:100'],
            'shipping_area'      => ['required', 'string', 'max:100'],
            'shipping_address'   => ['required', 'string', 'max:1000'],
            'delivery_zone'      => ['required', Rule::in(array_keys(Order::ZONES))],

            'payment_gateway_id' => ['required', 'integer', 'exists:payment_gateways,id'],
            'coupon_code'        => ['nullable', 'string', 'max:50'],
            'customer_note'      => ['nullable', 'string', 'max:1000'],
            'admin_note'         => ['nullable', 'string', 'max:2000'],

            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.variant_id' => ['nullable', 'integer'],
            'items.*.quantity'   => ['required', 'integer', 'min:1', 'max:999'],
        ], [
            'items.required' => 'Add at least one product.',
            'items.min'      => 'Add at least one product.',
        ]);

        try {
            $order = $this->orders->place($data + ['placed_by' => 'admin'], $request->user());
        } catch (OrderException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.orders.show', $order)->with('success', "Order {$order->order_number} created.");
    }

    /** GET /admin/orders/products/search?q= : products to add to a new order */
    public function productSearch(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $products = Product::with('variants')
            ->where('is_active', true)
            ->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('sku', 'like', "%{$q}%"))
            ->orderBy('name')
            ->limit(10)
            ->get();

        return response()->json($products->map(fn (Product $p) => $this->productPayload($p))->values());
    }

    /** GET /admin/orders/customers/search?q= : find an existing customer by name, phone or email */
    public function customerSearch(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $users = User::with('addresses')
            ->withCount('orders')
            ->where(fn ($w) => $w
                ->where('name', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%"))
            ->orderByRaw("user_type = 'customer' desc")   // customers first, then admins
            ->orderBy('name')
            ->limit(8)
            ->get();

        return response()->json($users->map(fn (User $u) => $this->customerPayload($u))->values());
    }

    /** GET /admin/orders/customer-lookup?phone= : picks the customer up when their phone number is typed */
    public function customerLookup(Request $request): JsonResponse
    {
        $phone = preg_replace('/[\s\-()]+/', '', (string) $request->query('phone', ''));

        $user = $phone !== ''
            ? User::with('addresses')->withCount('orders')->where('phone', $phone)->first()
            : null;

        return response()->json($user ? ['found' => true] + $this->customerPayload($user) : ['found' => false]);
    }

    /* ------------------------------ helpers ------------------------------ */

    private function customerPayload(User $user): array
    {
        $addresses = $user->addresses
            ->where('address_type', 'shipping')
            ->sortByDesc('is_default')
            ->values();

        return [
            'user_id'      => $user->id,
            'name'         => $user->name,
            'email'        => $user->email,
            'phone'        => $user->phone,
            'type'         => $user->user_type,
            'orders_count' => (int) ($user->orders_count ?? $user->orders()->count()),
            'addresses'    => $addresses->map(fn ($a) => [
                'id'           => $a->id,
                'name'         => $a->full_name,
                'phone'        => $a->phone,
                'city'         => $a->city,
                'area'         => $a->area,
                'address_line' => $a->address_line,
                'is_default'   => $a->is_default,
            ])->all(),
        ];
    }

    private function productPayload(Product $p): array
    {
        $onSale = $p->discount_price && (float) $p->discount_price < (float) $p->price;

        return [
            'product_id'   => $p->id,
            'name'         => $p->name,
            'sku'          => $p->sku,
            'image'        => $p->featured_image_url,
            'has_variants' => (bool) $p->has_variants,
            'price'        => (float) ($onSale ? $p->discount_price : $p->price),
            'stock'        => (int) $p->stock_quantity,
            'variants'     => $p->has_variants
                ? $p->variants->map(fn ($v) => [
                    'id'    => $v->id,
                    'label' => trim("{$v->attribute_name}: {$v->attribute_value}"),
                    'sku'   => $v->sku,
                    'price' => (float) $v->price,
                    'stock' => (int) $v->stock_quantity,
                ])->values()->all()
                : [],
        ];
    }
}

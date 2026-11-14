<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\OrderException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Services\ReturnService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderReturnController extends Controller
{
    public function __construct(private ReturnService $returns)
    {
    }

    public function index(Request $request): View
    {
        $q      = trim((string) $request->query('q', ''));
        $status = array_key_exists($request->query('status'), OrderReturn::STATUSES) ? $request->query('status') : null;

        $returns = OrderReturn::query()
            ->with('order:id,order_number,customer_name,customer_phone')
            ->withCount('items')
            ->when($status, fn ($x) => $x->where('status', $status))
            ->when($q !== '', fn ($x) => $x->where(fn ($w) => $w
                ->where('return_number', 'like', "%{$q}%")
                ->orWhereHas('order', fn ($o) => $o->where('order_number', 'like', "%{$q}%")
                    ->orWhere('customer_name', 'like', "%{$q}%")->orWhere('customer_phone', 'like', "%{$q}%"))))
            ->latest()->paginate(15)->withQueryString();

        return view('admin.order-returns.index', [
            'returns' => $returns,
            'counts'  => OrderReturn::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'status'  => $status,
            'q'       => $q,
        ]);
    }

    public function show(OrderReturn $orderReturn): View
    {
        $orderReturn->load(['order.paymentGateway', 'items.orderItem', 'handler']);

        return view('admin.order-returns.show', [
            'return'    => $orderReturn,
            'suggested' => $this->returns->suggestedRefund($orderReturn),
            'maxRefund' => max(round($orderReturn->order->paid_amount - ($orderReturn->order->total - $orderReturn->order->returned_total - $orderReturn->items_value), 2), 0),
        ]);
    }

    /** Start a return by hand (a customer phoned or messaged). */
    public function create(Order $order)
    {
        $order->load(['items', 'status']);
        $check = $this->returns->eligibility($order, admin: true);

        if (! $check['ok']) {
            return redirect()->route('admin.orders.show', $order)->with('error', $check['reason']);
        }

        return view('admin.order-returns.create', ['order' => $order, 'remaining' => $check['remaining']]);
    }

    public function store(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'qty'          => ['required', 'array'],
            'qty.*'        => ['nullable', 'integer', 'min:0', 'max:999'],
            'reason'       => ['required', Rule::in(array_keys(config('shop.return_reasons')))],
            'details'      => ['nullable', 'string', 'max:1000'],
            'auto_approve' => ['nullable', 'boolean'],
        ]);

        try {
            $return = $this->returns->create(
                $order, $data['qty'], $data['reason'], $data['details'] ?? null, 'admin', $request->user(), (bool) ($data['auto_approve'] ?? false)
            );
        } catch (OrderException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.order-returns.show', $return)->with('success', "Return {$return->return_number} created.");
    }

    public function approve(Request $request, OrderReturn $orderReturn): RedirectResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);

        return $this->run(fn () => $this->returns->approve($orderReturn, $request->user(), $data['note'] ?? null), 'Return approved.');
    }

    public function reject(Request $request, OrderReturn $orderReturn): RedirectResponse
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:1000']], ['note.required' => 'Write a short reason for the customer.']);

        return $this->run(fn () => $this->returns->reject($orderReturn, $request->user(), $data['note']), 'Return rejected.');
    }

    public function complete(Request $request, OrderReturn $orderReturn): RedirectResponse
    {
        $data = $request->validate([
            'restock'            => ['nullable', 'boolean'],
            'refund_amount'      => ['required', 'numeric', 'min:0'],
            'payment_gateway_id' => ['nullable', 'integer', 'exists:payment_gateways,id'],
            'transaction_id'     => ['nullable', 'string', 'max:100'],
            'note'               => ['nullable', 'string', 'max:1000'],
        ]);

        return $this->run(fn () => $this->returns->complete(
            $orderReturn, (bool) ($data['restock'] ?? false), (float) $data['refund_amount'],
            $data['payment_gateway_id'] ?? null, $data['transaction_id'] ?? null, $data['note'] ?? null, $request->user()
        ), 'Return completed.');
    }

    private function run(callable $action, string $message): RedirectResponse
    {
        try {
            $action();
        } catch (OrderException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', $message);
    }
}

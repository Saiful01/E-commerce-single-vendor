<?php

namespace App\Services;

use App\Events\OrderNotification;
use App\Exceptions\OrderException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\OrderReturnItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Returns work per item and per quantity:
 *   requested -> approved -> completed   (or rejected)
 * Completing a return puts the goods back in stock (if you tick it) and gives the money back.
 */
class ReturnService
{
    public function __construct(private OrderPaymentService $payments)
    {
    }

    /** @return array{ok: bool, reason: ?string, remaining: array<int,int>} */
    public function eligibility(Order $order, bool $admin = false): array
    {
        $order->loadMissing(['status', 'items']);
        $remaining = $this->remaining($order);
        $no = fn (string $why) => ['ok' => false, 'reason' => $why, 'remaining' => $remaining];

        if ($order->status->restock_items) {
            return $no('This order was cancelled or returned.');
        }
        if (! $order->delivered_at) {
            return $no('Returns open once the order is delivered.');
        }

        $days = (int) config('shop.return_window_days', 7);
        if (! $admin && $days > 0 && $order->delivered_at->lt(now()->subDays($days))) {
            return $no("The {$days}-day return window for this order has ended. Please contact us.");
        }
        if (array_sum($remaining) < 1) {
            return $no('Every item of this order is already returned or in a return request.');
        }

        return ['ok' => true, 'reason' => null, 'remaining' => $remaining];
    }

    /** Quantity still returnable per order item (already returned and requested quantities are taken off). */
    public function remaining(Order $order): array
    {
        $reserved = OrderReturnItem::query()
            ->join('order_returns', 'order_returns.id', '=', 'order_return_items.order_return_id')
            ->where('order_returns.order_id', $order->id)
            ->whereIn('order_returns.status', ['requested', 'approved'])
            ->groupBy('order_return_items.order_item_id')
            ->selectRaw('order_return_items.order_item_id as item_id, sum(order_return_items.quantity) as qty')
            ->pluck('qty', 'item_id');

        return $order->items->mapWithKeys(fn (OrderItem $i) => [
            $i->id => max($i->quantity - $i->returned_quantity - (int) ($reserved[$i->id] ?? 0), 0),
        ])->all();
    }

    /**
     * @param array<int,int> $quantities order_item_id => quantity
     * @throws OrderException
     */
    public function create(Order $order, array $quantities, string $reason, ?string $details, string $source, ?User $by = null, bool $autoApprove = false): OrderReturn
    {
        return DB::transaction(function () use ($order, $quantities, $reason, $details, $source, $by, $autoApprove) {
            $order = Order::whereKey($order->id)->lockForUpdate()->with(['status', 'items', 'coupon'])->firstOrFail();

            $check = $this->eligibility($order, $source === 'admin');
            if (! $check['ok']) {
                throw new OrderException($check['reason']);
            }

            $rows  = [];
            $total = 0.0;

            foreach ($quantities as $itemId => $qty) {
                $qty  = (int) $qty;
                $item = $order->items->firstWhere('id', (int) $itemId);
                if ($qty < 1 || ! $item) {
                    continue;
                }

                $left = $check['remaining'][$item->id] ?? 0;
                if ($qty > $left) {
                    throw new OrderException($left > 0
                        ? "You can return at most {$left} of \"{$item->name}\"."
                        : "\"{$item->name}\" cannot be returned any more.");
                }

                $value  = $this->valueOf($order, $item, $qty);
                $total += $value;
                $rows[] = ['order_item_id' => $item->id, 'quantity' => $qty, 'value' => $value];
            }

            if (! $rows) {
                throw new OrderException('Choose at least one item to return.');
            }

            $return = OrderReturn::create([
                'return_number' => OrderReturn::generateNumber(),
                'order_id'      => $order->id,
                'user_id'       => $order->user_id,
                'source'        => $source,
                'status'        => $autoApprove ? 'approved' : 'requested',
                'reason'        => $reason,
                'details'       => $details ?: null,
                'items_value'   => round($total, 2),
                'handled_by'    => $autoApprove ? $by?->id : null,
                'approved_at'   => $autoApprove ? now() : null,
            ]);
            $return->items()->createMany($rows);

            $key = $autoApprove ? 'return.approved' : 'return.requested';
            DB::afterCommit(fn () => event(new OrderNotification($key, $order->fresh(), ['return_id' => $return->id])));

            return $return;
        });
    }

    /** @throws OrderException */
    public function approve(OrderReturn $return, ?User $by = null, ?string $note = null): OrderReturn
    {
        return DB::transaction(function () use ($return, $by, $note) {
            $return = OrderReturn::whereKey($return->id)->lockForUpdate()->firstOrFail();

            if ($return->status !== 'requested') {
                throw new OrderException('Only a requested return can be approved.');
            }

            $return->update([
                'status' => 'approved', 'approved_at' => now(), 'handled_by' => $by?->id,
                'admin_note' => $note ?: $return->admin_note,
            ]);

            $order = $return->order;
            DB::afterCommit(fn () => event(new OrderNotification('return.approved', $order->fresh(), ['return_id' => $return->id])));

            return $return;
        });
    }

    /** @throws OrderException */
    public function reject(OrderReturn $return, ?User $by = null, ?string $note = null): OrderReturn
    {
        return DB::transaction(function () use ($return, $by, $note) {
            $return = OrderReturn::whereKey($return->id)->lockForUpdate()->firstOrFail();

            if (! in_array($return->status, ['requested', 'approved'], true)) {
                throw new OrderException('This return can no longer be rejected.');
            }

            $return->update([
                'status' => 'rejected', 'rejected_at' => now(), 'handled_by' => $by?->id,
                'admin_note' => $note ?: $return->admin_note,
            ]);

            $order = $return->order;
            DB::afterCommit(fn () => event(new OrderNotification('return.rejected', $order->fresh(), ['return_id' => $return->id, 'note' => $note])));

            return $return;
        });
    }

    /**
     * The goods are back. Optionally restock them and give the money back.
     *
     * @throws OrderException
     */
    public function complete(OrderReturn $return, bool $restock, float $refund, ?int $gatewayId = null, ?string $transactionId = null, ?string $note = null, ?User $by = null): OrderReturn
    {
        return DB::transaction(function () use ($return, $restock, $refund, $gatewayId, $transactionId, $note, $by) {
            $return = OrderReturn::whereKey($return->id)->lockForUpdate()->with('items.orderItem')->firstOrFail();

            if ($return->status !== 'approved') {
                throw new OrderException('Approve the return before completing it.');
            }

            $order = Order::whereKey($return->order_id)->lockForUpdate()->firstOrFail();

            foreach ($return->items as $ri) {
                $item = $ri->orderItem;

                if ($item->returned_quantity + $ri->quantity > $item->quantity) {
                    throw new OrderException("\"{$item->name}\" has already been returned.");
                }

                $item->increment('returned_quantity', $ri->quantity);

                if ($restock) {
                    if ($item->product_id) {
                        Product::whereKey($item->product_id)->increment('stock_quantity', $ri->quantity);
                    }
                    if ($item->product_variant_id) {
                        ProductVariant::whereKey($item->product_variant_id)->increment('stock_quantity', $ri->quantity);
                    }
                }
            }

            $order->returned_total = round($order->returned_total + $return->items_value, 2);
            $order->save();

            $refund = round($refund, 2);
            $max    = $this->maxRefund($order);

            if ($refund < 0 || $refund > $max + 0.009) {
                throw new OrderException('The refund cannot be more than ' . SiteSetting::current()->formatMoney($max) . ' for this order.');
            }

            if ($refund > 0) {
                $this->payments->refund($order, $refund, $gatewayId, $transactionId, $note ?: "Refund for {$return->return_number}", $by);
            } else {
                $this->payments->sync($order);
            }

            $return->update([
                'status' => 'completed', 'refund_amount' => $refund, 'restocked' => $restock,
                'completed_at' => now(), 'handled_by' => $by?->id, 'admin_note' => $note ?: $return->admin_note,
            ]);

            DB::afterCommit(fn () => event(new OrderNotification('return.completed', $order->fresh(), ['return_id' => $return->id, 'refund' => $refund])));

            return $return;
        });
    }

    /** What the customer paid beyond what they now owe. */
    public function maxRefund(Order $order): float
    {
        return max(round($order->paid_amount - $order->payable_amount, 2), 0);
    }

    /** Refund to suggest before the return is completed. */
    public function suggestedRefund(OrderReturn $return): float
    {
        $order        = $return->order;
        $payableAfter = $order->total - ($order->returned_total + $return->items_value);

        return max(round($order->paid_amount - $payableAfter, 2), 0);
    }

    /** Price of the returned goods. A coupon discount is shared out by value, a free-delivery coupon is not. */
    private function valueOf(Order $order, OrderItem $item, int $qty): float
    {
        $line  = $item->unit_price * $qty;
        $share = 0.0;

        if ($order->discount_amount > 0 && $order->subtotal > 0 && $order->coupon?->type !== 'free_shipping') {
            $share = $order->discount_amount * ($line / $order->subtotal);
        }

        return round($line - $share, 2);
    }
}

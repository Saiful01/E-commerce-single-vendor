<?php

namespace App\Services;

use App\Exceptions\OrderException;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Everything that changes an order lives here, so the admin panel and the
 * storefront checkout follow exactly the same rules.
 *
 *   $order = app(OrderService::class)->place([...], $actingUser);
 *   app(OrderService::class)->changeStatus($order, $status, 'note', $actingUser);
 */
class OrderService
{
    /* ---------------------------------------------------------------
     |  PLACE AN ORDER
     * --------------------------------------------------------------*/

    /**
     * $data keys:
     *   items[]            product_id, variant_id (optional), quantity
     *   customer_name, customer_phone, customer_email (optional), user_id (optional)
     *   shipping_name, shipping_phone, shipping_city, shipping_area, shipping_address
     *   delivery_zone      inside_dhaka | outside_dhaka
     *   payment_gateway_id
     *   coupon_code (optional), customer_note (optional), admin_note (optional)
     *   placed_by          customer | admin
     *
     * @throws OrderException with a message that is safe to show
     */
    public function place(array $data, ?User $actor = null): Order
    {
        return DB::transaction(function () use ($data, $actor) {
            $settings = SiteSetting::current();
            $user     = ! empty($data['user_id']) ? User::find($data['user_id']) : null;

            // 1) Lines and subtotal (rows are locked so two orders cannot take the last item)
            $lines    = $this->buildLines($data['items']);
            $subtotal = round($lines->sum('line_total'), 2);

            // 2) Delivery charge
            $zone     = $data['delivery_zone'];
            $shipping = (float) ($zone === 'inside_dhaka'
                ? $settings->delivery_charge_inside_dhaka
                : $settings->delivery_charge_outside_dhaka);

            if ($settings->free_shipping_min !== null && $subtotal >= $settings->free_shipping_min) {
                $shipping = 0.0;
            }

            // 3) Coupon
            $coupon   = null;
            $discount = 0.0;

            if (! empty($data['coupon_code'])) {
                [$coupon, $discount] = $this->resolveCoupon(
                    $data['coupon_code'], $user, $data['customer_phone'], $lines, $subtotal, $shipping
                );
            }

            // 4) Payment method and its fee
            $beforeFee = round($subtotal - $discount + $shipping, 2);
            $gateway   = PaymentGateway::find($data['payment_gateway_id'] ?? null);

            if (! $gateway || ! $gateway->isAvailableFor($beforeFee)) {
                throw new OrderException('That payment method is not available for this order.');
            }

            $fee   = $gateway->feeFor($beforeFee);
            $total = round($beforeFee + $fee, 2);

            // 5) The order itself
            $status = OrderStatus::default()
                ?? throw new OrderException('No default order status is set. Run the OrderStatusSeeder first.');

            $order = Order::create([
                'order_number'       => Order::generateNumber(),
                'user_id'            => $user?->id,
                'customer_name'      => $data['customer_name'],
                'customer_phone'     => $data['customer_phone'],
                'customer_email'     => $data['customer_email'] ?? null,
                'shipping_name'      => $data['shipping_name'],
                'shipping_phone'     => $data['shipping_phone'],
                'shipping_city'      => $data['shipping_city'],
                'shipping_area'      => $data['shipping_area'],
                'shipping_address'   => $data['shipping_address'],
                'delivery_zone'      => $zone,
                'order_status_id'    => $status->id,
                'payment_gateway_id' => $gateway->id,
                'payment_status'     => 'unpaid',
                'coupon_id'          => $coupon?->id,
                'coupon_code'        => $coupon?->code,
                'subtotal'           => $subtotal,
                'discount_amount'    => $discount,
                'shipping_charge'    => $shipping,
                'payment_fee'        => $fee,
                'total'              => $total,
                'customer_note'      => $data['customer_note'] ?? null,
                'admin_note'         => $data['admin_note'] ?? null,
                'placed_by'          => $data['placed_by'] ?? 'customer',
            ]);

            // 6) Items (a copy of name, sku and price) and stock
            foreach ($lines as $line) {
                $order->items()->create([
                    'product_id'         => $line['product']->id,
                    'product_variant_id' => $line['variant']?->id,
                    'name'               => $line['name'],
                    'sku'                => $line['sku'],
                    'variant_label'      => $line['variant_label'],
                    'image'              => $line['product']->featured_image,
                    'unit_price'         => $line['unit_price'],
                    'quantity'           => $line['quantity'],
                    'line_total'         => $line['line_total'],
                ]);

                $line['product']->decrement('stock_quantity', $line['quantity']);
                $line['variant']?->decrement('stock_quantity', $line['quantity']);
            }

            // 7) Count the coupon use
            if ($coupon) {
                try {
                    $coupon->redeem($user, $discount, $order->id);
                } catch (\RuntimeException $e) {
                    throw new OrderException($e->getMessage());
                }
            }

            $order->histories()->create([
                'order_status_id' => $status->id,
                'user_id'         => $actor?->id,
                'note'            => ($data['placed_by'] ?? 'customer') === 'admin' ? 'Order created from the admin panel.' : 'Order placed.',
                'notified'        => $status->notify_customer,
            ]);

            DB::afterCommit(fn () => event(new \App\Events\OrderNotification('order.placed', $order)));

            return $order->load(['status', 'items']);
        });
    }

    /* ---------------------------------------------------------------
     |  CHANGE STATUS
     * --------------------------------------------------------------*/

    /** @throws OrderException */
    public function changeStatus(Order $order, OrderStatus $to, ?string $note = null, ?User $actor = null): Order
    {
        return DB::transaction(function () use ($order, $to, $note, $actor) {
            $order = Order::whereKey($order->id)->lockForUpdate()->with(['status', 'paymentGateway'])->firstOrFail();

            if ($order->status->is_final) {
                throw new OrderException("This order is already {$order->status->name} and cannot be changed.");
            }
            if ($order->order_status_id === $to->id) {
                throw new OrderException("The order is already {$to->name}.");
            }
            if (! $to->is_active) {
                throw new OrderException("\"{$to->name}\" is switched off.");
            }

            $changes = ['order_status_id' => $to->id];

            // Cancelled / returned: the items go back on the shelf, once
            if ($to->restock_items && ! $order->restocked_at) {
                $this->restock($order);
                $changes['restocked_at'] = now();
            }

            switch ($to->slug) {
                case 'shipped':
                    $changes['shipped_at'] = now();
                    break;
                case 'delivered':
                    $changes['delivered_at'] = now();
                    break;
                case 'cancelled':
                    $changes['cancelled_at'] = now();
                    break;
            }

            $order->update($changes);
            // Cash on delivery: the money is collected at the door, so it is recorded as a payment
            if ($to->slug === 'delivered' && $order->paymentGateway?->driver === 'cod' && $order->due_amount > 0) {
                app(\App\Services\OrderPaymentService::class)->record(
                    $order, $order->due_amount, $order->payment_gateway_id, null, 'Collected on delivery', $actor
                );
            }

            $order->histories()->create([
                'order_status_id' => $to->id,
                'user_id'         => $actor?->id,
                'note'            => $note ?: null,
                'notified'        => $to->notify_customer,
            ]);

            DB::afterCommit(fn () => event(new \App\Events\OrderNotification('order.status_changed', $order->fresh(), ['status' => $to->name])));

            return $order->load(['status', 'paymentGateway']);
        });
    }

    /* ---------------------------------------------------------------
     |  INTERNALS
     * --------------------------------------------------------------*/

    /**
     * Check every item, lock its stock row and work out prices.
     * Returns one array per distinct product / variant.
     */
    private function buildLines(array $items): Collection
    {
        // The same product added twice becomes one line
        $merged = [];
        foreach ($items as $item) {
            $key = ($item['product_id'] ?? 0) . ':' . ($item['variant_id'] ?? 0);
            $merged[$key] = [
                'product_id' => (int) $item['product_id'],
                'variant_id' => ! empty($item['variant_id']) ? (int) $item['variant_id'] : null,
                'quantity'   => ($merged[$key]['quantity'] ?? 0) + max((int) $item['quantity'], 1),
            ];
        }

        return collect($merged)->values()->map(function (array $item) {
            $product = Product::whereKey($item['product_id'])->lockForUpdate()->first();

            if (! $product || ! $product->is_active) {
                throw new OrderException('One of the products is no longer available.');
            }

            $variant = null;

            if ($product->has_variants) {
                $variant = $item['variant_id']
                    ? ProductVariant::where('product_id', $product->id)->whereKey($item['variant_id'])->lockForUpdate()->first()
                    : null;

                if (! $variant) {
                    throw new OrderException("Choose an option for \"{$product->name}\".");
                }

                $unit  = (float) $variant->price;
                $stock = (int) $variant->stock_quantity;
                $onSale = false;
                $label = trim("{$variant->attribute_name}: {$variant->attribute_value}");
                $sku   = $variant->sku;
            } else {
                $onSale = $product->discount_price && (float) $product->discount_price < (float) $product->price;
                $unit   = (float) ($onSale ? $product->discount_price : $product->price);
                $stock  = (int) $product->stock_quantity;
                $label  = null;
                $sku    = $product->sku;
            }

            if ($stock < $item['quantity']) {
                $name = $label ? "{$product->name} ({$label})" : $product->name;
                throw new OrderException($stock > 0
                    ? "Only {$stock} left of \"{$name}\"."
                    : "\"{$name}\" is out of stock.");
            }

            return [
                'product'       => $product,
                'variant'       => $variant,
                'name'          => $product->name,
                'sku'           => $sku,
                'variant_label' => $label,
                'unit_price'    => $unit,
                'quantity'      => $item['quantity'],
                'line_total'    => round($unit * $item['quantity'], 2),
                'on_sale'       => (bool) $onSale,
            ];
        });
    }

    /** @return array{0: Coupon, 1: float}  the coupon and the discount it gives */
    private function resolveCoupon(string $code, ?User $user, string $phone, Collection $lines, float $subtotal, float $shipping): array
    {
        $coupon = Coupon::with(['categories:id', 'brands:id', 'products:id'])
            ->where('code', strtoupper(trim($code)))
            ->first();

        if (! $coupon) {
            throw new OrderException('That coupon code is not valid.');
        }

        // "First order" is judged by account or by phone number
        $previousOrders = Order::query()
            ->when($user, fn ($q) => $q->where('user_id', $user->id)->orWhere('customer_phone', $phone), fn ($q) => $q->where('customer_phone', $phone))
            ->count();

        if ($error = $coupon->validationError($user, $subtotal, $previousOrders)) {
            throw new OrderException($error);
        }

        $eligible = $lines->filter(function ($line) use ($coupon) {
            if ($coupon->exclude_discounted && $line['on_sale']) {
                return false;
            }

            return match ($coupon->applies_to) {
                'categories' => $coupon->categories->contains('id', $line['product']->category_id),
                'brands'     => $coupon->brands->contains('id', $line['product']->brand_id),
                'products'   => $coupon->products->contains('id', $line['product']->id),
                default      => true,
            };
        })->sum('line_total');

        if ($eligible <= 0) {
            throw new OrderException('This coupon does not apply to the items in the cart.');
        }

        return [$coupon, $coupon->discountFor((float) $eligible, $shipping)];
    }

    /** Put the items of an order back into stock. */
    private function restock(Order $order): void
    {
        foreach ($order->items as $item) {
            if ($item->product_id) {
                Product::whereKey($item->product_id)->increment('stock_quantity', $item->quantity);
            }
            if ($item->product_variant_id) {
                ProductVariant::whereKey($item->product_variant_id)->increment('stock_quantity', $item->quantity);
            }
        }
    }
}

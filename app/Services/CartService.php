<?php

namespace App\Services;

use App\Exceptions\OrderException;
use App\Models\AbandonedCart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Session cart. Only product / variant ids and quantities are stored;
 * prices and stock are always read fresh from the database.
 */
class CartService
{
    private const KEY = 'cart.items';

    /** Payment drivers that work without an online redirect (customer pays on delivery or sends money). */
    public const CHECKOUT_DRIVERS = ['cod', 'bank_transfer', 'bkash_manual', 'nagad_manual', 'rocket_manual', 'sslcommerz', 'bkash', 'nagad'];

    public function raw(): array
    {
        return session(self::KEY, []);
    }

    /* ------------------------------ changes ------------------------------ */

    /** @throws OrderException */
    public function add(int $productId, ?int $variantId, int $qty = 1): void
    {
        $product = Product::active()->with('variants')->find($productId)
            ?? throw new OrderException('This product is not available.');

        if ($product->has_variants) {
            $variant = $product->variants->firstWhere('id', $variantId)
                ?? throw new OrderException("Choose an option for \"{$product->name}\".");
            $variantId = $variant->id;
            $stock     = (int) $variant->stock_quantity;
        } else {
            $variantId = null;
            $stock     = (int) $product->stock_quantity;
        }

        if ($stock < 1) {
            throw new OrderException("\"{$product->name}\" is out of stock.");
        }

        $cart = $this->raw();
        $key  = $productId . ':' . ($variantId ?? 0);
        $have = $cart[$key]['qty'] ?? 0;
        $want = $have + max($qty, 1);

        if ($want > $stock) {
            throw new OrderException($have
                ? "Only {$stock} available, and you already have {$have} in your cart."
                : "Only {$stock} available.");
        }

        $cart[$key] = ['product_id' => $productId, 'variant_id' => $variantId, 'qty' => $want];
        $this->save($cart);
    }

    /** Returns a note when the quantity had to be lowered. */
    public function update(string $key, int $qty): ?string
    {
        $cart = $this->raw();
        if (! isset($cart[$key])) {
            return null;
        }

        if ($qty < 1) {
            unset($cart[$key]);
            $this->save($cart);
            return null;
        }

        $note = null;
        $line = $this->lines()->firstWhere('key', $key);
        if ($line && $qty > $line['stock']) {
            $qty  = max($line['stock'], 1);
            $note = "Only {$line['stock']} available.";
        }

        $cart[$key]['qty'] = min($qty, 999);
        $this->save($cart);

        return $note;
    }

    public function remove(string $key): void
    {
        $cart = $this->raw();
        unset($cart[$key]);
        $this->save($cart);
    }

    public function clear(bool $recovered = false): void
    {
        $sid = session()->getId();
        session()->forget(self::KEY);

        try {
            $query = AbandonedCart::where('session_id', $sid)->open();
            $recovered ? $query->update(['recovered_at' => now()]) : $query->delete();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /* ------------------------------ reading ------------------------------ */

    /** One array per cart row with live price and stock. Rows whose product is gone are dropped. */
    public function lines(): Collection
    {
        $cart = $this->raw();
        if (! $cart) {
            return collect();
        }

        $products = Product::active()->with(['variants', 'category:id,name,slug'])
            ->whereIn('id', collect($cart)->pluck('product_id'))->get()->keyBy('id');

        $lines = collect();
        $dirty = false;

        foreach ($cart as $key => $row) {
            $p = $products->get($row['product_id']);
            $variant = ($p && $p->has_variants) ? $p->variants->firstWhere('id', $row['variant_id']) : null;

            if (! $p || ($p->has_variants && ! $variant)) {
                unset($cart[$key]);
                $dirty = true;
                continue;
            }

            if ($variant) {
                $unit = (float) $variant->price;
                $old  = null;
                $stock = (int) $variant->stock_quantity;
                $label = trim("{$variant->attribute_name}: {$variant->attribute_value}");
                $image = $variant->image_url ?: $p->featured_image_url;
            } else {
                $unit = $p->final_price;
                $old  = $p->has_discount ? (float) $p->price : null;
                $stock = (int) $p->stock_quantity;
                $label = null;
                $image = $p->featured_image_url;
            }

            $qty = min((int) $row['qty'], max($stock, 1));

            $lines->push([
                'key'        => $key,
                'product'    => $p,
                'variant'    => $variant,
                'name'       => $p->name,
                'label'      => $label,
                'image'      => $image,
                'url'        => $p->url,
                'unit_price' => $unit,
                'old_price'  => $old,
                'qty'        => $qty,
                'stock'      => $stock,
                'available'  => $stock > 0,
                'line_total' => round($unit * $qty, 2),
            ]);
        }

        if ($dirty) {
            session([self::KEY => $cart]);
        }

        return $lines;
    }

    /** JSON the cart page, header badge and add-to-cart toast use. */
    public function payload(): array
    {
        $s     = SiteSetting::current();
        $lines = $this->lines();
        $sub   = round($lines->sum('line_total'), 2);
        $min   = (float) $s->free_shipping_min;
        $left  = $min > 0 ? max($min - $sub, 0) : 0;

        return [
            'count'         => $lines->count(),                       // different products, shown on the cart icon and in "Your cart (n)"
            'quantity'      => (int) $lines->sum('qty'),             // all pieces together
            'subtotal'      => $sub,
            'subtotal_fmt'  => $s->formatMoney($sub),
            'free_min'      => $min > 0 ? $min : null,
            'free_left_fmt' => $s->formatMoney($left),
            'free_progress' => $min > 0 ? min(100, (int) round($sub / $min * 100)) : 0,
            'ok'            => $lines->isNotEmpty() && $lines->every(fn ($l) => $l['available']),
            'lines'         => $lines->map(fn ($l) => [
                'key'            => $l['key'],
                'name'           => $l['name'],
                'label'          => $l['label'],
                'image'          => $l['image'],
                'url'            => $l['url'],
                'qty'            => $l['qty'],
                'stock'          => $l['stock'],
                'available'      => $l['available'],
                'unit_fmt'       => $s->formatMoney($l['unit_price']),
                'old_fmt'        => $l['old_price'] ? $s->formatMoney($l['old_price']) : null,
                'line_total_fmt' => $s->formatMoney($l['line_total']),
            ])->values()->all(),
        ];
    }

    /* ------------------------------ checkout ------------------------------ */

    public function gateways(): Collection
    {
        return PaymentGateway::active()->ordered()
            ->whereIn('driver', self::CHECKOUT_DRIVERS)->get()
            ->filter->isConfigured()->values();
    }

    /**
     * Price preview for the checkout page. Follows the same rules as OrderService::place(),
     * which recalculates everything again when the order is really placed.
     */
    public function quote(string $zone, ?string $code = null, ?int $gatewayId = null, ?User $user = null, ?string $phone = null): array
    {
        $s        = SiteSetting::current();
        $lines    = $this->lines();
        $subtotal = round($lines->sum('line_total'), 2);

        $shipping = (float) ($zone === 'inside_dhaka' ? $s->delivery_charge_inside_dhaka : $s->delivery_charge_outside_dhaka);
        if ($s->free_shipping_min !== null && $subtotal >= $s->free_shipping_min) {
            $shipping = 0.0;
        }

        // Coupon
        $coupon = null;
        $discount = 0.0;
        $error = null;

        if ($code !== null && trim($code) !== '') {
            $found = Coupon::with(['categories:id', 'brands:id', 'products:id'])
                ->where('code', strtoupper(trim($code)))->first();

            if (! $found) {
                $error = 'That coupon code is not valid.';
            } else {
                $previous = Order::query()
                    ->when($user,
                        fn ($q) => $q->where('user_id', $user->id)->orWhere('customer_phone', (string) $phone),
                        fn ($q) => $q->where('customer_phone', (string) $phone))
                    ->count();

                $error = $found->validationError($user, $subtotal, $previous);

                if (! $error) {
                    $eligible = $lines->filter(function ($l) use ($found) {
                        if ($found->exclude_discounted && $l['old_price']) {
                            return false;
                        }
                        return match ($found->applies_to) {
                            'categories' => $found->categories->contains('id', $l['product']->category_id),
                            'brands'     => $found->brands->contains('id', $l['product']->brand_id),
                            'products'   => $found->products->contains('id', $l['product']->id),
                            default      => true,
                        };
                    })->sum('line_total');

                    if ($eligible <= 0) {
                        $error = 'This coupon does not apply to the items in the cart.';
                    } else {
                        $coupon   = $found;
                        $discount = $found->discountFor((float) $eligible, $shipping);
                    }
                }
            }
        }

        $beforeFee = round($subtotal - $discount + $shipping, 2);

        $gateways = [];
        foreach ($this->gateways() as $g) {
            $gateways[$g->id] = ['ok' => $g->isAvailableFor($beforeFee), 'fee' => $g->feeFor($beforeFee)];
        }
        $fee   = ($gatewayId && ($gateways[$gatewayId]['ok'] ?? false)) ? $gateways[$gatewayId]['fee'] : 0.0;
        $total = round($beforeFee + $fee, 2);

        $fmt = fn ($n) => $s->formatMoney($n);

        return [
            'subtotal' => $subtotal, 'discount' => $discount, 'shipping' => $shipping, 'fee' => $fee, 'total' => $total,
            'fmt' => [
                'subtotal' => $fmt($subtotal), 'discount' => $fmt($discount), 'shipping' => $shipping > 0 ? $fmt($shipping) : 'Free',
                'fee' => $fmt($fee), 'total' => $fmt($total),
            ],
            'coupon_code'  => $coupon?->code,
            'coupon_label' => $coupon?->discount_label,
            'coupon_error' => $error,
            'gateways'     => collect($gateways)->map(fn ($g) => ['ok' => $g['ok'], 'fee_fmt' => $g['fee'] > 0 ? '+' . $fmt($g['fee']) : null])->all(),
        ];
    }

    /* ------------------------------ internals ------------------------------ */

    private function save(array $cart): void
    {
        session([self::KEY => $cart]);
        $this->trackAbandoned();
    }

    /** Keeps the admin "abandoned carts" list up to date. Must never break the cart itself. */
    private function trackAbandoned(): void
    {
        try {
            $sid   = session()->getId();
            $lines = $this->lines();

            if ($lines->isEmpty()) {
                AbandonedCart::where('session_id', $sid)->open()->delete();
                return;
            }

            $user = auth()->user();

            AbandonedCart::updateOrCreate(
                ['session_id' => $sid, 'recovered_at' => null],
                [
                    'user_id' => $user?->id,
                    'email'   => $user?->email,
                    'phone'   => $user?->phone,
                    'items'   => $lines->map(fn ($l) => [
                        'product_id' => $l['product']->id, 'variant_id' => $l['variant']?->id,
                        'name' => $l['name'], 'variant' => $l['label'], 'qty' => $l['qty'], 'price' => $l['unit_price'],
                    ])->all(),
                    'total'   => round($lines->sum('line_total'), 2),
                ]
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

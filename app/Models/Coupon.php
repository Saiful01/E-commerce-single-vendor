<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Coupon extends Model
{
    public const TYPES = [
        'percentage'    => 'Percentage',
        'fixed'         => 'Fixed amount',
        'free_shipping' => 'Free delivery',
    ];

    public const APPLIES_TO = [
        'all'        => 'All products',
        'categories' => 'Specific categories',
        'brands'     => 'Specific brands',
        'products'   => 'Specific products',
    ];

    /** live | scheduled | expired | exhausted | inactive */
    public const STATES = ['live', 'scheduled', 'expired', 'exhausted', 'inactive'];

    protected $fillable = [
        'code', 'name', 'description',
        'type', 'value', 'max_discount_amount', 'min_order_amount',
        'usage_limit', 'usage_limit_per_user', 'used_count', 'first_order_only',
        'applies_to', 'exclude_discounted',
        'starts_at', 'expires_at', 'is_active',
    ];

    protected $casts = [
        'value'                => 'float',
        'max_discount_amount'  => 'float',
        'min_order_amount'     => 'float',
        'usage_limit'          => 'integer',
        'usage_limit_per_user' => 'integer',
        'used_count'           => 'integer',
        'first_order_only'     => 'boolean',
        'exclude_discounted'   => 'boolean',
        'is_active'            => 'boolean',
        'starts_at'            => 'datetime',
        'expires_at'           => 'datetime',
    ];

    /* ------------------------------ relations ------------------------------ */

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    /* ------------------------------ scopes ------------------------------ */

    /** Same rules as the "state" accessor below, but as SQL. */
    public function scopeState(Builder $query, string $state): Builder
    {
        $now = now();

        $started   = fn ($w) => $w->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
        $notEnded  = fn ($w) => $w->whereNull('expires_at')->orWhere('expires_at', '>=', $now);
        $hasUsesLeft = fn ($w) => $w->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit');

        return match ($state) {
            'inactive'  => $query->where('is_active', false),
            'scheduled' => $query->where('is_active', true)->where('starts_at', '>', $now),
            'expired'   => $query->where('is_active', true)->where('expires_at', '<', $now),
            'exhausted' => $query->where('is_active', true)
                ->whereNotNull('usage_limit')->whereColumn('used_count', '>=', 'usage_limit')
                ->where($started)->where($notEnded),
            'live'      => $query->where('is_active', true)->where($started)->where($notEnded)->where($hasUsesLeft),
            default     => $query,
        };
    }

    /** Coupons customers can use right now. */
    public function scopeLive(Builder $query): Builder
    {
        return $query->state('live');
    }

    /* ------------------------------ accessors ------------------------------ */

    public function getStateAttribute(): string
    {
        if (! $this->is_active) {
            return 'inactive';
        }
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return 'scheduled';
        }
        if ($this->expires_at && $this->expires_at->isPast()) {
            return 'expired';
        }
        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return 'exhausted';
        }

        return 'live';
    }

    /** "20% off, up to ৳500" / "৳100 off" / "Free delivery" */
    public function getDiscountLabelAttribute(): string
    {
        $money = SiteSetting::current();

        return match ($this->type) {
            'percentage'    => $this->trimNumber($this->value) . '% off'
                . ($this->max_discount_amount ? ', up to ' . $money->formatMoney($this->max_discount_amount) : ''),
            'fixed'         => $money->formatMoney($this->value) . ' off',
            'free_shipping' => 'Free delivery',
            default         => '',
        };
    }

    public function getMinOrderLabelAttribute(): ?string
    {
        return $this->min_order_amount
            ? 'Min. order ' . SiteSetting::current()->formatMoney($this->min_order_amount)
            : null;
    }

    public function getValidityLabelAttribute(): string
    {
        if (! $this->starts_at && ! $this->expires_at) {
            return 'No expiry';
        }

        $from = $this->starts_at?->format('d M Y') ?? 'now';
        $to   = $this->expires_at?->format('d M Y') ?? 'no end date';

        return "{$from} to {$to}";
    }

    public function getUsageLabelAttribute(): string
    {
        return $this->usage_limit !== null
            ? "{$this->used_count} / {$this->usage_limit}"
            : "{$this->used_count} used";
    }

    /* ------------------------------ checkout helpers ------------------------------ */

    /**
     * Why the coupon cannot be used right now, or null when it is fine.
     * $subtotal is the cart total that counts toward the minimum order.
     */
    public function validationError(?User $user, float $subtotal, int $previousOrders = 0): ?string
    {
        $money = SiteSetting::current();

        switch ($this->state) {
            case 'inactive':
                return 'This coupon is not active.';
            case 'scheduled':
                return 'This coupon is not valid yet.';
            case 'expired':
                return 'This coupon has expired.';
            case 'exhausted':
                return 'This coupon has reached its usage limit.';
        }

        if ($this->min_order_amount && $subtotal < $this->min_order_amount) {
            return 'Spend at least ' . $money->formatMoney($this->min_order_amount) . ' to use this coupon.';
        }

        if ($this->first_order_only && $previousOrders > 0) {
            return 'This coupon is only valid on your first order.';
        }

        if ($this->usage_limit_per_user) {
            if (! $user) {
                return 'Please log in to use this coupon.';
            }
            if ($this->usages()->where('user_id', $user->id)->count() >= $this->usage_limit_per_user) {
                return 'You have already used this coupon the maximum number of times.';
            }
        }

        return null;
    }

    /**
     * Discount amount.
     * $eligibleSubtotal: total of the cart items this coupon applies to.
     * $shippingCharge:   used by free-delivery coupons.
     */
    public function discountFor(float $eligibleSubtotal, float $shippingCharge = 0): float
    {
        $discount = match ($this->type) {
            'free_shipping' => $shippingCharge,
            'percentage'    => min($eligibleSubtotal * $this->value / 100, $this->max_discount_amount ?: INF),
            'fixed'         => min($this->value, $eligibleSubtotal),
            default         => 0,
        };

        return round(max($discount, 0), 2);
    }

    /**
     * Count one use. Call this when the order is placed.
     * Locks the row so two checkouts cannot both take the last use.
     */
    public function redeem(?User $user, float $discount, ?int $orderId = null): CouponUsage
    {
        return DB::transaction(function () use ($user, $discount, $orderId) {
            $fresh = static::whereKey($this->id)->lockForUpdate()->firstOrFail();

            if ($fresh->usage_limit !== null && $fresh->used_count >= $fresh->usage_limit) {
                throw new \RuntimeException('This coupon has reached its usage limit.');
            }

            $fresh->increment('used_count');

            return $fresh->usages()->create([
                'user_id'         => $user?->id,
                'order_id'        => $orderId,
                'discount_amount' => $discount,
            ]);
        });
    }

    private function trimNumber(float $n): string
    {
        return rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
    }
}

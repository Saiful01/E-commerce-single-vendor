<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public const PAYMENT_STATUSES = [
        'unpaid'   => 'Unpaid',
        'partial'  => 'Partly paid',
        'paid'     => 'Paid',
        'refunded' => 'Refunded',
    ];

    public const ZONES = [
        'inside_dhaka'  => 'Inside Dhaka',
        'outside_dhaka' => 'Outside Dhaka',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'subtotal'        => 'float',
        'discount_amount' => 'float',
        'shipping_charge' => 'float',
        'payment_fee'     => 'float',
        'total'           => 'float',
        'returned_total'  => 'float',
        'paid_amount'     => 'float',
        'courier_booked_at' => 'datetime',
        'shipped_at'      => 'datetime',
        'delivered_at'    => 'datetime',
        'cancelled_at'    => 'datetime',
        'restocked_at'    => 'datetime',
    ];

    /* ------------------------------ relations ------------------------------ */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class, 'order_status_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest('created_at')->latest('id');
    }

    public function paymentGateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class);
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(CourierService::class, 'courier_service_id');
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /** Every payment and refund. An order can be paid in many parts. */
    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayment::class)->latest('id');
    }

    public function returns(): HasMany
    {
        return $this->hasMany(OrderReturn::class)->latest('id');
    }

    public function courierEvents(): HasMany
    {
        return $this->hasMany(CourierEvent::class)->latest('id');
    }

    /* ------------------------------ scopes ------------------------------ */

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(fn ($w) => $w
            ->where('order_number', 'like', "%{$term}%")
            ->orWhere('customer_name', 'like', "%{$term}%")
            ->orWhere('customer_phone', 'like', "%{$term}%")
            ->orWhere('tracking_code', 'like', "%{$term}%"));
    }

    /* ------------------------------ accessors ------------------------------ */

    public function getPaymentStatusLabelAttribute(): string
    {
        return self::PAYMENT_STATUSES[$this->payment_status] ?? $this->payment_status;
    }

    public function getZoneLabelAttribute(): string
    {
        return self::ZONES[$this->delivery_zone] ?? $this->delivery_zone;
    }

    /** What the customer has to pay once the value of returned goods is taken off. */
    public function getPayableAmountAttribute(): float
    {
        return max(round($this->total - $this->returned_total, 2), 0);
    }

    public function getDueAmountAttribute(): float
    {
        return max(round($this->payable_amount - $this->paid_amount, 2), 0);
    }

    /** null | partially | fully (needs the items relation) */
    public function getReturnStateAttribute(): ?string
    {
        $returned = (int) $this->items->sum('returned_quantity');

        if ($returned < 1) {
            return null;
        }

        return $returned >= (int) $this->items->sum('quantity') ? 'fully' : 'partially';
    }

    /** What the courier says about the parcel, in plain words. */
    public function getCourierStatusLabelAttribute(): ?string
    {
        return $this->courier_status ? \App\Services\Couriers\SteadfastService::label($this->courier_status) : null;
    }

    /** Link the customer can use to follow the parcel. */
    public function getTrackingUrlAttribute(): ?string
    {
        return ($this->tracking_code && $this->courier)
            ? $this->courier->trackingUrlFor($this->tracking_code)
            : null;
    }

    /* ------------------------------ helpers ------------------------------ */

    /** ORD-261012-4821: date plus a random part, checked for uniqueness. */
    public static function generateNumber(): string
    {
        do {
            $number = 'ORD-' . now()->format('ymd') . '-' . random_int(1000, 9999);
        } while (static::where('order_number', $number)->exists());

        return $number;
    }
}

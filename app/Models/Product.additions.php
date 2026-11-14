<?php
// Existing App\Models\Product class er bhitore ei method gulo add korun (use Illuminate\Database\Eloquent\Builder; lagbe)

public function scopeActive(Builder $q): Builder   { return $q->where('is_active', true); }
public function scopeFeatured(Builder $q): Builder { return $q->where('is_featured', true); }

public function reviews() { return $this->hasMany(ProductReview::class); }

public function getHasDiscountAttribute(): bool
{
    return $this->discount_price !== null && (float) $this->discount_price > 0
        && (float) $this->discount_price < (float) $this->price;
}

public function getFinalPriceAttribute(): float
{
    return $this->has_discount ? (float) $this->discount_price : (float) $this->price;
}

public function getDiscountPercentAttribute(): int
{
    return $this->has_discount ? (int) round((1 - $this->discount_price / $this->price) * 100) : 0;
}

public function getInStockAttribute(): bool
{
    if ($this->has_variants && $this->relationLoaded('variants')) {
        return $this->variants->sum('stock_quantity') > 0;
    }
    return $this->has_variants ? true : $this->stock_quantity > 0;
}

public function getUrlAttribute(): string { return url('/product/' . $this->slug); }

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id', 'brand_id', 'name', 'slug', 'sku', 'price',
        'discount_price', 'stock_quantity', 'featured_image',
        'short_description', 'description', 'has_variants',
        'is_featured', 'is_active', 'seo_metadata'
    ];

    protected $casts = [
        'has_variants' => 'boolean',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'seo_metadata' => 'array',
    ];

    /* ------------------------------ scopes ------------------------------ */

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeFeatured(Builder $q): Builder
    {
        return $q->where('is_featured', true);
    }

    /* ------------------------------ accessors ------------------------------ */

    public function getFeaturedImageUrlAttribute(): string
    {
        $disk = config('filesystems.default');

        if (! $this->featured_image) {
            return asset('images/placeholder.png');
        }

        return $disk === 's3'
            ? Storage::disk('s3')->temporaryUrl($this->featured_image, now()->addMinutes(60))
            : Storage::disk($disk)->url($this->featured_image);
    }

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

    public function getUrlAttribute(): string
    {
        return url('/product/' . $this->slug);
    }

    /* ------------------------------ relations ------------------------------ */

    public function category() { return $this->belongsTo(Category::class); }
    public function brand() { return $this->belongsTo(Brand::class); }
    public function images() { return $this->hasMany(ProductImage::class); }
    public function variants() { return $this->hasMany(ProductVariant::class); }
    public function reviews() { return $this->hasMany(ProductReview::class); }
}

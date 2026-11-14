<?php

namespace App\Models;

use App\Models\Concerns\ResolvesStorageUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Brand extends Model
{
    use HasFactory, ResolvesStorageUrl;

    protected $fillable = [
        'name', 'slug', 'description', 'logo', 'website_url',
        'is_active', 'is_featured', 'sort_order',
        'meta_title', 'meta_description', 'meta_keywords',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'is_featured' => 'boolean',
        'sort_order'  => 'integer',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /* ------------------------------ scopes ------------------------------ */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /* ------------------------------ accessors ------------------------------ */

    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo) {
            return null;
        }

        // Logos saved before the move to the default (S3) disk still live on the local public disk
        if (config('filesystems.default') !== 'public' && Storage::disk('public')->exists($this->logo)) {
            return Storage::disk('public')->url($this->logo);
        }

        return $this->storageUrl($this->logo);
    }

    /** Storefront URL. Adjust if your brand route is different. */
    public function getUrlAttribute(): string
    {
        return url('/brand/' . $this->slug);
    }
}

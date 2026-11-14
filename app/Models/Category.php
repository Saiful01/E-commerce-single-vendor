<?php

namespace App\Models;

use App\Models\Concerns\ResolvesStorageUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Category extends Model
{
    use HasFactory, ResolvesStorageUrl;

    protected $fillable = [
        'parent_id', 'name', 'slug', 'description', 'image',
        'is_active', 'is_featured', 'show_in_menu', 'sort_order',
        'meta_title', 'meta_description', 'meta_keywords',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'is_featured'  => 'boolean',
        'show_in_menu' => 'boolean',
        'sort_order'   => 'integer',
    ];

    /* ------------------------------ relations ------------------------------ */

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

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

    public function scopeInMenu(Builder $query): Builder
    {
        return $query->where('show_in_menu', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /* ------------------------------ accessors ------------------------------ */

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image) {
            return null;
        }

        // Images saved before the move to the default (S3) disk still live on the local public disk
        if (config('filesystems.default') !== 'public' && Storage::disk('public')->exists($this->image)) {
            return Storage::disk('public')->url($this->image);
        }

        return $this->storageUrl($this->image);
    }

    /** Storefront URL. Adjust if your category route is different. */
    public function getUrlAttribute(): string
    {
        return url('/category/' . $this->slug);
    }

    /* ------------------------------ helpers ------------------------------ */

    /** IDs of every category below this one (children, grandchildren, ...). */
    public function descendantIds(): array
    {
        $found    = [];
        $frontier = [$this->id];

        while ($frontier) {
            $children = static::whereIn('parent_id', $frontier)->pluck('id')->all();
            $children = array_values(array_diff($children, $found));
            $found    = array_merge($found, $children);
            $frontier = $children;
        }

        return $found;
    }
}

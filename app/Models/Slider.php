<?php

namespace App\Models;

use App\Models\Concerns\ResolvesStorageUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Slider extends Model
{
    use ResolvesStorageUrl;

    /**
     * Every place a banner can appear. Add a new entry here and it shows up in
     * the admin form automatically. Render it with <x-banner-slot position="..." />.
     *
     * type: slider (rotating carousel) | grid (banners side by side) | popup (modal)
     */
    public const POSITIONS = [
        'home_hero' => [
            'label' => 'Home hero slider', 'type' => 'slider',
            'size' => '1920 × 720', 'mobile_size' => '800 × 600', 'ratio' => '8/3',
            'hint' => 'Main rotating banner at the top of the home page.',
        ],
        'home_category' => [
            'label' => 'Home category tiles', 'type' => 'grid',
            'size' => '600 × 600', 'mobile_size' => '600 × 600', 'ratio' => '1/1',
            'hint' => 'Square tiles that link to categories. Four tiles look best.',
        ],
        'home_middle' => [
            'label' => 'Home middle banners', 'type' => 'grid',
            'size' => '800 × 400', 'mobile_size' => '800 × 400', 'ratio' => '2/1',
            'hint' => 'Two or three promo banners between product sections.',
        ],
        'home_bottom' => [
            'label' => 'Home bottom strip', 'type' => 'grid',
            'size' => '1400 × 300', 'mobile_size' => '800 × 300', 'ratio' => '14/3',
            'hint' => 'Wide promo strip near the bottom of the home page.',
        ],
        'category_page' => [
            'label' => 'Category page banner', 'type' => 'grid',
            'size' => '1200 × 300', 'mobile_size' => '800 × 300', 'ratio' => '4/1',
            'hint' => 'Banner at the top of category and shop pages.',
        ],
        'sidebar' => [
            'label' => 'Sidebar banner', 'type' => 'grid',
            'size' => '400 × 500', 'mobile_size' => '400 × 500', 'ratio' => '4/5',
            'hint' => 'Tall banner beside product listings.',
        ],
        'popup' => [
            'label' => 'Promo popup', 'type' => 'popup',
            'size' => '600 × 750', 'mobile_size' => '600 × 750', 'ratio' => '4/5',
            'hint' => 'Shown once a day when a visitor lands. Only the first active banner is used.',
        ],
    ];

    public const LINK_TYPES = [
        'none'     => 'No link',
        'custom'   => 'Custom URL',
        'category' => 'Category',
        'product'  => 'Product',
        'brand'    => 'Brand',
    ];

    protected $fillable = [
        'title', 'subtitle', 'show_text', 'position', 'serial_order',
        'image', 'mobile_image',
        'btn_text', 'link_type', 'link_id', 'btn_link', 'open_new_tab',
        'text_color', 'text_align', 'overlay_opacity',
        'starts_at', 'ends_at', 'status',
    ];

    protected $casts = [
        'show_text'       => 'boolean',
        'open_new_tab'    => 'boolean',
        'status'          => 'boolean',
        'overlay_opacity' => 'integer',
        'serial_order'    => 'integer',
        'starts_at'       => 'datetime',
        'ends_at'         => 'datetime',
    ];

    /* ------------------------------ scopes ------------------------------ */

    /** Enabled and inside its schedule window right now. */
    public function scopeActive(Builder $query): Builder
    {
        $now = now();

        return $query->where('status', true)
            ->where(fn ($w) => $w->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($w) => $w->whereNull('ends_at')->orWhere('ends_at', '>=', $now));
    }

    public function scopeForPosition(Builder $query, string $position): Builder
    {
        return $query->where('position', $position);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('serial_order')->orderBy('id');
    }

    /* ------------------------------ accessors ------------------------------ */

    public function getImageUrlAttribute(): ?string
    {
        return $this->storageUrl($this->image);
    }

    /** Falls back to the desktop image when no mobile image was uploaded. */
    public function getMobileImageUrlAttribute(): ?string
    {
        return $this->storageUrl($this->mobile_image ?: $this->image);
    }

    public function getPositionLabelAttribute(): string
    {
        return self::POSITIONS[$this->position]['label'] ?? Str::headline($this->position);
    }

    /** live | scheduled | expired | inactive */
    public function getLiveStateAttribute(): string
    {
        if (! $this->status) {
            return 'inactive';
        }
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return 'scheduled';
        }
        if ($this->ends_at && $this->ends_at->isPast()) {
            return 'expired';
        }

        return 'live';
    }

    public function getScheduleLabelAttribute(): string
    {
        if (! $this->starts_at && ! $this->ends_at) {
            return 'Always on';
        }

        $from = $this->starts_at?->format('d M Y') ?? 'now';
        $to   = $this->ends_at?->format('d M Y') ?? 'no end date';

        return "{$from} to {$to}";
    }

    /** Short description of where the banner points, for the admin list. */
    public function getTargetLabelAttribute(): string
    {
        return match ($this->link_type) {
            'custom'   => $this->btn_link ?: 'Custom URL',
            'category' => 'Category: ' . (Category::find($this->link_id)?->name ?? 'deleted'),
            'product'  => 'Product: ' . (Product::find($this->link_id)?->name ?? 'deleted'),
            'brand'    => 'Brand: ' . (Brand::find($this->link_id)?->name ?? 'deleted'),
            default    => 'No link',
        };
    }

    /**
     * The URL the banner opens. Adjust the three path patterns below to match
     * your storefront routes.
     */
    public function getResolvedUrlAttribute(): ?string
    {
        return match ($this->link_type) {
            'custom'   => $this->normalizeCustomLink($this->btn_link),
            'category' => ($m = Category::find($this->link_id)) ? url('/category/' . ($m->slug ?? $m->id)) : null,
            'product'  => ($m = Product::find($this->link_id)) ? url('/product/' . $m->slug) : null,
            'brand'    => ($m = Brand::find($this->link_id)) ? url('/brand/' . ($m->slug ?? $m->id)) : null,
            default    => null,
        };
    }

    private function normalizeCustomLink(?string $link): ?string
    {
        if (! $link) {
            return null;
        }

        return Str::startsWith($link, ['http://', 'https://', '/', '#', 'mailto:', 'tel:'])
            ? $link
            : '/' . $link;
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\ResolvesStorageUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    use ResolvesStorageUrl;

    public const CACHE_KEY = 'site_settings.attributes';

    protected $fillable = [
        'site_name', 'site_tagline', 'site_logo', 'site_favicon', 'footer_text',
        'primary_color', 'secondary_color', 'header_bg_color', 'footer_bg_color', 'text_color',
        'primary_email', 'phone_number', 'whatsapp_number', 'address', 'business_hours',
        'facebook_url', 'instagram_url', 'youtube_url', 'twitter_url', 'linkedin_url', 'tiktok_url',
        'currency_symbol', 'currency_code', 'currency_position',
        'delivery_charge_inside_dhaka', 'delivery_charge_outside_dhaka', 'free_shipping_min',
        'meta_title', 'meta_description', 'meta_keywords', 'google_analytics_id', 'facebook_pixel_id',
    ];

    protected $casts = [
        'delivery_charge_inside_dhaka'  => 'float',
        'delivery_charge_outside_dhaka' => 'float',
        'free_shipping_min'             => 'float',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * The one settings row (created with defaults on first use, cached afterwards).
     * Usage anywhere: SiteSetting::current()->site_name
     *
     * Only the raw attributes array is cached. Laravel 13 refuses to unserialize
     * model objects from the cache, so the model is rebuilt on every call.
     */
    public static function current(): self
    {
        $attributes = Cache::rememberForever(self::CACHE_KEY, function () {
            $row = static::query()->first() ?? static::create([])->fresh();

            return $row->getAttributes();
        });

        return (new static)->newFromBuilder($attributes);
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->storageUrl($this->site_logo);
    }

    public function getFaviconUrlAttribute(): ?string
    {
        return $this->storageUrl($this->site_favicon);
    }

    /** Format a price using the configured symbol and position. */
    public function formatMoney(float|int|string $amount): string
    {
        $number = number_format((float) $amount, 2);

        return $this->currency_position === 'after'
            ? "{$number} {$this->currency_symbol}"
            : "{$this->currency_symbol}{$number}";
    }
}

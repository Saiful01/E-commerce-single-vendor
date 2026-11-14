<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Page extends Model
{
    public const TEMPLATES = [
        'standard' => 'Normal page',
        'faq'      => 'FAQ (questions and answers)',
        'contact'  => 'Contact page (shows a message form)',
    ];

    protected $fillable = [
        'title', 'slug', 'template', 'content', 'faq_items', 'meta_title', 'meta_description',
        'show_in_footer', 'show_in_header', 'sort_order', 'is_active', 'is_system',
    ];

    protected $casts = [
        'faq_items'      => 'array',
        'show_in_footer' => 'boolean',
        'show_in_header' => 'boolean',
        'is_active'      => 'boolean',
        'is_system'      => 'boolean',
        'sort_order'     => 'integer',
    ];

    public function scopeActive(Builder $q): Builder   { return $q->where('is_active', true); }
    public function scopeOrdered(Builder $q): Builder  { return $q->orderBy('sort_order')->orderBy('title'); }

    public function getUrlAttribute(): string
    {
        return url('/pages/' . $this->slug);
    }

    public function getTemplateLabelAttribute(): string
    {
        return self::TEMPLATES[$this->template] ?? $this->template;
    }

    /** Link to a page by slug, or null when it is missing or switched off. Used for the terms link at checkout. */
    public static function urlFor(string $slug): ?string
    {
        return static::active()->where('slug', $slug)->exists() ? url('/pages/' . $slug) : null;
    }

    /** Short plain text for search results, used when no meta description is written. */
    public function getExcerptAttribute(): string
    {
        return Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $this->content))), 155);
    }
}

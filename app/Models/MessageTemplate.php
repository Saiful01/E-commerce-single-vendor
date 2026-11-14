<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageTemplate extends Model
{
    protected $fillable = ['name', 'slug', 'channel', 'subject', 'body', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    /** Placeholder gulo: {customer_name}, {cart_total}, {site_name} ... */
    public static function replace(string $text, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $text = str_replace('{' . $key . '}', (string) $value, $text);
        }
        return $text;
    }

    public function render(array $vars): array
    {
        return [
            'subject' => $this->subject ? self::replace($this->subject, $vars) : null,
            'body' => self::replace($this->body, $vars),
        ];
    }

    public static function findActive(string $slug): ?self
    {
        return static::where('slug', $slug)->where('is_active', true)->first();
    }
}

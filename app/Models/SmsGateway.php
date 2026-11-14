<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsGateway extends Model
{
    protected $fillable = [
        'name', 'api_url', 'http_method', 'api_key', 'api_secret', 'sender_id', 'is_active',
    ];

    protected $hidden = ['api_key', 'api_secret'];

    protected $casts = [
        'api_key' => 'encrypted',
        'api_secret' => 'encrypted',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(function (self $gateway) {
            if ($gateway->is_active) {
                static::where('id', '!=', $gateway->id)->update(['is_active' => false]);
            }
        });
    }

    public static function active(): ?self
    {
        return static::where('is_active', true)->first();
    }
}

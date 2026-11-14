<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailConfiguration extends Model
{
    protected $fillable = [
        'name', 'host', 'port', 'username', 'password',
        'encryption', 'from_address', 'from_name', 'is_active',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'password' => 'encrypted',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Ekbar-e shudhu ekta active thakbe
        static::saved(function (self $config) {
            if ($config->is_active) {
                static::where('id', '!=', $config->id)->update(['is_active' => false]);
            }
        });
    }

    public static function active(): ?self
    {
        return static::where('is_active', true)->first();
    }
}

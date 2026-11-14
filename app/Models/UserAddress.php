<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAddress extends Model
{
    public const TYPES = [
        'shipping' => 'Shipping',
        'billing'  => 'Billing',
    ];

    protected $fillable = [
        'user_id', 'address_type', 'full_name', 'phone',
        'city', 'area', 'address_line', 'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** "House 12, Road 3, Pahartali, Chattogram" */
    public function getFullAddressAttribute(): string
    {
        return collect([$this->address_line, $this->area, $this->city])->filter()->implode(', ');
    }
}

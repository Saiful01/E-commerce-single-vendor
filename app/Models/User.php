<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'phone', 'password', 'user_type', 'status'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->user_type === 'admin';
    }

    public function isCustomer(): bool
    {
        return $this->user_type === 'customer';
    }

    /* ------------------------------ orders ------------------------------ */

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /* ------------------------------ addresses ------------------------------ */

    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class);
    }

    /** Default shipping address (used at checkout). */
    public function shippingAddress(): HasOne
    {
        return $this->hasOne(UserAddress::class)
            ->where('address_type', 'shipping')
            ->where('is_default', true);
    }

    /** Default billing address. */
    public function billingAddress(): HasOne
    {
        return $this->hasOne(UserAddress::class)
            ->where('address_type', 'billing')
            ->where('is_default', true);
    }
}

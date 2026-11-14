<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbandonedCart extends Model
{
    protected $fillable = [
        'user_id', 'session_id', 'email', 'phone', 'items', 'total',
        'reminder_count', 'last_reminded_at', 'recovered_at',
    ];

    protected $casts = [
        'items' => 'array',
        'total' => 'decimal:2',
        'last_reminded_at' => 'datetime',
        'recovered_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeOpen($query)
    {
        return $query->whereNull('recovered_at');
    }

    public function getCustomerNameAttribute(): string
    {
        return $this->user->name ?? 'Guest';
    }

    public function getContactEmailAttribute(): ?string
    {
        return $this->email ?: ($this->user->email ?? null);
    }

    public function getContactPhoneAttribute(): ?string
    {
        return $this->phone ?: ($this->user->phone ?? null);
    }

    public function getItemsCountAttribute(): int
    {
        return collect($this->items)->sum(fn ($i) => (int) ($i['qty'] ?? 1));
    }
}

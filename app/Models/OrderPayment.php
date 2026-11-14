<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPayment extends Model
{
    public const TYPES    = ['payment' => 'Payment', 'refund' => 'Refund'];
    public const STATUSES = ['pending' => 'Waiting for check', 'confirmed' => 'Confirmed', 'rejected' => 'Rejected'];

    protected $guarded = ['id'];

    protected $casts = [
        'amount'       => 'float',
        'confirmed_at' => 'datetime',
    ];

    public function order(): BelongsTo   { return $this->belongsTo(Order::class); }
    public function gateway(): BelongsTo { return $this->belongsTo(PaymentGateway::class, 'payment_gateway_id'); }
    public function user(): BelongsTo    { return $this->belongsTo(User::class); }

    public function scopeConfirmed(Builder $q): Builder { return $q->where('status', 'confirmed'); }
    public function scopePending(Builder $q): Builder   { return $q->where('status', 'pending'); }

    public function getIsRefundAttribute(): bool { return $this->type === 'refund'; }
}

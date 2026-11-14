<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnlinePayment extends Model
{
    public const STATUSES = [
        'initiated' => 'Started',
        'paid'      => 'Paid',
        'failed'    => 'Failed',
        'cancelled' => 'Cancelled',
        'review'    => 'Needs review',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'amount'   => 'float',
        'response' => 'array',
    ];

    public function order(): BelongsTo   { return $this->belongsTo(Order::class); }
    public function gateway(): BelongsTo { return $this->belongsTo(PaymentGateway::class, 'payment_gateway_id'); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderReturn extends Model
{
    public const STATUSES = [
        'requested' => 'Requested',
        'approved'  => 'Approved',
        'rejected'  => 'Rejected',
        'completed' => 'Completed',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'items_value'   => 'float',
        'refund_amount' => 'float',
        'restocked'     => 'boolean',
        'approved_at'   => 'datetime',
        'rejected_at'   => 'datetime',
        'completed_at'  => 'datetime',
    ];

    public function order(): BelongsTo   { return $this->belongsTo(Order::class); }
    public function user(): BelongsTo    { return $this->belongsTo(User::class); }
    public function handler(): BelongsTo { return $this->belongsTo(User::class, 'handled_by'); }
    public function items(): HasMany     { return $this->hasMany(OrderReturnItem::class); }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getReasonLabelAttribute(): string
    {
        return config('shop.return_reasons')[$this->reason] ?? $this->reason;
    }

    /** RET-261019-4821 */
    public static function generateNumber(): string
    {
        do {
            $number = 'RET-' . now()->format('ymd') . '-' . random_int(1000, 9999);
        } while (static::where('return_number', $number)->exists());

        return $number;
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\ResolvesStorageUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use ResolvesStorageUrl;

    protected $guarded = ['id'];

    protected $casts = [
        'unit_price'        => 'float',
        'line_total'        => 'float',
        'quantity'          => 'integer',
        'returned_quantity' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->storageUrl($this->image);
    }
}

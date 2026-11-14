<?php

namespace App\Models;

use App\Models\Concerns\ResolvesStorageUrl;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use ResolvesStorageUrl;

    protected $fillable = [
        'product_id', 'attribute_name', 'attribute_value',
        'sku', 'price', 'stock_quantity', 'image',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->storageUrl($this->image);
    }
}

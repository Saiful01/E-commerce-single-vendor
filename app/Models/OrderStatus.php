<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OrderStatus extends Model
{
    protected $fillable = [
        'name', 'slug', 'color', 'description', 'sort_order',
        'is_default', 'is_final', 'restock_items', 'notify_customer', 'is_system', 'is_active',
    ];

    protected $casts = [
        'sort_order'      => 'integer',
        'is_default'      => 'boolean',
        'is_final'        => 'boolean',
        'restock_items'   => 'boolean',
        'notify_customer' => 'boolean',
        'is_system'       => 'boolean',
        'is_active'       => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /** The status every new order starts with. */
    public static function default(): ?self
    {
        return static::active()->where('is_default', true)->first();
    }

    /**
     * How many orders use this status.
     * Returns 0 until the orders table exists; once you build Orders, turn this into a real relation.
     */
    public function ordersCount(): int
    {
        static $ready = null;
        $ready ??= Schema::hasTable('orders') && Schema::hasColumn('orders', 'order_status_id');

        return $ready ? DB::table('orders')->where('order_status_id', $this->id)->count() : 0;
    }
}

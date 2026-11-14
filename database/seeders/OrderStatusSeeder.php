<?php

namespace Database\Seeders;

use App\Models\OrderStatus;
use Illuminate\Database\Seeder;

/**
 * Safe to run many times: it only adds the statuses that are missing.
 *
 *   php artisan db:seed --class=OrderStatusSeeder
 */
class OrderStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            // slug        name          color      description                                     default final  restock notify
            ['pending',    'Pending',    '#F59E0B', 'Order placed, waiting for confirmation.',       true,  false, false, true],
            ['confirmed',  'Confirmed',  '#3B82F6', 'Order confirmed with the customer.',            false, false, false, true],
            ['processing', 'Processing', '#8B5CF6', 'Items are being packed.',                       false, false, false, false],
            ['shipped',    'Shipped',    '#0EA5E9', 'Handed over to the courier.',                   false, false, false, true],
            ['delivered',  'Delivered',  '#10B981', 'Customer received the order.',                  false, true,  false, true],
            ['cancelled',  'Cancelled',  '#EF4444', 'Order was cancelled.',                          false, true,  true,  true],
            ['returned',   'Returned',   '#F97316', 'Order came back from the customer.',            false, true,  true,  true],
        ];

        foreach ($statuses as $i => [$slug, $name, $color, $description, $default, $final, $restock, $notify]) {
            OrderStatus::firstOrCreate(['slug' => $slug], [
                'name'            => $name,
                'color'           => $color,
                'description'     => $description,
                'sort_order'      => $i + 1,
                'is_default'      => $default,
                'is_final'        => $final,
                'restock_items'   => $restock,
                'notify_customer' => $notify,
                'is_system'       => true,
                'is_active'       => true,
            ]);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\MessageTemplate;
use Illuminate\Database\Seeder;

class EcommerceExtrasSeeder extends Seeder
{
    /**
     * PERMISSIONS:
     * Tomar project-e permission kivabe kaj kore (Spatie / custom Gate) seta
     * ami dekhte pai ni. Spatie hole niche-r block uncomment koro.
     * Gate::define diye korle AuthServiceProvider-e ei 7 ta name add koro.
     */
    public const PERMISSIONS = [
        'manage-wishlists',
        'manage-product-reviews',
        'manage-abandoned-carts',
        'manage-sms-email-templates',
        'manage-email-configurations',
        'manage-sms-gateways',
        'manage-custom-messages',
    ];

    public function run(): void
    {
        /* --- Spatie Permission hole ---
        foreach (self::PERMISSIONS as $name) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
        \Spatie\Permission\Models\Role::where('name', 'Super Admin')->first()
            ?->givePermissionTo(self::PERMISSIONS);
        */

        $templates = [
            [
                'name' => 'Abandoned Cart - Email',
                'slug' => 'abandoned-cart-email',
                'channel' => 'email',
                'subject' => 'You left something behind at {site_name}',
                'body' => "Hi {customer_name},\n\nYou left {items_count} item(s) worth {cart_total} in your cart.\nComplete your order here: {cart_url}\n\nThanks,\n{site_name}",
            ],
            [
                'name' => 'Abandoned Cart - SMS',
                'slug' => 'abandoned-cart-sms',
                'channel' => 'sms',
                'subject' => null,
                'body' => 'Hi {customer_name}, your cart ({cart_total}) is waiting at {site_name}. Checkout: {cart_url}',
            ],
            [
                'name' => 'Order Confirmation - Email',
                'slug' => 'order-confirmation-email',
                'channel' => 'email',
                'subject' => 'Order {order_number} confirmed',
                'body' => "Hi {customer_name},\n\nThank you for your order {order_number}. We'll notify you when it ships.\n\n{site_name}",
            ],
            [
                'name' => 'Order Confirmation - SMS',
                'slug' => 'order-confirmation-sms',
                'channel' => 'sms',
                'subject' => null,
                'body' => 'Thanks {customer_name}! Order {order_number} confirmed. - {site_name}',
            ],
        ];

        foreach ($templates as $t) {
            MessageTemplate::firstOrCreate(['slug' => $t['slug']], $t + ['is_active' => true]);
        }
    }
}

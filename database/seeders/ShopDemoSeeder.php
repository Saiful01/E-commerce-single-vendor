<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\CourierService;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\Slider;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Demo data for: payment gateways, courier services, coupons and banners (sliders).
 * Safe to run again: every row is matched by its name / code / title, so nothing is duplicated.
 *
 *   php artisan db:seed --class=ShopDemoSeeder
 *
 * Replace the dummy bank / bKash / Nagad numbers before the shop goes live.
 */
class ShopDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->paymentGateways();
        $this->couriers();
        $this->coupons();
        $this->sliders();
    }

    /* ------------------------------------------------------------------ */
    private function paymentGateways(): void
    {
        $rows = [
            [
                'name' => 'Cash on delivery', 'driver' => 'cod', 'is_active' => true, 'sort_order' => 1,
                'instructions' => 'Pay in cash when the parcel reaches you. Please keep the exact amount ready.',
                'max_amount' => 50000,
            ],
            [
                'name' => 'bKash (send money)', 'driver' => 'bkash_manual', 'is_active' => true, 'sort_order' => 2,
                'instructions' => "Send the order total to the bKash number below, then enter the transaction ID.\nUse the order number as reference.",
                'credentials' => ['account_number' => '01700000000', 'account_type' => 'Personal'],
            ],
            [
                'name' => 'Nagad (send money)', 'driver' => 'nagad_manual', 'is_active' => true, 'sort_order' => 3,
                'instructions' => 'Send the order total to the Nagad number below, then enter the transaction ID.',
                'credentials' => ['account_number' => '01800000000', 'account_type' => 'Personal'],
                'fee_type' => 'percentage', 'fee_value' => 1.5,   // example of a payment fee
            ],
            [
                'name' => 'Bank transfer', 'driver' => 'bank_transfer', 'is_active' => true, 'sort_order' => 4,
                'instructions' => 'Transfer the order total to the account below. We confirm the order once the money arrives.',
                'credentials' => ['bank_name' => 'Demo Bank Ltd', 'account_name' => 'Your Shop Name',
                    'account_number' => '0000000000000', 'branch' => 'Agrabad, Chattogram'],
                'min_amount' => 1000,
            ],
            // Online gateways: off until the API keys are entered in the admin panel
            ['name' => 'bKash (payment API)', 'driver' => 'bkash',      'is_active' => false, 'sort_order' => 5],
            ['name' => 'Nagad (payment API)', 'driver' => 'nagad',      'is_active' => false, 'sort_order' => 6],
            ['name' => 'SSLCommerz',          'driver' => 'sslcommerz', 'is_active' => false, 'sort_order' => 7, 'sandbox_mode' => true],
            ['name' => 'Stripe',              'driver' => 'stripe',     'is_active' => false, 'sort_order' => 8, 'sandbox_mode' => true],
        ];

        foreach ($rows as $row) {
            PaymentGateway::updateOrCreate(['name' => $row['name']], $row + ['fee_type' => 'none', 'fee_value' => 0]);
        }

        $this->command?->info('Payment gateways: ' . count($rows));
        $this->command?->warn('The bank / bKash / Nagad numbers are dummy values. Change them before going live.');
    }

    /* ------------------------------------------------------------------ */
    private function couriers(): void
    {
        $rows = [
            // Manual couriers work straight away
            [
                'name' => 'Own delivery', 'driver' => 'manual', 'is_default' => true, 'is_active' => true,
                'phone' => '01700000001', 'delivery_time' => 'Same day to 2 days',
                'coverage_note' => 'Chattogram city only.',
            ],
            [
                'name' => 'Sundarban Courier', 'driver' => 'manual', 'is_active' => true,
                'delivery_time' => '2-4 days', 'coverage_note' => 'Branch pickup available in most districts.',
            ],
            [
                'name' => 'SA Paribahan', 'driver' => 'manual', 'is_active' => true,
                'delivery_time' => '2-4 days', 'coverage_note' => 'Counter to counter, all districts.',
            ],
            // API couriers: off until the credentials are entered
            [
                'name' => 'Steadfast', 'driver' => 'steadfast', 'is_active' => false,
                'website' => 'https://steadfast.com.bd', 'tracking_url' => 'https://steadfast.com.bd/t/{tracking_code}',
                'delivery_time' => '1-3 days inside Dhaka, 2-4 days outside',
            ],
            [
                'name' => 'Pathao Courier', 'driver' => 'pathao', 'is_active' => false,
                'website' => 'https://pathao.com', 'delivery_time' => '1-3 days',
            ],
            [
                'name' => 'RedX', 'driver' => 'redx', 'is_active' => false,
                'website' => 'https://redx.com.bd', 'tracking_url' => 'https://redx.com.bd/track-global-parcel/?trackingId={tracking_code}',
                'delivery_time' => '1-3 days',
            ],
            ['name' => 'Paperfly', 'driver' => 'paperfly', 'is_active' => false, 'delivery_time' => '2-3 days'],
            ['name' => 'eCourier', 'driver' => 'ecourier', 'is_active' => false, 'delivery_time' => '2-3 days'],
        ];

        foreach ($rows as $row) {
            CourierService::updateOrCreate(['name' => $row['name']], $row + ['is_default' => false]);
        }

        // only one default courier
        $default = CourierService::where('name', 'Own delivery')->first();
        CourierService::where('id', '!=', $default->id)->update(['is_default' => false]);

        $this->command?->info('Couriers: ' . count($rows) . '. Verify the tracking links before relying on them.');
    }

    /* ------------------------------------------------------------------ */
    private function coupons(): void
    {
        $rows = [
            // live
            ['code' => 'WELCOME10', 'name' => 'First order 10%', 'description' => '10% off your first order, up to 500.',
                'type' => 'percentage', 'value' => 10, 'max_discount_amount' => 500, 'min_order_amount' => 1000,
                'first_order_only' => true, 'usage_limit_per_user' => 1],
            ['code' => 'FLAT100', 'name' => 'Flat 100 off', 'description' => '100 off orders above 800.',
                'type' => 'fixed', 'value' => 100, 'min_order_amount' => 800],
            ['code' => 'FREESHIP', 'name' => 'Free delivery', 'description' => 'Free delivery on orders above 1500.',
                'type' => 'free_shipping', 'value' => 0, 'min_order_amount' => 1500],
            ['code' => 'SAVE15', 'name' => '15% on regular price items', 'description' => '15% off items that are not already on sale.',
                'type' => 'percentage', 'value' => 15, 'max_discount_amount' => 1000, 'exclude_discounted' => true,
                'expires_at' => now()->addDays(30), 'usage_limit' => 200],
            // examples of the other states
            ['code' => 'RAMADAN20', 'name' => 'Upcoming campaign (scheduled)', 'type' => 'percentage', 'value' => 20,
                'max_discount_amount' => 800, 'starts_at' => now()->addDays(14), 'expires_at' => now()->addDays(45)],
            ['code' => 'OLD25', 'name' => 'Old campaign (expired)', 'type' => 'percentage', 'value' => 25,
                'starts_at' => now()->subDays(60), 'expires_at' => now()->subDays(5)],
            ['code' => 'LASTONE', 'name' => 'Used up (exhausted)', 'type' => 'fixed', 'value' => 200,
                'usage_limit' => 1, 'used_count' => 1],
        ];

        foreach ($rows as $row) {
            Coupon::updateOrCreate(['code' => $row['code']], $row + ['is_active' => true]);
        }

        // A coupon limited to categories, when categories exist
        $categories = Category::active()->take(2)->get();
        if ($categories->isNotEmpty()) {
            $coupon = Coupon::updateOrCreate(['code' => 'CATEGORY10'], [
                'name' => '10% on selected categories', 'description' => '10% off ' . $categories->pluck('name')->join(' and ') . '.',
                'type' => 'percentage', 'value' => 10, 'max_discount_amount' => 600, 'applies_to' => 'categories', 'is_active' => true,
            ]);
            $coupon->categories()->sync($categories->pluck('id'));
        }

        // A coupon limited to one brand, when a brand exists
        if ($brand = Brand::active()->first()) {
            $coupon = Coupon::updateOrCreate(['code' => 'BRAND5'], [
                'name' => "5% on {$brand->name}", 'description' => "5% off {$brand->name} products.",
                'type' => 'percentage', 'value' => 5, 'applies_to' => 'brands', 'is_active' => true,
            ]);
            $coupon->brands()->sync([$brand->id]);
        }

        $this->command?->info('Coupons seeded (live, scheduled, expired and used-up examples).');
    }

    /* ------------------------------------------------------------------ */
    private function sliders(): void
    {
        $categories = Category::active()->take(4)->get();
        $product    = Product::active()->first();

        $toShop = ['link_type' => 'custom', 'link_id' => null, 'btn_link' => '/shop'];
        $toCat  = fn (int $i) => isset($categories[$i]) ? ['link_type' => 'category', 'link_id' => $categories[$i]->id, 'btn_link' => null] : $toShop;
        $toProd = $product ? ['link_type' => 'product', 'link_id' => $product->id, 'btn_link' => null] : $toShop;

        // [position, title, subtitle, button, link, background colour, extra]
        $rows = [
            ['home_hero', 'Fresh arrivals are here', 'New styles added every week', 'Shop now', $toShop, '#0f766e', ['text_align' => 'left']],
            ['home_hero', 'Weekend flash sale', 'Up to 40% off selected items', 'See deals', ['link_type' => 'custom', 'link_id' => null, 'btn_link' => '/shop?sort=discount'], '#b45309', ['text_align' => 'left']],
            ['home_hero', 'Free delivery on bigger orders', 'Order today, get it in 1-3 days', 'Start shopping', $toProd, '#1d4ed8', ['text_align' => 'center']],
            // schedule examples: one waiting to start, one already over
            ['home_hero', 'Upcoming campaign (scheduled)', 'Starts in 10 days', 'Notify me', $toShop, '#7e22ce', ['starts_at' => now()->addDays(10), 'ends_at' => now()->addDays(40)]],
            ['home_hero', 'Old campaign (expired)', 'This one has ended', 'Shop now', $toShop, '#475569', ['ends_at' => now()->subDay()]],

            ['home_middle', 'Gift ideas under 1,000', 'Easy picks for everyone', 'Browse gifts', $toShop, '#be123c', []],
            ['home_middle', 'Best sellers', 'What everyone is buying', 'View all', ['link_type' => 'custom', 'link_id' => null, 'btn_link' => '/shop?featured=1'], '#0369a1', []],

            ['home_bottom', 'Join us and get 10% off your first order', 'Use code WELCOME10 at checkout', 'Create account', ['link_type' => 'custom', 'link_id' => null, 'btn_link' => '/register'], '#15803d', ['text_align' => 'center']],

            ['category_page', 'Find exactly what you need', 'Use the filters on the left', null, $toShop, '#334155', ['text_align' => 'left']],
            ['sidebar', 'Weekend sale', 'Up to 40% off', 'Shop', $toShop, '#c2410c', ['text_align' => 'center']],
            ['popup', 'Welcome gift: 10% off', 'Use code WELCOME10 on your first order', 'Shop now', $toShop, '#0f766e', ['text_align' => 'center', 'ends_at' => now()->addDays(30)]],
        ];

        // four square category tiles (real categories when there are some)
        foreach (range(0, 3) as $i) {
            $rows[] = ['home_category', $categories->get($i)?->name ?? 'Category ' . ($i + 1), null, null, $toCat($i),
                ['#0f766e', '#b45309', '#1d4ed8', '#be123c'][$i], ['text_align' => 'center']];
        }

        $order = [];
        try {
            foreach ($rows as [$position, $title, $subtitle, $button, $link, $colour, $extra]) {
                $meta = Slider::POSITIONS[$position];
                $n    = $order[$position] = ($order[$position] ?? 0) + 1;
                $slug = $position . '-' . $n;

                Slider::updateOrCreate(['position' => $position, 'title' => $title], [
                        'subtitle'        => $subtitle,
                        'show_text'       => true,
                        'serial_order'    => $n,
                        'image'           => $this->placeholder($slug, $meta['size'], $colour),
                        'mobile_image'    => $position === 'home_hero' ? $this->placeholder($slug . '-mobile', $meta['mobile_size'], $colour) : null,
                        'btn_text'        => $button,
                        'text_color'      => '#FFFFFF',
                        'overlay_opacity' => 25,
                        'status'          => true,
                    ] + $link + $extra);
            }
        } catch (\Throwable $e) {
            $this->command?->warn('Banners skipped: could not save the placeholder images (' . $e->getMessage() . '). Check the filesystem disk in .env.');
            return;
        }

        $this->command?->info('Banners: ' . count($rows) . '. They use plain coloured placeholder images, so upload real ones from the admin panel.');
    }

    /** Writes a simple coloured SVG on the default disk and returns its path. */
    private function placeholder(string $name, string $size, string $colour): string
    {
        [$w, $h] = array_map('intval', preg_split('/\s*×\s*/u', $size));
        $r = (int) round(min($w, $h) * 0.55);

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$w}" height="{$h}" viewBox="0 0 {$w} {$h}">
  <rect width="{$w}" height="{$h}" fill="{$colour}"/>
  <circle cx="{$w}" cy="0" r="{$r}" fill="#ffffff" fill-opacity="0.09"/>
  <circle cx="0" cy="{$h}" r="{$r}" fill="#000000" fill-opacity="0.10"/>
</svg>
SVG;

        $path = "sliders/demo-{$name}.svg";
        Storage::put($path, $svg);

        return $path;
    }
}

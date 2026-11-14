<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Fills the shop with sample categories, brands and products so every page has something to show.
 *
 *   php artisan db:seed --class=CatalogDemoSeeder
 *
 * Safe to run again: anything that already exists (matched by slug or SKU) is left alone,
 * so your own edits are never overwritten. No images are added, upload them from the admin panel.
 */
class CatalogDemoSeeder extends Seeder
{
    /** Column names of each table, so the seeder still works if an optional migration has not been run. */
    private array $columns = [];

    public function run(): void
    {
        $counts = ['categories' => 0, 'brands' => 0, 'products' => 0, 'variants' => 0];

        DB::transaction(function () use (&$counts) {
            $categories = $this->seedCategories($counts);
            $brands     = $this->seedBrands($counts);
            $this->seedProducts($categories, $brands, $counts);
        });

        $this->command?->info(sprintf(
            'Added %d categories, %d brands, %d products and %d variants.',
            $counts['categories'], $counts['brands'], $counts['products'], $counts['variants']
        ));
    }

    /* ---------------------------------------------------------------
     |  CATEGORIES
     * --------------------------------------------------------------*/
    private function seedCategories(array &$counts): array
    {
        $tree = [
            'Electronics' => [
                'featured' => true,
                'description' => 'Phones, laptops, audio and everyday gadgets.',
                'children' => ['Mobile Phones', 'Laptops', 'Headphones & Audio'],
            ],
            'Fashion' => [
                'featured' => true,
                'description' => 'Clothing and footwear for the whole family.',
                'children' => ["Men's Clothing", "Women's Clothing", 'Footwear'],
            ],
            'Home & Kitchen' => [
                'featured' => true,
                'description' => 'Appliances and decor that make a home.',
                'children' => ['Kitchen Appliances', 'Home Decor'],
            ],
            'Beauty & Care' => [
                'featured' => false,
                'description' => 'Skin care, fragrances and personal care.',
                'children' => ['Skin Care', 'Fragrances'],
            ],
            'Sports & Fitness' => [
                'featured' => false,
                'description' => 'Gear to stay active.',
                'children' => ['Fitness Equipment'],
            ],
            'Books & Stationery' => [
                'featured' => false,
                'description' => 'Notebooks, pens and office basics.',
                'children' => [],
            ],
        ];

        $made  = [];
        $order = 0;

        foreach ($tree as $name => $info) {
            $parent = $this->makeCategory($name, null, $info['description'], $info['featured'], ++$order, $counts);
            $made[$name] = $parent;

            $childOrder = 0;
            foreach ($info['children'] as $childName) {
                $made[$childName] = $this->makeCategory($childName, $parent->id, null, false, ++$childOrder, $counts);
            }
        }

        return $made;
    }

    private function makeCategory(string $name, ?int $parentId, ?string $description, bool $featured, int $order, array &$counts): Category
    {
        $slug = Str::slug($name);

        $category = Category::firstOrCreate(['slug' => $slug], $this->only('categories', [
            'parent_id'        => $parentId,
            'name'             => $name,
            'slug'             => $slug,
            'description'      => $description ?? "Shop {$name}.",
            'is_active'        => true,
            'is_featured'      => $featured,
            'show_in_menu'     => true,
            'sort_order'       => $order,
            'meta_title'       => $name,
            'meta_description' => "Buy {$name} online at the best price with fast delivery.",
        ]));

        $category->wasRecentlyCreated && $counts['categories']++;

        return $category;
    }

    /* ---------------------------------------------------------------
     |  BRANDS
     * --------------------------------------------------------------*/
    private function seedBrands(array &$counts): array
    {
        // name => [website, featured]
        $list = [
            'Samsung'  => ['https://www.samsung.com', true],
            'Xiaomi'   => ['https://www.mi.com', false],
            'Apple'    => ['https://www.apple.com', true],
            'Lenovo'   => ['https://www.lenovo.com', false],
            'HP'       => ['https://www.hp.com', false],
            'Sony'     => ['https://www.sony.com', true],
            'JBL'      => ['https://www.jbl.com', false],
            'Nike'     => ['https://www.nike.com', true],
            'Adidas'   => ['https://www.adidas.com', false],
            'Puma'     => ['https://www.puma.com', false],
            'Aarong'   => ['https://www.aarong.com', false],
            'Yellow'   => ['https://www.yellowclothing.net', false],
            'Philips'  => ['https://www.philips.com', true],
            'Walton'   => ['https://waltonbd.com', true],
            'Nivea'    => ['https://www.nivea.com', false],
            'Yardley'  => ['https://www.yardleylondon.com', false],
        ];

        $made  = [];
        $order = 0;

        foreach ($list as $name => [$website, $featured]) {
            $slug = Str::slug($name);

            $brand = Brand::firstOrCreate(['slug' => $slug], $this->only('brands', [
                'name'             => $name,
                'slug'             => $slug,
                'description'      => "Official {$name} products.",
                'website_url'      => $website,
                'is_active'        => true,
                'is_featured'      => $featured,
                'sort_order'       => ++$order,
                'meta_title'       => "{$name} products",
                'meta_description' => "Shop genuine {$name} products online.",
            ]));

            $brand->wasRecentlyCreated && $counts['brands']++;
            $made[$name] = $brand;
        }

        return $made;
    }

    /* ---------------------------------------------------------------
     |  PRODUCTS
     * --------------------------------------------------------------*/
    private function seedProducts(array $categories, array $brands, array &$counts): void
    {
        // Products without a photo: null if the column allows it, otherwise an empty string
        $noImage = $this->emptyImageValue();

        foreach ($this->products() as $p) {
            $variants = $p['variants'] ?? [];

            $product = Product::firstOrCreate(['sku' => $p['sku']], $this->only('products', [
                'category_id'       => $categories[$p['category']]->id,
                'brand_id'          => isset($p['brand']) ? $brands[$p['brand']]->id : null,
                'name'              => $p['name'],
                'slug'              => Str::slug($p['name']),
                'sku'               => $p['sku'],
                // products with options show their lowest price, and their stock is the sum of the options
                'price'             => $variants ? min(array_column($variants, 1)) : $p['price'],
                'discount_price'    => $variants ? null : ($p['discount'] ?? null),
                'stock_quantity'    => $variants ? array_sum(array_column($variants, 2)) : $p['stock'],
                'featured_image'    => $noImage,
                'short_description' => $p['short'],
                'description'       => $p['description'],
                'has_variants'      => (bool) $variants,
                'is_featured'       => $p['featured'] ?? false,
                'is_active'         => true,
                'seo_metadata'      => [
                    'meta_title'       => $p['name'],
                    'meta_description' => $p['short'],
                    'meta_keywords'    => implode(', ', array_filter([$p['category'], $p['brand'] ?? null])),
                ],
            ]));

            if (! $product->wasRecentlyCreated) {
                continue;
            }

            $counts['products']++;

            foreach ($variants as [$value, $price, $stock]) {
                $product->variants()->create($this->only('product_variants', [
                    'attribute_name'  => $p['option'],
                    'attribute_value' => $value,
                    'sku'             => $p['sku'] . '-' . strtoupper(Str::slug($value)),
                    'price'           => $price,
                    'stock_quantity'  => $stock,
                ]));
                $counts['variants']++;
            }
        }
    }

    /**
     * The sample products.
     * variants: [value, price, stock]. A product with variants takes its price and stock from them.
     */
    private function products(): array
    {
        return [
            // ---------------------------------------------------- Mobile phones
            [
                'sku' => 'MOB-SAM-A15', 'name' => 'Samsung Galaxy A15 128GB', 'category' => 'Mobile Phones', 'brand' => 'Samsung',
                'featured' => true, 'option' => 'Color',
                'variants' => [['Blue Black', 24990, 12], ['Light Blue', 24990, 9], ['Yellow', 25490, 6]],
                'short' => '6.5 inch Super AMOLED display, 50MP camera and a 5000mAh battery.',
                'description' => "A dependable everyday phone.\n\n- 6.5 inch Super AMOLED, 90Hz\n- 50MP triple camera\n- 5000mAh battery with fast charging\n- 128GB storage, microSD slot\n- Official warranty",
            ],
            [
                'sku' => 'MOB-XIA-RN13', 'name' => 'Xiaomi Redmi Note 13 8GB/128GB', 'category' => 'Mobile Phones', 'brand' => 'Xiaomi',
                'price' => 27500, 'discount' => 25990, 'stock' => 30, 'featured' => true,
                'short' => '120Hz AMOLED screen and a 108MP main camera at a friendly price.',
                'description' => "Big screen, big camera.\n\n- 6.67 inch 120Hz AMOLED\n- 108MP main camera\n- 5000mAh battery, 33W charging\n- 8GB RAM, 128GB storage",
            ],
            [
                'sku' => 'MOB-APL-IP13', 'name' => 'Apple iPhone 13 128GB', 'category' => 'Mobile Phones', 'brand' => 'Apple',
                'price' => 79900, 'stock' => 6,
                'short' => 'A15 Bionic chip, dual 12MP cameras and all-day battery life.',
                'description' => "Still one of the best phones to own.\n\n- 6.1 inch Super Retina XDR display\n- A15 Bionic chip\n- Dual 12MP camera system\n- 128GB storage",
            ],

            // ---------------------------------------------------- Laptops
            [
                'sku' => 'LAP-LEN-IP3', 'name' => 'Lenovo IdeaPad Slim 3 15 inch', 'category' => 'Laptops', 'brand' => 'Lenovo',
                'price' => 62500, 'discount' => 59900, 'stock' => 10, 'featured' => true,
                'short' => 'Core i5, 8GB RAM and 512GB SSD in a slim, light body.',
                'description' => "For study and work.\n\n- Intel Core i5 processor\n- 8GB RAM, 512GB SSD\n- 15.6 inch Full HD display\n- Windows 11, 2 year warranty",
            ],
            [
                'sku' => 'LAP-HP-15S', 'name' => 'HP 15s Laptop Ryzen 5', 'category' => 'Laptops', 'brand' => 'HP',
                'price' => 58000, 'stock' => 8,
                'short' => 'Ryzen 5 power with a full HD anti-glare screen.',
                'description' => "A reliable all-rounder.\n\n- AMD Ryzen 5 processor\n- 8GB RAM, 512GB SSD\n- 15.6 inch Full HD, anti-glare\n- Long battery life",
            ],

            // ---------------------------------------------------- Headphones & audio
            [
                'sku' => 'AUD-SNY-CH520', 'name' => 'Sony WH-CH520 Wireless Headphones', 'category' => 'Headphones & Audio', 'brand' => 'Sony',
                'price' => 5490, 'discount' => 4490, 'stock' => 40, 'featured' => true,
                'short' => 'Up to 50 hours of battery and comfortable on-ear fit.',
                'description' => "Clear sound, all day.\n\n- Up to 50 hours of playback\n- Bluetooth with multipoint\n- Built-in microphone for calls\n- Lightweight design",
            ],
            [
                'sku' => 'AUD-JBL-T510', 'name' => 'JBL Tune 510BT Wireless Headphones', 'category' => 'Headphones & Audio', 'brand' => 'JBL',
                'price' => 4200, 'stock' => 35,
                'short' => 'JBL Pure Bass sound with up to 40 hours of battery.',
                'description' => "Bass you can feel.\n\n- JBL Pure Bass sound\n- Up to 40 hours of battery\n- Fast charging\n- Foldable design",
            ],

            // ---------------------------------------------------- Men's clothing
            [
                'sku' => 'MEN-NIK-TEE', 'name' => 'Nike Dri-FIT Training T-Shirt', 'category' => "Men's Clothing", 'brand' => 'Nike',
                'option' => 'Size',
                'variants' => [['M', 2490, 15], ['L', 2490, 20], ['XL', 2490, 10], ['XXL', 2690, 5]],
                'short' => 'Sweat-wicking fabric that keeps you dry through the workout.',
                'description' => "Made for training.\n\n- Dri-FIT moisture-wicking fabric\n- Relaxed fit\n- Soft, breathable feel",
            ],
            [
                'sku' => 'MEN-ADI-JOG', 'name' => 'Adidas Essentials Joggers', 'category' => "Men's Clothing", 'brand' => 'Adidas',
                'price' => 3990, 'discount' => 3490, 'stock' => 25,
                'short' => 'Soft fleece joggers with a tapered leg and drawcord waist.',
                'description' => "Easy comfort.\n\n- Soft cotton-blend fleece\n- Elastic waist with drawcord\n- Side pockets",
            ],

            // ---------------------------------------------------- Women's clothing
            [
                'sku' => 'WOM-AAR-SK', 'name' => 'Aarong Cotton Salwar Kameez', 'category' => "Women's Clothing", 'brand' => 'Aarong',
                'price' => 4850, 'stock' => 15, 'featured' => true,
                'short' => 'Hand-finished cotton three piece with a block print.',
                'description' => "Comfortable and elegant.\n\n- Pure cotton fabric\n- Block print design\n- Three piece set with dupatta",
            ],
            [
                'sku' => 'WOM-YEL-KUR', 'name' => 'Yellow Women\'s Casual Kurti', 'category' => "Women's Clothing", 'brand' => 'Yellow',
                'price' => 2200, 'discount' => 1800, 'stock' => 30,
                'short' => 'Light everyday kurti in a soft cotton blend.',
                'description' => "Easy to wear, easy to wash.\n\n- Cotton blend fabric\n- Straight cut\n- Three-quarter sleeves",
            ],

            // ---------------------------------------------------- Footwear
            [
                'sku' => 'FTW-NIK-REV6', 'name' => 'Nike Revolution 6 Running Shoes', 'category' => 'Footwear', 'brand' => 'Nike',
                'featured' => true, 'option' => 'Size',
                'variants' => [['40', 6990, 6], ['41', 6990, 9], ['42', 6990, 12], ['43', 6990, 7]],
                'short' => 'Lightweight, cushioned and ready for the daily run.',
                'description' => "Everyday running.\n\n- Soft foam midsole\n- Breathable mesh upper\n- Durable rubber outsole",
            ],
            [
                'sku' => 'FTW-PUM-SMASH', 'name' => 'Puma Smash v2 Sneakers', 'category' => 'Footwear', 'brand' => 'Puma',
                'price' => 5490, 'discount' => 4690, 'stock' => 18,
                'short' => 'Classic court style in soft leather.',
                'description' => "Clean looks that go with anything.\n\n- Soft leather-look upper\n- Cushioned sockliner\n- Rubber cupsole",
            ],

            // ---------------------------------------------------- Kitchen & home
            [
                'sku' => 'KIT-PHI-AF92', 'name' => 'Philips Air Fryer HD9200', 'category' => 'Kitchen Appliances', 'brand' => 'Philips',
                'price' => 11900, 'discount' => 10490, 'stock' => 12, 'featured' => true,
                'short' => 'Fry, bake and roast with up to 90% less oil.',
                'description' => "Healthier cooking.\n\n- 4.1 litre capacity\n- Rapid Air technology\n- Adjustable temperature up to 200C\n- Dishwasher-safe basket",
            ],
            [
                'sku' => 'KIT-WAL-RC18', 'name' => 'Walton Electric Rice Cooker 1.8L', 'category' => 'Kitchen Appliances', 'brand' => 'Walton',
                'price' => 3650, 'stock' => 20,
                'short' => 'Cooks fluffy rice for the whole family and keeps it warm.',
                'description' => "Simple and dependable.\n\n- 1.8 litre capacity\n- Automatic keep-warm\n- Non-stick inner pot\n- Steamer tray included",
            ],
            [
                'sku' => 'HOM-CLK-001', 'name' => 'Decorative Wall Clock 30cm', 'category' => 'Home Decor',
                'price' => 1250, 'stock' => 40,
                'short' => 'A quiet, modern wall clock for any room.',
                'description' => "A small detail that finishes a room.\n\n- 30cm round face\n- Silent sweep movement\n- Runs on one AA battery",
            ],

            // ---------------------------------------------------- Beauty
            [
                'sku' => 'BEA-NIV-CRM', 'name' => 'Nivea Soft Moisturising Cream 200ml', 'category' => 'Skin Care', 'brand' => 'Nivea',
                'price' => 450, 'discount' => 390, 'stock' => 100,
                'short' => 'Light cream for face, hands and body.',
                'description' => "Everyday moisture.\n\n- Enriched with Vitamin E and jojoba oil\n- Absorbs quickly\n- Suitable for the whole family",
            ],
            [
                'sku' => 'BEA-YAR-PRF', 'name' => 'Yardley London Perfume 50ml', 'category' => 'Fragrances', 'brand' => 'Yardley',
                'price' => 1650, 'stock' => 28,
                'short' => 'A fresh floral fragrance that lasts through the day.',
                'description' => "A classic English scent.\n\n- 50ml eau de toilette\n- Fresh floral notes\n- Long lasting",
            ],

            // ---------------------------------------------------- Sports & stationery
            [
                'sku' => 'SPT-PUM-YOGA', 'name' => 'Puma Yoga Mat 6mm', 'category' => 'Fitness Equipment', 'brand' => 'Puma',
                'price' => 1850, 'discount' => 1590, 'stock' => 22,
                'short' => 'Non-slip 6mm mat with a carry strap.',
                'description' => "For yoga, stretching and floor workouts.\n\n- 6mm thick for joint comfort\n- Non-slip surface\n- Easy to roll and carry",
            ],
            [
                'sku' => 'STA-NTB-A5', 'name' => 'A5 Hardcover Notebook (Pack of 3)', 'category' => 'Books & Stationery',
                'price' => 480, 'stock' => 120,
                'short' => 'Three ruled hardcover notebooks, 160 pages each.',
                'description' => "Good for class, work and ideas.\n\n- A5 size, hardcover\n- 160 ruled pages each\n- Smooth, bleed-resistant paper",
            ],
        ];
    }

    /* ---------------------------------------------------------------
     |  HELPERS
     * --------------------------------------------------------------*/

    /** Keeps only the keys that are real columns of the table. */
    private function only(string $table, array $attributes): array
    {
        $this->columns[$table] ??= Schema::getColumnListing($table);

        return array_intersect_key($attributes, array_flip($this->columns[$table]));
    }

    private function emptyImageValue(): ?string
    {
        $column = collect(Schema::getColumns('products'))->firstWhere('name', 'featured_image');

        return ($column['nullable'] ?? true) ? null : '';
    }
}

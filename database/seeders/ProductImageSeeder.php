<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Gives every product without a photo a random picture from public/images (p1.jpg, p2.jfif ... p8).
 *
 *   php artisan db:seed --class=ProductImageSeeder
 *
 * Why the pictures are copied: Product::featured_image_url reads files from the storage disk
 * (config filesystems.default), not from public/images. So each picture is copied to
 * products/seed-pN.* on that disk once, and the products point to the copies.
 *
 * Safe to run again: products that already have a main picture or gallery are left alone.
 * Set $replace to true if you want to give every product a new random picture.
 */
class ProductImageSeeder extends Seeder
{
    /** true = also overwrite products that already have a main picture */
    private bool $replace = false;

    /** extra pictures added to each product's gallery */
    private int $galleryCount = 2;

    public function run(): void
    {
        $pool = $this->copyPictures();

        if (! $pool) {
            $this->command?->warn('No pictures found. Put files named p1.jpg, p2.jfif ... in public/images and run this again.');
            return;
        }

        $main = 0;
        $gallery = 0;

        Product::query()->with('images')->chunkById(100, function ($products) use ($pool, &$main, &$gallery) {
            foreach ($products as $product) {
                $picks = $pool;
                shuffle($picks);

                if ($this->replace || ! $product->featured_image) {
                    $product->update(['featured_image' => $picks[0]]);
                    $main++;
                }

                if ($product->images->isEmpty()) {
                    // different pictures from the main one when there are enough
                    $others = array_values(array_diff($picks, [$product->featured_image])) ?: $picks;

                    foreach (array_slice($others, 0, $this->galleryCount) as $path) {
                        $product->images()->create(['image_path' => $path]);
                        $gallery++;
                    }
                }
            }
        });

        $this->command?->info(count($pool) . " pictures copied. Main picture set on {$main} products, {$gallery} gallery pictures added.");
    }

    /** Copies public/images/pN.* to the storage disk and returns the storage paths. */
    private function copyPictures(): array
    {
        $dir = public_path('images');
        if (! is_dir($dir)) {
            return [];
        }

        $paths = [];

        try {
            foreach (scandir($dir) as $file) {
                if (! preg_match('/^p\d+\.(jpe?g|jfif|png|webp|avif)$/i', $file)) {
                    continue;
                }

                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                $ext = $ext === 'jfif' ? 'jpg' : $ext;               // a .jfif is a normal JPEG; .jpg is safer for browsers and servers
                $path = 'products/seed-' . strtolower(pathinfo($file, PATHINFO_FILENAME)) . '.' . $ext;

                if (! Storage::exists($path)) {
                    Storage::put($path, file_get_contents($dir . DIRECTORY_SEPARATOR . $file));
                }

                $paths[] = $path;
            }
        } catch (\Throwable $e) {
            $this->command?->warn('Could not copy the pictures to the storage disk: ' . $e->getMessage());
            return [];
        }

        return $paths;
    }
}

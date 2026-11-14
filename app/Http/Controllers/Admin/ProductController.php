<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /* ---------------------------------------------------------------
     |  INDEX  (search + filters + data for the details modal)
     * --------------------------------------------------------------*/
    public function index(Request $request)
    {
        $disk = config('filesystems.default');

        $q          = trim((string) $request->query('q', ''));
        $status     = $request->query('status');      // active | inactive | low_stock
        $categoryId = $request->query('category');

        $products = Product::with(['category', 'brand', 'variants', 'images'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                        ->orWhere('sku', 'like', "%{$q}%")
                        ->orWhereHas('variants', fn ($v) => $v->where('sku', 'like', "%{$q}%")
                            ->orWhere('attribute_value', 'like', "%{$q}%"))
                        ->orWhereHas('category', fn ($c) => $c->where('name', 'like', "%{$q}%"))
                        ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', "%{$q}%"));
                });
            })
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($status === 'low_stock', fn ($query) => $query->where('stock_quantity', '<=', 5))
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // Full details for the modal (built once, rendered as JSON in the page)
        $details = $products->getCollection()->map(function (Product $p) use ($disk) {
            $seo = $p->seo_metadata;
            if (is_string($seo)) {
                $seo = json_decode($seo, true) ?: [];
            }
            $seo = is_array($seo) ? $seo : [];

            return [
                'id'                => $p->id,
                'name'              => $p->name,
                'slug'              => $p->slug,
                'sku'               => $p->sku,
                'category'          => $p->category?->name,
                'brand'             => $p->brand?->name,
                'price'             => (float) $p->price,
                'discount_price'    => $p->discount_price ? (float) $p->discount_price : null,
                'stock'             => (int) $p->stock_quantity,
                'is_active'         => (bool) $p->is_active,
                'is_featured'       => (bool) $p->is_featured,
                'has_variants'      => (bool) $p->has_variants,
                'featured_image'    => $p->featured_image_url,
                'gallery'           => $p->images->map(fn ($i) => $i->image_url)->values(),
                'variants'          => $p->variants->map(fn ($v) => [
                    'attribute_name'  => $v->attribute_name,
                    'attribute_value' => $v->attribute_value,
                    'sku'             => $v->sku,
                    'price'           => (float) $v->price,
                    'stock'           => (int) $v->stock_quantity,
                    'image'           => $v->image_url,
                ])->values(),
                'short_description' => $p->short_description,
                'description'       => $p->description,
                'seo'               => [
                    'meta_title'       => $seo['meta_title'] ?? null,
                    'meta_description' => $seo['meta_description'] ?? null,
                    'meta_keywords'    => $seo['meta_keywords'] ?? null,
                ],
                'created_at'        => $p->created_at?->format('d M Y, h:i A'),
                'updated_at'        => $p->updated_at?->format('d M Y, h:i A'),
                'edit_url'          => route('admin.products.edit', $p),
            ];
        })->values();

        $categories = Category::orderBy('name')->get(['id', 'name']);

        return view('admin.products.index', compact('products', 'details', 'categories', 'q', 'status', 'categoryId'));
    }

    /* ---------------------------------------------------------------
     |  CREATE / STORE
     * --------------------------------------------------------------*/
    public function create()
    {
        $categories = Category::where('is_active', true)->get();
        $brands     = Brand::where('is_active', true)->get();

        return view('admin.products.create', compact('categories', 'brands'));
    }

    public function store(Request $request)
    {
        $disk = config('filesystems.default');

        $this->normalizeSlug($request);
        $request->validate($this->rules($request));

        $hasVariants = $request->boolean('has_variants');

        DB::transaction(function () use ($request, $disk, $hasVariants) {
            $featuredPath = $request->file('featured_image')->store('products/featured', $disk);

            $variants = $hasVariants ? collect($request->input('variants', [])) : collect();

            $product = Product::create([
                'name'              => $request->name,
                'slug'              => $request->filled('slug')
                    ? $request->slug
                    : Str::slug($request->name) . '-' . Str::random(5),
                'category_id'       => $request->category_id,
                'brand_id'          => $request->brand_id,
                'sku'               => strtoupper($request->sku),
                'price'             => $request->price,
                'discount_price'    => $request->discount_price,
                'stock_quantity'    => $hasVariants ? $variants->sum(fn ($v) => (int) $v['stock']) : $request->stock_quantity,
                'featured_image'    => $featuredPath,
                'short_description' => $request->short_description,
                'description'       => $request->description,
                'has_variants'      => $hasVariants,
                'is_featured'       => $request->boolean('is_featured'),
                'is_active'         => $request->boolean('is_active'),
                'seo_metadata'      => $this->seoPayload($request),
            ]);

            $this->storeGallery($product, $request, $disk);
            $this->syncVariants($product, $request, $disk, $hasVariants);
        });

        return redirect()->route('admin.products.index')->with('success', 'Product created successfully.');
    }

    /* ---------------------------------------------------------------
     |  EDIT / UPDATE
     * --------------------------------------------------------------*/
    public function edit(Product $product)
    {
        $categories = Category::where('is_active', true)->get();
        $brands     = Brand::where('is_active', true)->get();
        $product->load(['images', 'variants']);

        return view('admin.products.edit', compact('product', 'categories', 'brands'));
    }

    public function update(Request $request, Product $product)
    {
        $disk = config('filesystems.default');

        $this->normalizeSlug($request);
        $request->validate($this->rules($request, $product));

        $hasVariants = $request->boolean('has_variants');

        DB::transaction(function () use ($request, $product, $disk, $hasVariants) {
            $variants = $hasVariants ? collect($request->input('variants', [])) : collect();

            $data = [
                'name'              => $request->name,
                'slug'              => $request->filled('slug') ? $request->slug : $product->slug,
                'category_id'       => $request->category_id,
                'brand_id'          => $request->brand_id,
                'sku'               => strtoupper($request->sku),
                'price'             => $request->price,
                'discount_price'    => $request->discount_price,
                'stock_quantity'    => $hasVariants ? $variants->sum(fn ($v) => (int) $v['stock']) : $request->stock_quantity,
                'short_description' => $request->short_description,
                'description'       => $request->description,
                'has_variants'      => $hasVariants,
                'is_featured'       => $request->boolean('is_featured'),
                'is_active'         => $request->boolean('is_active'),
                'seo_metadata'      => $this->seoPayload($request),
            ];

            if ($request->hasFile('featured_image')) {
                if ($product->featured_image) {
                    Storage::disk($disk)->delete($product->featured_image);
                }
                $data['featured_image'] = $request->file('featured_image')->store('products/featured', $disk);
            }

            $product->update($data);

            $this->storeGallery($product, $request, $disk);
            $this->syncVariants($product, $request, $disk, $hasVariants);
        });

        return redirect()->route('admin.products.index')->with('success', 'Product updated successfully.');
    }

    /* ---------------------------------------------------------------
     |  DELETE
     * --------------------------------------------------------------*/
    public function destroy(Product $product)
    {
        $disk = config('filesystems.default');

        if ($product->featured_image) {
            Storage::disk($disk)->delete($product->featured_image);
        }

        foreach ($product->images as $img) {
            Storage::disk($disk)->delete($img->image_path);
            $img->delete();
        }

        foreach ($product->variants as $variant) {
            if ($variant->image) {
                Storage::disk($disk)->delete($variant->image);
            }
            $variant->delete();
        }

        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product deleted successfully.');
    }

    /** DELETE /admin/product-images/{image}  (used by the edit page gallery) */
    public function destroyImage(ProductImage $image): JsonResponse
    {
        $fileError = false;

        try {
            Storage::disk(config('filesystems.default'))->delete($image->image_path);
        } catch (\Throwable $e) {
            // e.g. missing s3:DeleteObject permission. Log it, but still remove the broken record.
            report($e);
            $fileError = true;
        }

        $image->delete();

        return response()->json([
            'success' => true,
            'message' => $fileError
                ? 'Removed from the product, but the file could not be deleted from storage (check the log).'
                : 'Gallery image removed.',
        ]);
    }

    /* ---------------------------------------------------------------
     |  HELPERS
     * --------------------------------------------------------------*/
    private function rules(Request $request, ?Product $product = null): array
    {
        $hasVariants = $request->boolean('has_variants');

        $rules = [
            'name'              => 'required|string|max:255',
            'slug'              => ['nullable', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($product?->id)],
            'category_id'       => 'required|exists:categories,id',
            'brand_id'          => 'nullable|exists:brands,id',
            'sku'               => ['required', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($product?->id)],
            'price'             => 'required|numeric|min:0',
            'discount_price'    => 'nullable|numeric|min:0|lt:price',
            'stock_quantity'    => $hasVariants ? 'nullable|integer|min:0' : 'required|integer|min:0',
            'featured_image'    => [$product ? 'nullable' : 'required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:3072'],
            'gallery_images'    => 'nullable|array',
            'gallery_images.*'  => 'image|mimes:jpeg,png,jpg,webp|max:3072',
            'short_description' => 'nullable|string|max:500',
            'description'       => 'nullable|string',
            'meta_title'        => 'nullable|string|max:70',
            'meta_description'  => 'nullable|string|max:160',
            'meta_keywords'     => 'nullable|string|max:255',
        ];

        if ($hasVariants) {
            $rules['variants'] = 'required|array|min:1';
            $rules['variants.*.attribute_name']  = 'required|string|max:100';
            $rules['variants.*.attribute_value'] = 'required|string|max:100';
            $rules['variants.*.price']           = 'required|numeric|min:0';
            $rules['variants.*.stock']           = 'required|integer|min:0';
            $rules['variants.*.image']           = 'nullable|image|mimes:jpeg,png,jpg,webp|max:3072';

            foreach ($request->input('variants', []) as $i => $variant) {
                $rules["variants.$i.sku"] = [
                    'required', 'string', 'max:100', 'distinct',
                    Rule::unique('product_variants', 'sku')->ignore($variant['id'] ?? null),
                ];
            }
        }

        return $rules;
    }

    private function normalizeSlug(Request $request): void
    {
        if ($request->filled('slug')) {
            $request->merge(['slug' => Str::slug($request->slug)]);
        }
    }

    private function seoPayload(Request $request): array
    {
        return [
            'meta_title'       => $request->meta_title,
            'meta_description' => $request->meta_description,
            'meta_keywords'    => $request->meta_keywords,
        ];
    }

    private function storeGallery(Product $product, Request $request, string $disk): void
    {
        if (! $request->hasFile('gallery_images')) {
            return;
        }

        foreach ($request->file('gallery_images') as $file) {
            $product->images()->create([
                'image_path' => $file->store('products/gallery', $disk),
            ]);
        }
    }

    /**
     * Create / update / delete variants so the DB matches the submitted form.
     */
    private function syncVariants(Product $product, Request $request, string $disk, bool $hasVariants): void
    {
        $submitted = $hasVariants ? collect($request->input('variants', [])) : collect();

        // 1) Remove variants that were deleted in the form (or all, if variants were turned off)
        $keepIds = $submitted->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();

        $product->variants()->whereNotIn('id', $keepIds)->get()->each(function ($variant) use ($disk) {
            if ($variant->image) {
                Storage::disk($disk)->delete($variant->image);
            }
            $variant->delete();
        });

        // 2) Update existing / create new
        foreach ($submitted as $i => $data) {
            $variant = ! empty($data['id']) ? $product->variants()->find($data['id']) : null;

            $payload = [
                'attribute_name'  => $data['attribute_name'],
                'attribute_value' => $data['attribute_value'],
                'sku'             => strtoupper($data['sku']),
                'price'           => $data['price'],
                'stock_quantity'  => $data['stock'],
            ];

            if ($request->hasFile("variants.{$i}.image")) {
                if ($variant?->image) {
                    Storage::disk($disk)->delete($variant->image);
                }
                $payload['image'] = $request->file("variants.{$i}.image")->store('products/variants', $disk);
            }

            $variant ? $variant->update($payload) : $product->variants()->create($payload);
        }
    }
}

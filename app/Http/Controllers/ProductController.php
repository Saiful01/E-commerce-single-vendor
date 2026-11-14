<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductReview;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function show(string $slug)
    {
        $product = Product::active()->where('slug', $slug)
            ->with(['category.parent', 'brand', 'images', 'variants'])
            ->firstOrFail();

        $reviews = $product->reviews()->approved()->with('user:id,name')->latest()->get();

        $stats = [
            'avg'   => round($reviews->avg('rating') ?: 0, 1),
            'count' => $reviews->count(),
            'dist'  => collect([5, 4, 3, 2, 1])->mapWithKeys(fn ($s) => [$s => $reviews->where('rating', $s)->count()]),
        ];

        $related = Product::active()->with('category:id,name,slug')
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->inRandomOrder()->take(8)->get();

        return view('products.show', compact('product', 'reviews', 'stats', 'related'));
    }

    /** JSON for the quick-view modal. */
    public function quick(string $slug)
    {
        $s = SiteSetting::current();
        $p = Product::active()->where('slug', $slug)->with(['brand:id,name', 'variants'])->firstOrFail();

        return response()->json([
            'id'           => $p->id,
            'name'         => $p->name,
            'url'          => $p->url,
            'image'        => $p->featured_image_url,
            'price'        => $s->formatMoney($p->final_price),
            'old_price'    => $p->has_discount ? $s->formatMoney($p->price) : null,
            'discount'     => $p->discount_percent,
            'brand'        => $p->brand?->name,
            'has_variants' => (bool) $p->has_variants,
            'in_stock'     => $p->in_stock,
            'excerpt'      => $p->short_description,
        ]);
    }

    /** JSON for the header live search. */
    public function suggest(Request $request)
    {
        $q = trim((string) $request->query('q'));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $s = SiteSetting::current();

        return Product::active()
            ->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('sku', 'like', "%{$q}%"))
            ->take(6)->get()
            ->map(fn ($p) => [
                'name'  => $p->name,
                'url'   => $p->url,
                'image' => $p->featured_image_url,
                'price' => $s->formatMoney($p->final_price),
            ]);
    }

    public function review(Request $request, Product $product)
    {
        $data = $request->validate([
            'rating'  => 'required|integer|between:1,5',
            'title'   => 'nullable|string|max:120',
            'comment' => 'required|string|min:5|max:1000',
        ]);

        $already = ProductReview::where('product_id', $product->id)->where('user_id', $request->user()->id)->exists();
        if ($already) {
            return response()->json(['message' => 'You have already reviewed this product.'], 422);
        }

        ProductReview::create($data + [
            'product_id' => $product->id,
            'user_id'    => $request->user()->id,
            'status'     => 'pending',
        ]);

        return response()->json(['message' => 'Thanks! Your review will show up once it is approved.']);
    }
}

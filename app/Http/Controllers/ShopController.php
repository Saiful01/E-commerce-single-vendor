<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        return $this->render($request);
    }

    public function category(Request $request, string $slug)
    {
        return $this->render($request, Category::active()->where('slug', $slug)->firstOrFail());
    }

    public function brand(Request $request, string $slug)
    {
        return $this->render($request, null, Brand::active()->where('slug', $slug)->firstOrFail());
    }

    private function render(Request $r, ?Category $category = null, ?Brand $brand = null)
    {
        $q    = trim((string) $r->query('q'));
        $sort = in_array($r->query('sort'), ['latest', 'price_asc', 'price_desc', 'name', 'discount'], true) ? $r->query('sort') : 'latest';
        $min  = is_numeric($r->query('min')) ? (float) $r->query('min') : null;
        $max  = is_numeric($r->query('max')) ? (float) $r->query('max') : null;

        // Price a customer really pays
        $eff = 'CASE WHEN discount_price IS NOT NULL AND discount_price > 0 AND discount_price < price THEN discount_price ELSE price END';

        $query = Product::active()->with('category:id,name,slug')
            ->when($category, fn ($x) => $x->whereIn('category_id', array_merge([$category->id], $category->descendantIds())))
            ->when($brand, fn ($x) => $x->where('brand_id', $brand->id))
            ->when($q !== '', fn ($x) => $x->where(fn ($w) => $w
                ->where('name', 'like', "%{$q}%")->orWhere('sku', 'like', "%{$q}%")->orWhere('short_description', 'like', "%{$q}%")))
            ->when($min !== null, fn ($x) => $x->whereRaw("({$eff}) >= ?", [$min]))
            ->when($max !== null, fn ($x) => $x->whereRaw("({$eff}) <= ?", [$max]))
            ->when($r->boolean('featured'), fn ($x) => $x->featured())
            ->when($r->boolean('in_stock'), fn ($x) => $x->where(fn ($w) => $w->where('has_variants', true)->orWhere('stock_quantity', '>', 0)))
            ->when($sort === 'discount', fn ($x) => $x->whereNotNull('discount_price')->whereColumn('discount_price', '<', 'price'));

        match ($sort) {
            'price_asc'  => $query->orderByRaw("({$eff}) asc"),
            'price_desc' => $query->orderByRaw("({$eff}) desc"),
            'name'       => $query->orderBy('name'),
            'discount'   => $query->orderByRaw('(price - discount_price) / price desc'),
            default      => $query->latest(),
        };

        $title = $category?->name ?? $brand?->name ?? ($q !== '' ? "Results for \"{$q}\"" : ($r->boolean('featured') ? 'Our picks' : ($sort === 'discount' ? 'Deals' : 'All products')));

        return view('shop.index', [
            'products'   => $query->paginate(12)->withQueryString(),
            'title'      => $title,
            'category'   => $category,
            'brand'      => $brand,
            'q'          => $q,
            'sort'       => $sort,
            'min'        => $min,
            'max'        => $max,
            'categories' => Category::active()->whereNull('parent_id')
                ->with(['children' => fn ($c) => $c->active()->ordered()])->ordered()->get(),
            'brands'     => Brand::active()->ordered()->limit(40)->get(['id', 'name', 'slug']),
        ]);
    }
}

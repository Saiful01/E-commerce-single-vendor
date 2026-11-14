<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BrandController extends Controller
{
    /* ---------------------------------------------------------------
     |  INDEX
     * --------------------------------------------------------------*/
    public function index(Request $request): View
    {
        $q      = trim((string) $request->query('q', ''));
        $status = in_array($request->query('status'), ['active', 'inactive', 'featured'], true)
            ? $request->query('status') : null;

        $brands = Brand::withCount('products')
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('name', 'like', "%{$q}%")
                ->orWhere('slug', 'like', "%{$q}%")
                ->orWhere('description', 'like', "%{$q}%")))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($status === 'featured', fn ($query) => $query->where('is_featured', true))
            ->ordered()
            ->paginate(12)
            ->withQueryString();

        $details = $brands->getCollection()->map(fn (Brand $b) => [
            'id'             => $b->id,
            'name'           => $b->name,
            'slug'           => $b->slug,
            'logo'           => $b->logo_url,
            'website_url'    => $b->website_url,
            'description'    => $b->description,
            'is_active'      => $b->is_active,
            'is_featured'    => $b->is_featured,
            'sort_order'     => $b->sort_order,
            'products_count' => $b->products_count,
            'seo'            => [
                'meta_title'       => $b->meta_title,
                'meta_description' => $b->meta_description,
                'meta_keywords'    => $b->meta_keywords,
            ],
            'url'            => $b->url,
            'created_at'     => $b->created_at?->format('d M Y, h:i A'),
            'updated_at'     => $b->updated_at?->format('d M Y, h:i A'),
            'edit_url'       => route('admin.brands.edit', $b),
        ])->values();

        return view('admin.brands.index', [
            'brands'  => $brands,
            'details' => $details,
            'stats'   => [
                'total'    => Brand::count(),
                'active'   => Brand::where('is_active', true)->count(),
                'featured' => Brand::where('is_featured', true)->count(),
            ],
            'q'       => $q,
            'status'  => $status,
        ]);
    }

    /* ---------------------------------------------------------------
     |  CREATE / STORE
     * --------------------------------------------------------------*/
    public function create(): View
    {
        return view('admin.brands.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->normalizeSlug($request);
        $data = $request->validate($this->rules());
        $disk = config('filesystems.default');

        $brand = Brand::create([
            'name'             => trim($data['name']),
            'slug'             => $request->filled('slug') ? $data['slug'] : $this->uniqueSlug($data['name']),
            'description'      => $data['description'] ?? null,
            'logo'             => $request->hasFile('logo') ? $request->file('logo')->store('brands', $disk) : null,
            'website_url'      => $data['website_url'] ?? null,
            'is_active'        => $request->boolean('is_active'),
            'is_featured'      => $request->boolean('is_featured'),
            'sort_order'       => $data['sort_order'] ?? ((int) Brand::max('sort_order') + 1),
            'meta_title'       => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'meta_keywords'    => $data['meta_keywords'] ?? null,
        ]);

        return redirect()->route('admin.brands.index')->with('success', "Brand \"{$brand->name}\" created.");
    }

    /* ---------------------------------------------------------------
     |  EDIT / UPDATE
     * --------------------------------------------------------------*/
    public function edit(Brand $brand): View
    {
        $brand->loadCount('products');

        return view('admin.brands.edit', compact('brand'));
    }

    public function update(Request $request, Brand $brand): RedirectResponse
    {
        $this->normalizeSlug($request);
        $data = $request->validate($this->rules($brand));
        $disk = config('filesystems.default');

        $payload = [
            'name'             => trim($data['name']),
            'slug'             => $request->filled('slug') ? $data['slug'] : $brand->slug, // never change URLs by accident
            'description'      => $data['description'] ?? null,
            'website_url'      => $data['website_url'] ?? null,
            'is_active'        => $request->boolean('is_active'),
            'is_featured'      => $request->boolean('is_featured'),
            'meta_title'       => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'meta_keywords'    => $data['meta_keywords'] ?? null,
        ];

        if (isset($data['sort_order'])) {
            $payload['sort_order'] = $data['sort_order'];
        }

        if ($request->hasFile('logo')) {
            $this->deleteLogo($brand->logo);
            $payload['logo'] = $request->file('logo')->store('brands', $disk);
        } elseif ($request->boolean('remove_logo')) {
            $this->deleteLogo($brand->logo);
            $payload['logo'] = null;
        }

        $brand->update($payload);

        return redirect()->route('admin.brands.index')->with('success', 'Brand updated successfully.');
    }

    /* ---------------------------------------------------------------
     |  DELETE / TOGGLE
     * --------------------------------------------------------------*/
    public function destroy(Brand $brand): RedirectResponse
    {
        $products = $brand->products()->count();

        if ($products > 0) {
            return back()->with('error', "\"{$brand->name}\" is used by {$products} " . Str::plural('product', $products) . '. Remove the brand from those products first.');
        }

        $this->deleteLogo($brand->logo);
        $brand->delete();

        return back()->with('success', 'Brand deleted successfully.');
    }

    /** PATCH /admin/brands/{brand}/toggle   body: { field: is_active | is_featured } */
    public function toggle(Request $request, Brand $brand): JsonResponse
    {
        $field = $request->validate([
            'field' => ['required', Rule::in(['is_active', 'is_featured'])],
        ])['field'];

        $brand->update([$field => ! $brand->{$field}]);

        $labels = ['is_active' => 'Active', 'is_featured' => 'Featured'];

        return response()->json([
            'success' => true,
            'value'   => (bool) $brand->{$field},
            'message' => $labels[$field] . ($brand->{$field} ? ': on.' : ': off.'),
        ]);
    }

    /* ---------------------------------------------------------------
     |  HELPERS
     * --------------------------------------------------------------*/
    private function rules(?Brand $brand = null): array
    {
        return [
            'name'             => ['required', 'string', 'max:255'],
            'slug'             => ['nullable', 'string', 'max:255', Rule::unique('brands', 'slug')->ignore($brand?->id)],
            'description'      => ['nullable', 'string', 'max:2000'],
            'logo'             => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'remove_logo'      => ['nullable', 'boolean'],
            'website_url'      => ['nullable', 'url', 'max:255'],
            'sort_order'       => ['nullable', 'integer', 'min:0', 'max:9999'],
            'meta_title'       => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'meta_keywords'    => ['nullable', 'string', 'max:255'],
        ];
    }

    private function normalizeSlug(Request $request): void
    {
        if ($request->filled('slug')) {
            $request->merge(['slug' => Str::slug($request->slug)]);
        }
    }

    /** Slug from the name, with -2, -3 ... added if it is already taken. */
    private function uniqueSlug(string $name): string
    {
        $base      = Str::slug($name) ?: 'brand'; // names without latin letters slugify to an empty string
        $candidate = $base;
        $i         = 2;

        while (Brand::where('slug', $candidate)->exists()) {
            $candidate = $base . '-' . $i++;
        }

        return $candidate;
    }

    private function deleteLogo(?string $path): void
    {
        if (! $path) {
            return;
        }

        // The file is on the default disk, or on the local public disk if it is an older upload
        foreach (array_unique([config('filesystems.default'), 'public']) as $disk) {
            try {
                Storage::disk($disk)->delete($path);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}

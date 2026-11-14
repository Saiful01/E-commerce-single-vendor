<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    /* ---------------------------------------------------------------
     |  INDEX  (tree view, search, filters, data for the details modal)
     * --------------------------------------------------------------*/
    public function index(Request $request): View
    {
        $q      = trim((string) $request->query('q', ''));
        $status = in_array($request->query('status'), ['active', 'inactive', 'featured', 'hidden'], true)
            ? $request->query('status') : null;

        $all  = Category::withCount(['products', 'children'])->get();
        $tree = $this->buildTree($all);

        $filtering = $q !== '' || $status !== null;

        $rows = $tree->filter(function (Category $c) use ($q, $status) {
            if ($q !== '' && mb_stripos($c->name . ' ' . $c->slug . ' ' . $c->description, $q) === false) {
                return false;
            }

            return match ($status) {
                'active'   => $c->is_active,
                'inactive' => ! $c->is_active,
                'featured' => $c->is_featured,
                'hidden'   => ! $c->show_in_menu,
                default    => true,
            };
        })->values();

        $details = $rows->map(fn (Category $c) => [
            'id'             => $c->id,
            'name'           => $c->name,
            'slug'           => $c->slug,
            'path'           => $c->path,
            'parent'         => $all->firstWhere('id', $c->parent_id)?->name,
            'description'    => $c->description,
            'image'          => $c->image_url,
            'is_active'      => $c->is_active,
            'is_featured'    => $c->is_featured,
            'show_in_menu'   => $c->show_in_menu,
            'sort_order'     => $c->sort_order,
            'products_count' => $c->products_count,
            'children'       => $all->where('parent_id', $c->id)->sortBy('sort_order')->pluck('name')->values(),
            'seo'            => [
                'meta_title'       => $c->meta_title,
                'meta_description' => $c->meta_description,
                'meta_keywords'    => $c->meta_keywords,
            ],
            'url'            => $c->url,
            'created_at'     => $c->created_at?->format('d M Y, h:i A'),
            'updated_at'     => $c->updated_at?->format('d M Y, h:i A'),
            'edit_url'       => route('admin.categories.edit', $c),
        ])->values();

        return view('admin.categories.index', [
            'rows'      => $rows,
            'details'   => $details,
            'filtering' => $filtering,
            'stats'     => [
                'total'    => $all->count(),
                'active'   => $all->where('is_active', true)->count(),
                'featured' => $all->where('is_featured', true)->count(),
            ],
            'q'         => $q,
            'status'    => $status,
        ]);
    }

    /* ---------------------------------------------------------------
     |  CREATE / STORE
     * --------------------------------------------------------------*/
    public function create(): View
    {
        return view('admin.categories.create', [
            'parentOptions' => $this->parentOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->normalizeSlug($request);
        $data = $request->validate($this->rules($request));
        $disk = config('filesystems.default');

        $parentId = $data['parent_id'] ?? null;

        $category = Category::create([
            'parent_id'        => $parentId,
            'name'             => trim($data['name']),
            'slug'             => $request->filled('slug') ? $data['slug'] : $this->uniqueSlug($data['name']),
            'description'      => $data['description'] ?? null,
            'image'            => $request->hasFile('image') ? $request->file('image')->store('categories', $disk) : null,
            'is_active'        => $request->boolean('is_active'),
            'is_featured'      => $request->boolean('is_featured'),
            'show_in_menu'     => $request->boolean('show_in_menu'),
            'sort_order'       => $data['sort_order'] ?? ((int) Category::where('parent_id', $parentId)->max('sort_order') + 1),
            'meta_title'       => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'meta_keywords'    => $data['meta_keywords'] ?? null,
        ]);

        return redirect()->route('admin.categories.index')->with('success', "Category \"{$category->name}\" created.");
    }

    /* ---------------------------------------------------------------
     |  EDIT / UPDATE
     * --------------------------------------------------------------*/
    public function edit(Category $category): View
    {
        $category->loadCount(['products', 'children']);

        return view('admin.categories.edit', [
            'category'      => $category,
            'parentOptions' => $this->parentOptions($category),
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $this->normalizeSlug($request);
        $data = $request->validate($this->rules($request, $category));
        $disk = config('filesystems.default');

        // A category cannot sit under itself or under one of its own sub-categories
        if (! empty($data['parent_id'])) {
            $parentId = (int) $data['parent_id'];

            if ($parentId === $category->id || in_array($parentId, $category->descendantIds(), true)) {
                return back()->withInput()->withErrors([
                    'parent_id' => 'A category cannot be placed under itself or one of its sub-categories.',
                ]);
            }
        }

        $payload = [
            'parent_id'        => $data['parent_id'] ?? null,
            'name'             => trim($data['name']),
            'slug'             => $request->filled('slug') ? $data['slug'] : $category->slug, // never change URLs by accident
            'description'      => $data['description'] ?? null,
            'is_active'        => $request->boolean('is_active'),
            'is_featured'      => $request->boolean('is_featured'),
            'show_in_menu'     => $request->boolean('show_in_menu'),
            'meta_title'       => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'meta_keywords'    => $data['meta_keywords'] ?? null,
        ];

        if (isset($data['sort_order'])) {
            $payload['sort_order'] = $data['sort_order'];
        }

        if ($request->hasFile('image')) {
            $this->deleteImage($category->image);
            $payload['image'] = $request->file('image')->store('categories', $disk);
        } elseif ($request->boolean('remove_image')) {
            $this->deleteImage($category->image);
            $payload['image'] = null;
        }

        $category->update($payload);

        return redirect()->route('admin.categories.index')->with('success', 'Category updated successfully.');
    }

    /* ---------------------------------------------------------------
     |  DELETE / TOGGLE
     * --------------------------------------------------------------*/
    public function destroy(Category $category): RedirectResponse
    {
        $subs = $category->children()->count();
        if ($subs > 0) {
            return back()->with('error', "\"{$category->name}\" has {$subs} sub-" . Str::plural('category', $subs) . '. Move or delete them first.');
        }

        $products = $category->products()->count();
        if ($products > 0) {
            return back()->with('error', "\"{$category->name}\" still has {$products} " . Str::plural('product', $products) . '. Move them to another category first.');
        }

        $this->deleteImage($category->image);
        $category->delete();

        return back()->with('success', 'Category deleted successfully.');
    }

    /** PATCH /admin/categories/{category}/toggle   body: { field: is_active | is_featured | show_in_menu } */
    public function toggle(Request $request, Category $category): JsonResponse
    {
        $field = $request->validate([
            'field' => ['required', Rule::in(['is_active', 'is_featured', 'show_in_menu'])],
        ])['field'];

        $category->update([$field => ! $category->{$field}]);

        $labels = ['is_active' => 'Active', 'is_featured' => 'Featured', 'show_in_menu' => 'Shown in menu'];

        return response()->json([
            'success' => true,
            'value'   => (bool) $category->{$field},
            'message' => $labels[$field] . ($category->{$field} ? ': on.' : ': off.'),
        ]);
    }

    /* ---------------------------------------------------------------
     |  HELPERS
     * --------------------------------------------------------------*/
    private function rules(Request $request, ?Category $category = null): array
    {
        return [
            'name'             => ['required', 'string', 'max:255'],
            'slug'             => ['nullable', 'string', 'max:255', Rule::unique('categories', 'slug')->ignore($category?->id)],
            'parent_id'        => ['nullable', 'integer', 'exists:categories,id'],
            'description'      => ['nullable', 'string', 'max:2000'],
            'image'            => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'remove_image'     => ['nullable', 'boolean'],
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
        $base      = Str::slug($name) ?: 'category'; // names without latin letters (for example Bangla) slugify to an empty string
        $candidate = $base;
        $i         = 2;

        while (Category::where('slug', $candidate)->exists()) {
            $candidate = $base . '-' . $i++;
        }

        return $candidate;
    }

    /**
     * Every category in tree order (parents first, children right below them),
     * each with ->depth and ->path ("Men / Shoes / Sneakers") attached.
     */
    private function buildTree(Collection $all): Collection
    {
        $byParent = $all->sortBy([['sort_order', 'asc'], ['name', 'asc']])->groupBy(fn ($c) => $c->parent_id ?? 'root');
        $out      = [];

        $walk = function ($parentKey, int $depth, array $path) use (&$walk, &$out, $byParent) {
            foreach ($byParent->get($parentKey, collect()) as $category) {
                $trail = [...$path, $category->name];

                $category->setAttribute('depth', $depth);
                $category->setAttribute('path', implode(' / ', $trail));
                $out[] = $category;

                $walk($category->id, $depth + 1, $trail);
            }
        };

        $walk('root', 0, []);

        return collect($out);
    }

    /** Options for the "parent category" select. Skips the category itself and everything below it. */
    private function parentOptions(?Category $exclude = null): array
    {
        $skip = $exclude ? [$exclude->id, ...$exclude->descendantIds()] : [];

        return $this->buildTree(Category::all())
            ->reject(fn (Category $c) => in_array($c->id, $skip, true))
            ->map(fn (Category $c) => ['id' => $c->id, 'label' => str_repeat('- ', $c->depth) . $c->name])
            ->values()
            ->all();
    }

    private function deleteImage(?string $path): void
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

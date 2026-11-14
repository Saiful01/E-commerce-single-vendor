@extends('layouts.admin')

@section('title', 'Product Management')

@section('content')
    <div class="space-y-5" x-data="productIndex()" @keydown.escape.window="close()">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Products</h2>
                <p class="text-xs text-slate-500 mt-1">{{ $products->total() }} {{ Str::plural('product', $products->total()) }} in your catalog</p>
            </div>
            <a href="{{ route('admin.products.create') }}" class="px-4 py-2.5 bg-indigo-600 text-white font-semibold text-xs rounded-xl shadow-lg shadow-indigo-600/30 hover:bg-indigo-700 transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add product
            </a>
        </div>

        {{-- Search & filters --}}
        <form method="GET" action="{{ route('admin.products.index') }}" x-ref="filter"
              class="bg-white p-3 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col md:flex-row gap-2">
            <div class="relative flex-1">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="search" name="q" value="{{ $q }}" placeholder="Search by name, SKU, variant, category or brand"
                       @input.debounce.500ms="$refs.filter.submit()"
                       @if ($q !== '') x-init="$el.focus(); $el.setSelectionRange($el.value.length, $el.value.length)" @endif
                       class="w-full pl-9 text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <select name="category" @change="$refs.filter.submit()" class="text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All categories</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}" @selected((string) $categoryId === (string) $cat->id)>{{ $cat->name }}</option>
                @endforeach
            </select>

            <select name="status" @change="$refs.filter.submit()" class="text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Any status</option>
                <option value="active" @selected($status === 'active')>Active</option>
                <option value="inactive" @selected($status === 'inactive')>Inactive</option>
                <option value="low_stock" @selected($status === 'low_stock')>Low stock (5 or fewer)</option>
            </select>

            @if ($q !== '' || $status || $categoryId)
                <a href="{{ route('admin.products.index') }}" class="px-3 py-2.5 text-xs font-semibold text-slate-500 hover:text-slate-800 rounded-xl border border-slate-200 text-center">Clear</a>
            @endif
        </form>

        {{-- Table --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                        <th class="py-3.5 px-6">Product</th>
                        <th class="py-3.5 px-6">Category & Brand</th>
                        <th class="py-3.5 px-6">Price</th>
                        <th class="py-3.5 px-6">Variants / Stock</th>
                        <th class="py-3.5 px-6">Status</th>
                        <th class="py-3.5 px-6 text-right">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs font-medium">
                    @forelse ($products as $product)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-3 px-6">
                                <button type="button" @click="open({{ $product->id }})" class="flex items-center gap-3 text-left group">
                                    <img src="{{ $product->featured_image_url }}" class="w-12 h-12 rounded-xl object-cover border border-slate-200" alt="{{ $product->name }}">
                                    <div>
                                        <p class="font-bold text-slate-800 group-hover:text-indigo-600 transition-colors">{{ $product->name }}</p>
                                        <p class="text-[10px] text-slate-400 font-mono">SKU: {{ $product->sku }}</p>
                                    </div>
                                </button>
                            </td>
                            <td class="py-4 px-6">
                                <p class="text-slate-700 font-semibold">{{ $product->category->name ?? '-' }}</p>
                                <p class="text-slate-400 text-[11px]">{{ $product->brand->name ?? 'No brand' }}</p>
                            </td>
                            <td class="py-4 px-6">
                                @if ($product->discount_price)
                                    <span class="font-bold text-indigo-600">৳{{ number_format($product->discount_price, 2) }}</span>
                                    <span class="text-[10px] text-slate-400 line-through ml-1">৳{{ number_format($product->price, 2) }}</span>
                                @else
                                    <span class="font-bold text-slate-800">৳{{ number_format($product->price, 2) }}</span>
                                @endif
                            </td>
                            <td class="py-4 px-6">
                                @if ($product->has_variants)
                                    <span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                        {{ $product->variants->count() }} variants
                                    </span>
                                    <span class="text-[10px] text-slate-400 ml-1">{{ $product->stock_quantity }} pcs</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-md text-[10px] font-bold {{ $product->stock_quantity > 5 ? 'bg-slate-100 text-slate-700' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                        {{ $product->stock_quantity }} pcs
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-6">
                                <span class="px-2.5 py-1 rounded-md text-[10px] font-bold {{ $product->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                    {{ $product->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" @click="open({{ $product->id }})" class="p-1.5 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">View</button>
                                    <a href="{{ route('admin.products.edit', $product) }}" class="p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-slate-100 rounded-lg transition-colors">Edit</a>

                                    <form id="delete-product-{{ $product->id }}" action="{{ route('admin.products.destroy', $product) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" onclick="confirmDelete('delete-product-{{ $product->id }}', 'Deleting this product will permanently remove all gallery images and variants!')" class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition-colors">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-10 text-slate-400">
                                @if ($q !== '' || $status || $categoryId)
                                    No products match your filters. <a href="{{ route('admin.products.index') }}" class="text-indigo-600 font-semibold">Clear filters</a>
                                @else
                                    No products yet. <a href="{{ route('admin.products.create') }}" class="text-indigo-600 font-semibold">Add your first product</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-slate-100">
                {{ $products->links() }}
            </div>
        </div>

        {{-- ===================== DETAILS MODAL ===================== --}}
        <div x-show="p" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" :aria-label="p ? p.name : ''">
            <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"
                 x-show="p" x-transition.opacity @click="close()"></div>

            <div class="relative bg-white w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-2xl shadow-2xl"
                 x-show="p" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-3 scale-[0.98]" x-transition:enter-end="opacity-100 translate-y-0 scale-100">

                <template x-if="p">
                    <div>
                        {{-- modal header --}}
                        <div class="sticky top-0 z-10 bg-white/95 backdrop-blur border-b border-slate-100 px-6 py-4 flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <h3 class="text-base font-bold text-slate-800 truncate" x-text="p.name"></h3>
                                <p class="text-[11px] text-slate-400 font-mono mt-0.5">SKU: <span x-text="p.sku"></span></p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <a :href="p.edit_url" class="px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-lg hover:bg-indigo-700">Edit product</a>
                                <button type="button" @click="close()" class="p-1.5 text-slate-400 hover:text-slate-700 rounded-lg hover:bg-slate-100" aria-label="Close">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>

                        <div class="p-6 grid grid-cols-1 md:grid-cols-5 gap-6">

                            {{-- images --}}
                            <div class="md:col-span-2 space-y-3">
                                <img :src="activeImage" class="w-full aspect-square object-cover rounded-2xl border border-slate-200" :alt="p.name">
                                <div class="grid grid-cols-5 gap-2" x-show="allImages.length > 1">
                                    <template x-for="img in allImages" :key="img">
                                        <button type="button" @click="activeImage = img"
                                                class="rounded-lg overflow-hidden border-2 transition-colors"
                                                :class="activeImage === img ? 'border-indigo-500' : 'border-transparent hover:border-slate-300'">
                                            <img :src="img" class="w-full h-12 object-cover" alt="">
                                        </button>
                                    </template>
                                </div>
                            </div>

                            {{-- info --}}
                            <div class="md:col-span-3 space-y-5 text-xs">

                                <div class="flex flex-wrap gap-2">
                                    <span class="px-2.5 py-1 rounded-md text-[10px] font-bold border"
                                          :class="p.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                                          x-text="p.is_active ? 'Active' : 'Inactive'"></span>
                                    <span x-show="p.is_featured" class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Featured</span>
                                    <span x-show="p.has_variants" class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">Has variants</span>
                                </div>

                                <div class="flex items-end gap-3">
                                    <template x-if="p.discount_price">
                                        <div class="flex items-end gap-2">
                                            <span class="text-2xl font-bold text-indigo-600" x-text="money(p.discount_price)"></span>
                                            <span class="text-sm text-slate-400 line-through" x-text="money(p.price)"></span>
                                            <span class="text-[11px] font-bold text-emerald-600 pb-1" x-text="`${Math.round((1 - p.discount_price / p.price) * 100)}% off`"></span>
                                        </div>
                                    </template>
                                    <template x-if="!p.discount_price">
                                        <span class="text-2xl font-bold text-slate-800" x-text="money(p.price)"></span>
                                    </template>
                                </div>

                                <dl class="grid grid-cols-2 gap-x-4 gap-y-3 bg-slate-50 rounded-xl p-4">
                                    <div><dt class="text-slate-400">Category</dt><dd class="font-semibold text-slate-800" x-text="p.category || '-'"></dd></div>
                                    <div><dt class="text-slate-400">Brand</dt><dd class="font-semibold text-slate-800" x-text="p.brand || 'No brand'"></dd></div>
                                    <div>
                                        <dt class="text-slate-400">Total stock</dt>
                                        <dd class="font-semibold" :class="p.stock <= 5 ? 'text-amber-600' : 'text-slate-800'" x-text="`${p.stock} pcs`"></dd>
                                    </div>
                                    <div><dt class="text-slate-400">Gallery images</dt><dd class="font-semibold text-slate-800" x-text="p.gallery.length"></dd></div>
                                    <div><dt class="text-slate-400">Created</dt><dd class="font-semibold text-slate-800" x-text="p.created_at || '-'"></dd></div>
                                    <div><dt class="text-slate-400">Last updated</dt><dd class="font-semibold text-slate-800" x-text="p.updated_at || '-'"></dd></div>
                                </dl>

                                <div x-show="p.short_description">
                                    <h4 class="font-bold text-slate-800 mb-1">Short description</h4>
                                    <p class="text-slate-600 leading-relaxed" x-text="p.short_description"></p>
                                </div>

                                <div x-show="p.description">
                                    <h4 class="font-bold text-slate-800 mb-1">Full description</h4>
                                    <p class="text-slate-600 leading-relaxed whitespace-pre-line" x-text="p.description"></p>
                                </div>
                            </div>
                        </div>

                        {{-- variants --}}
                        <div class="px-6 pb-6" x-show="p.variants.length > 0">
                            <h4 class="text-xs font-bold text-slate-800 mb-2" x-text="`Variants (${p.variants.length})`"></h4>
                            <div class="overflow-x-auto border border-slate-200 rounded-xl">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-slate-50 text-[11px] text-slate-400">
                                    <tr>
                                        <th class="py-2 px-3 font-semibold">Variant</th>
                                        <th class="py-2 px-3 font-semibold">SKU</th>
                                        <th class="py-2 px-3 font-semibold">Price</th>
                                        <th class="py-2 px-3 font-semibold">Stock</th>
                                    </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                    <template x-for="v in p.variants" :key="v.sku">
                                        <tr>
                                            <td class="py-2 px-3">
                                                <div class="flex items-center gap-2">
                                                    <img x-show="v.image" :src="v.image" class="w-8 h-8 rounded-lg object-cover border border-slate-200" alt="">
                                                    <span><span class="text-slate-400" x-text="v.attribute_name + ':'"></span> <span class="font-semibold text-slate-800" x-text="v.attribute_value"></span></span>
                                                </div>
                                            </td>
                                            <td class="py-2 px-3 font-mono text-slate-600" x-text="v.sku"></td>
                                            <td class="py-2 px-3 font-semibold text-slate-800" x-text="money(v.price)"></td>
                                            <td class="py-2 px-3">
                                                <span class="font-semibold" :class="v.stock <= 5 ? 'text-amber-600' : 'text-slate-800'" x-text="v.stock"></span>
                                            </td>
                                        </tr>
                                    </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- SEO --}}
                        <div class="px-6 pb-6">
                            <h4 class="text-xs font-bold text-slate-800 mb-2">Search engine listing</h4>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3 text-xs">
                                <div>
                                    <p class="text-[11px] text-slate-500 truncate" x-text="`{{ rtrim(config('app.url'), '/') }}/products/${p.slug}`"></p>
                                    <p class="text-[15px] leading-snug text-indigo-700 font-medium" x-text="p.seo.meta_title || p.name"></p>
                                    <p class="text-slate-600" x-text="p.seo.meta_description || p.short_description || 'No meta description set.'"></p>
                                </div>
                                <dl class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-3 border-t border-slate-200">
                                    <div><dt class="text-slate-400">Meta title</dt><dd class="font-semibold text-slate-800" x-text="p.seo.meta_title || 'Not set'"></dd></div>
                                    <div><dt class="text-slate-400">Slug</dt><dd class="font-mono text-slate-800" x-text="p.slug"></dd></div>
                                    <div class="md:col-span-2"><dt class="text-slate-400">Meta keywords</dt><dd class="font-semibold text-slate-800" x-text="p.seo.meta_keywords || 'Not set'"></dd></div>
                                </dl>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <script>
        const PRODUCT_DETAILS = @json($details);

        function productIndex() {
            return {
                p: null,
                activeImage: null,

                get allImages() {
                    return this.p ? [this.p.featured_image, ...this.p.gallery] : [];
                },

                open(id) {
                    this.p = PRODUCT_DETAILS.find(x => x.id === id) || null;
                    if (this.p) {
                        this.activeImage = this.p.featured_image;
                        document.body.classList.add('overflow-hidden');
                    }
                },
                close() {
                    this.p = null;
                    document.body.classList.remove('overflow-hidden');
                },
                money(n) {
                    return '৳' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },
            };
        }
    </script>
@endsection

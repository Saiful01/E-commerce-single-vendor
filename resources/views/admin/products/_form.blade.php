{{--
    Shared form for create + edit.
    Expects: $action, $method ('POST' | 'PUT'), $product (null on create), $categories, $brands
--}}
@php
    $isEdit = ! is_null($product);

    $seo = [];
    if ($isEdit) {
        $seo = is_array($product->seo_metadata)
            ? $product->seo_metadata
            : (json_decode($product->seo_metadata ?? '[]', true) ?: []);
    }

    $hasOld = session()->hasOldInput();

    $initialVariants = old('variants');
    if (is_null($initialVariants)) {
        $initialVariants = $isEdit
            ? $product->variants->map(fn ($v) => [
                'id'              => $v->id,
                'attribute_name'  => $v->attribute_name,
                'attribute_value' => $v->attribute_value,
                'sku'             => $v->sku,
                'price'           => $v->price,
                'stock'           => $v->stock_quantity,
                'image_url'       => $v->image_url,
            ])->values()->all()
            : [];
    }

    $cfg = [
        'hasVariants'     => (bool) ($hasOld ? old('has_variants') : ($product->has_variants ?? false)),
        'variants'        => array_values($initialVariants),
        'featuredUrl'     => $isEdit ? $product->featured_image_url : null,
        'name'            => old('name', $product->name ?? ''),
        'slug'            => old('slug', $product->slug ?? ''),
        'shortDescription'=> old('short_description', $product->short_description ?? ''),
        'metaTitle'       => old('meta_title', $seo['meta_title'] ?? ''),
        'metaDescription' => old('meta_description', $seo['meta_description'] ?? ''),
        'siteUrl'         => rtrim(config('app.url'), '/'),
    ];

    $isActive   = $hasOld ? (bool) old('is_active')   : ($product->is_active ?? true);
    $isFeatured = $hasOld ? (bool) old('is_featured') : ($product->is_featured ?? false);

    $input = 'w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500';
    $label = 'block text-xs font-semibold text-slate-700 mb-1';
    $card  = 'bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-4';
    $err   = 'text-rose-500 text-[11px] mt-1 block';
@endphp

<form action="{{ $action }}" method="POST" enctype="multipart/form-data"
      x-data="productForm({{ \Illuminate\Support\Js::from($cfg) }})"
      class="space-y-4">
    @csrf
    @if ($method !== 'POST') @method($method) @endif

    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4">
            <p class="text-xs font-bold text-rose-700">Please fix the following before saving:</p>
            <ul class="mt-2 list-disc pl-5 text-[11px] text-rose-600 space-y-0.5">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-start">

        {{-- ======================= MAIN COLUMN ======================= --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Basic information --}}
            <section class="{{ $card }}">
                <h3 class="text-sm font-bold text-slate-800">Basic information</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="{{ $label }}">Product name *</label>
                        <input type="text" name="name" x-model="name" class="{{ $input }}" required>
                        @error('name') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="{{ $label }}">Base SKU *</label>
                        <input type="text" name="sku" value="{{ old('sku', $product->sku ?? '') }}" placeholder="PRD-100" class="{{ $input }} uppercase" required>
                        @error('sku') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="{{ $label }}">Short description</label>
                    <textarea name="short_description" rows="2" maxlength="500" x-model="shortDescription" class="{{ $input }}"></textarea>
                    @error('short_description') <span class="{{ $err }}">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="{{ $label }}">Full description</label>
                    <textarea name="description" rows="6" class="{{ $input }}">{{ old('description', $product->description ?? '') }}</textarea>
                    @error('description') <span class="{{ $err }}">{{ $message }}</span> @enderror
                </div>
            </section>

            {{-- Pricing & stock --}}
            <section class="{{ $card }}">
                <h3 class="text-sm font-bold text-slate-800">Pricing and stock</h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="{{ $label }}">Base price (৳) *</label>
                        <input type="number" step="0.01" min="0" name="price" x-model="price" value="{{ old('price', $product->price ?? '') }}" class="{{ $input }}" required>
                        @error('price') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="{{ $label }}">Discount price (৳)</label>
                        <input type="number" step="0.01" min="0" name="discount_price" x-model="discount" value="{{ old('discount_price', $product->discount_price ?? '') }}" class="{{ $input }}">
                        <p class="text-[11px] mt-1 text-emerald-600" x-show="discountPercent > 0" x-cloak>
                            <span x-text="discountPercent"></span>% off
                        </p>
                        @error('discount_price') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="{{ $label }}">Stock quantity <span x-show="!hasVariants">*</span></label>

                        <input type="number" min="0" name="stock_quantity"
                               value="{{ old('stock_quantity', $product->stock_quantity ?? 10) }}"
                               x-show="!hasVariants" :disabled="hasVariants" :required="!hasVariants"
                               class="{{ $input }}">

                        <div x-show="hasVariants" x-cloak class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-slate-600">
                            <span class="font-bold text-slate-800" x-text="totalStock"></span> pcs across variants
                        </div>
                        @error('stock_quantity') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                </div>
            </section>

            {{-- Gallery --}}
            <section class="{{ $card }}">
                <h3 class="text-sm font-bold text-slate-800">Gallery images</h3>

                @if ($isEdit && $product->images->count() > 0)
                    <div>
                        <p class="text-[11px] text-slate-500 mb-2">Current images. Removing one deletes it immediately.</p>
                        <div class="grid grid-cols-4 md:grid-cols-6 gap-3">
                            @foreach ($product->images as $img)
                                <div id="gallery-box-{{ $img->id }}" class="relative rounded-xl overflow-hidden border border-slate-200">
                                    <img src="{{ $img->image_url }}" class="w-full h-20 object-cover" alt="">
                                    <button type="button" onclick="deleteGalleryImage({{ $img->id }})"
                                            class="absolute top-1 right-1 bg-rose-600 text-white p-1 rounded-lg hover:bg-rose-700" aria-label="Delete image">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="border-2 border-dashed rounded-2xl p-5 text-center cursor-pointer transition-colors bg-slate-50/50"
                     :class="galleryDrag ? 'border-indigo-500 bg-indigo-50/50' : 'border-slate-200 hover:border-indigo-400'"
                     @click="$refs.galleryInput.click()"
                     @dragover.prevent="galleryDrag = true"
                     @dragleave.prevent="galleryDrag = false"
                     @drop.prevent="galleryDrag = false; addGallery(Array.from($event.dataTransfer.files))">
                    <svg class="w-7 h-7 mx-auto text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <p class="text-xs text-slate-500 font-medium mt-1">Drop images here or click to browse</p>
                    <p class="text-[10px] text-slate-400">JPG, PNG or WebP, up to 3 MB each</p>
                    <input type="file" x-ref="galleryInput" name="gallery_images[]" multiple accept="image/*" class="sr-only" tabindex="-1"
                           @click.stop @change="addGallery(Array.from($event.target.files))">
                </div>
                @error('gallery_images.*') <span class="{{ $err }}">{{ $message }}</span> @enderror

                <div class="grid grid-cols-4 md:grid-cols-6 gap-3" x-show="galleryPreviews.length > 0" x-cloak>
                    <template x-for="(img, index) in galleryPreviews" :key="img">
                        <div class="relative rounded-xl overflow-hidden border border-indigo-200 ring-2 ring-indigo-100">
                            <img :src="img" class="w-full h-20 object-cover" alt="">
                            <span class="absolute bottom-1 left-1 bg-indigo-600 text-white text-[9px] font-bold px-1.5 py-0.5 rounded">New</span>
                            <button type="button" @click="removeGalleryImage(index)" class="absolute top-1 right-1 bg-rose-600 text-white p-1 rounded-lg" aria-label="Remove image">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </template>
                </div>
            </section>

            {{-- Variants --}}
            <section class="{{ $card }}">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800">Variants</h3>
                        <p class="text-[11px] text-slate-500">Color, size or any option that has its own SKU, price and stock.</p>
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer shrink-0">
                        <span class="text-xs font-semibold text-slate-700">This product has variants</span>
                        <span class="relative inline-flex items-center">
                            <input type="checkbox" name="has_variants" value="1" x-model="hasVariants" @change="if (hasVariants && variants.length === 0) addVariant()" class="sr-only peer">
                            <span class="w-9 h-5 bg-slate-200 rounded-full peer-checked:bg-indigo-600 peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-400 transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-4"></span>
                        </span>
                    </label>
                </div>

                <div x-show="hasVariants" x-cloak class="space-y-3">
                    <template x-for="(variant, index) in variants" :key="variant._key">
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                            <input type="hidden" :name="`variants[${index}][id]`" :value="variant.id" :disabled="!variant.id">

                            <div class="flex items-start gap-3">
                                {{-- image --}}
                                <label class="shrink-0 cursor-pointer group" title="Variant image">
                                    <span class="block w-16 h-16 rounded-xl border border-dashed border-slate-300 bg-white overflow-hidden grid place-items-center group-hover:border-indigo-400">
                                        <template x-if="variant.image_url">
                                            <img :src="variant.image_url" class="w-full h-full object-cover" alt="">
                                        </template>
                                        <template x-if="!variant.image_url">
                                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4-4a3 3 0 014 0l4 4M14 8a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                        </template>
                                    </span>
                                    <input type="file" accept="image/*" class="sr-only" :name="`variants[${index}][image]`" @change="setVariantImage($event, variant)">
                                </label>

                                <div class="flex-1 grid grid-cols-2 md:grid-cols-5 gap-2">
                                    <div>
                                        <label class="text-[10px] font-semibold text-slate-500">Option</label>
                                        <input type="text" :name="`variants[${index}][attribute_name]`" x-model="variant.attribute_name" placeholder="Size" class="w-full text-xs rounded-lg border-slate-200" required>
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-semibold text-slate-500">Value</label>
                                        <input type="text" :name="`variants[${index}][attribute_value]`" x-model="variant.attribute_value" placeholder="XL" class="w-full text-xs rounded-lg border-slate-200" required>
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-semibold text-slate-500">SKU</label>
                                        <input type="text" :name="`variants[${index}][sku]`" x-model="variant.sku" placeholder="PRD-100-XL" class="w-full text-xs rounded-lg border-slate-200 uppercase" required>
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-semibold text-slate-500">Price (৳)</label>
                                        <input type="number" step="0.01" min="0" :name="`variants[${index}][price]`" x-model="variant.price" class="w-full text-xs rounded-lg border-slate-200" required>
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-semibold text-slate-500">Stock</label>
                                        <input type="number" min="0" :name="`variants[${index}][stock]`" x-model="variant.stock" class="w-full text-xs rounded-lg border-slate-200" required>
                                    </div>
                                </div>

                                <div class="flex flex-col gap-1 shrink-0 text-[11px] font-semibold">
                                    <button type="button" @click="duplicateVariant(index)" class="text-indigo-600 hover:text-indigo-800">Duplicate</button>
                                    <button type="button" @click="removeVariant(index)" class="text-rose-600 hover:text-rose-800">Remove</button>
                                </div>
                            </div>
                        </div>
                    </template>

                    <button type="button" @click="addVariant()" class="w-full py-2.5 border border-dashed border-indigo-300 text-indigo-600 font-semibold text-xs rounded-xl hover:bg-indigo-50 transition-colors">
                        Add variant
                    </button>
                </div>
            </section>

            {{-- SEO --}}
            <section class="{{ $card }}">
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Search engine listing</h3>
                    <p class="text-[11px] text-slate-500">Controls how this product appears on Google. Leave blank to use the product name and short description.</p>
                </div>

                {{-- Google-style preview --}}
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <p class="text-[11px] text-slate-500 truncate" x-text="serpUrl"></p>
                    <p class="text-[15px] leading-snug text-indigo-700 font-medium truncate" x-text="serpTitle"></p>
                    <p class="text-xs text-slate-600 line-clamp-2" x-text="serpDescription"></p>
                </div>

                <div>
                    <div class="flex justify-between">
                        <label class="{{ $label }}">Meta title</label>
                        <span class="text-[10px]" :class="metaTitle.length > 70 ? 'text-rose-500' : 'text-slate-400'" x-text="`${metaTitle.length}/70`"></span>
                    </div>
                    <input type="text" name="meta_title" x-model="metaTitle" maxlength="70" class="{{ $input }}">
                    @error('meta_title') <span class="{{ $err }}">{{ $message }}</span> @enderror
                </div>

                <div>
                    <div class="flex justify-between">
                        <label class="{{ $label }}">Meta description</label>
                        <span class="text-[10px]" :class="metaDescription.length > 160 ? 'text-rose-500' : 'text-slate-400'" x-text="`${metaDescription.length}/160`"></span>
                    </div>
                    <textarea name="meta_description" rows="2" x-model="metaDescription" maxlength="160" class="{{ $input }}"></textarea>
                    @error('meta_description') <span class="{{ $err }}">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="{{ $label }}">Meta keywords</label>
                        <input type="text" name="meta_keywords" value="{{ old('meta_keywords', $seo['meta_keywords'] ?? '') }}" placeholder="running shoes, men, lightweight" class="{{ $input }}">
                        <p class="text-[10px] text-slate-400 mt-1">Separate with commas.</p>
                        @error('meta_keywords') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="{{ $label }}">URL slug</label>
                        <input type="text" name="slug" x-model="slug" placeholder="auto-generated from name" class="{{ $input }}">
                        <p class="text-[10px] text-slate-400 mt-1">Lowercase letters, numbers and dashes only.</p>
                        @error('slug') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                </div>
            </section>
        </div>

        {{-- ======================= SIDEBAR ======================= --}}
        <aside class="space-y-4 lg:sticky lg:top-6">

            {{-- Publish --}}
            <section class="{{ $card }}">
                <h3 class="text-sm font-bold text-slate-800">Visibility</h3>

                <label class="flex items-center justify-between cursor-pointer">
                    <span class="text-xs font-medium text-slate-700">Active (visible in store)</span>
                    <span class="relative inline-flex items-center">
                        <input type="checkbox" name="is_active" value="1" @checked($isActive) class="sr-only peer">
                        <span class="w-9 h-5 bg-slate-200 rounded-full peer-checked:bg-emerald-500 peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-400 transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-4"></span>
                    </span>
                </label>

                <label class="flex items-center justify-between cursor-pointer">
                    <span class="text-xs font-medium text-slate-700">Featured product</span>
                    <span class="relative inline-flex items-center">
                        <input type="checkbox" name="is_featured" value="1" @checked($isFeatured) class="sr-only peer">
                        <span class="w-9 h-5 bg-slate-200 rounded-full peer-checked:bg-indigo-600 peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-400 transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-4"></span>
                    </span>
                </label>

                <div class="pt-2 flex gap-2">
                    <a href="{{ route('admin.products.index') }}" class="flex-1 text-center px-4 py-2.5 border border-slate-200 text-slate-600 font-semibold text-xs rounded-xl hover:bg-slate-50">Cancel</a>
                    <button type="submit" class="flex-1 px-4 py-2.5 bg-indigo-600 text-white font-semibold text-xs rounded-xl shadow-lg shadow-indigo-600/30 hover:bg-indigo-700 transition-all">
                        {{ $isEdit ? 'Save changes' : 'Save product' }}
                    </button>
                </div>
            </section>

            {{-- Featured image --}}
            <section class="{{ $card }}">
                <h3 class="text-sm font-bold text-slate-800">Main image {{ $isEdit ? '' : '*' }}</h3>

                <div class="border-2 border-dashed rounded-2xl p-3 text-center cursor-pointer transition-colors bg-slate-50/50"
                     :class="featuredDrag ? 'border-indigo-500 bg-indigo-50/50' : 'border-slate-200 hover:border-indigo-400'"
                     @click="$refs.featuredInput.click()"
                     @dragover.prevent="featuredDrag = true"
                     @dragleave.prevent="featuredDrag = false"
                     @drop.prevent="featuredDrag = false; dropFeatured($event)">

                    <div x-show="!featuredPreview" class="py-4 space-y-1">
                        <svg class="w-8 h-8 mx-auto text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4-4a3 3 0 014 0l4 4m0 0l4-4a3 3 0 014 0l4 4M14 8a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <p class="text-xs text-slate-500 font-medium">Drop an image or click to upload</p>
                    </div>

                    <div x-show="featuredPreview" x-cloak class="relative">
                        <img :src="featuredPreview" class="w-full aspect-square rounded-xl object-cover border border-slate-200" alt="Main image preview">
                        <button type="button" @click.stop="removeFeatured()"
                                class="absolute top-2 right-2 bg-white/90 text-rose-600 text-[11px] font-semibold px-2 py-1 rounded-lg shadow hover:bg-white"
                                x-text="hasOriginalFeatured && featuredPreview !== originalFeatured ? 'Undo' : (hasOriginalFeatured ? 'Replace' : 'Remove')"></button>
                    </div>

                    <input type="file" x-ref="featuredInput" name="featured_image" accept="image/*" class="sr-only" tabindex="-1"
                           @click.stop @change="previewFeatured($event)">
                </div>
                @error('featured_image') <span class="{{ $err }}">{{ $message }}</span> @enderror
            </section>

            {{-- Organization --}}
            <section class="{{ $card }}">
                <h3 class="text-sm font-bold text-slate-800">Organization</h3>

                <div>
                    <label class="{{ $label }}">Category *</label>
                    <select name="category_id" class="{{ $input }}" required>
                        <option value="">Select category</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id ?? '') == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id') <span class="{{ $err }}">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="{{ $label }}">Brand</label>
                    <select name="brand_id" class="{{ $input }}">
                        <option value="">No brand</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}" @selected(old('brand_id', $product->brand_id ?? '') == $brand->id)>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                    @error('brand_id') <span class="{{ $err }}">{{ $message }}</span> @enderror
                </div>
            </section>
        </aside>
    </div>
</form>

<script>
    function productForm(cfg) {
        let uid = 0;
        const withKey = v => ({ image_url: null, ...v, _key: ++uid });

        return {
            // basic
            name: cfg.name,
            slug: cfg.slug,
            shortDescription: cfg.shortDescription,
            price: @json(old('price', $product->price ?? '')),
            discount: @json(old('discount_price', $product->discount_price ?? '')),

            // images
            originalFeatured: cfg.featuredUrl,
            hasOriginalFeatured: !!cfg.featuredUrl,
            featuredPreview: cfg.featuredUrl,
            featuredDrag: false,
            galleryDrag: false,
            galleryFiles: [],
            galleryPreviews: [],

            // variants
            hasVariants: cfg.hasVariants,
            variants: cfg.variants.map(withKey),

            // seo
            metaTitle: cfg.metaTitle,
            metaDescription: cfg.metaDescription,

            /* ---------- computed ---------- */
            get totalStock() {
                return this.variants.reduce((s, v) => s + (parseInt(v.stock) || 0), 0);
            },
            get discountPercent() {
                const p = parseFloat(this.price), d = parseFloat(this.discount);
                return p > 0 && d > 0 && d < p ? Math.round((1 - d / p) * 100) : 0;
            },
            get slugPreview() {
                return this.slug || this.name.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
            },
            get serpUrl() { return `${cfg.siteUrl}/products/${this.slugPreview || 'your-product'}`; },
            get serpTitle() { return this.metaTitle || this.name || 'Product title'; },
            get serpDescription() {
                return this.metaDescription || this.shortDescription || 'Add a meta description to control how this product appears in search results.';
            },

            /* ---------- featured image ---------- */
            previewFeatured(e) {
                const file = e.target.files[0];
                if (file) this.featuredPreview = URL.createObjectURL(file);
            },
            dropFeatured(e) {
                const file = Array.from(e.dataTransfer.files).find(f => f.type.startsWith('image/'));
                if (!file) return;
                const dt = new DataTransfer();
                dt.items.add(file);
                this.$refs.featuredInput.files = dt.files;
                this.featuredPreview = URL.createObjectURL(file);
            },
            removeFeatured() {
                this.$refs.featuredInput.value = '';
                this.featuredPreview = this.originalFeatured; // null on create, current image on edit
            },

            /* ---------- gallery ---------- */
            addGallery(files) {
                files.filter(f => f.type.startsWith('image/')).forEach(f => {
                    this.galleryFiles.push(f);
                    this.galleryPreviews.push(URL.createObjectURL(f));
                });
                this.syncGallery();
            },
            removeGalleryImage(index) {
                this.galleryFiles.splice(index, 1);
                this.galleryPreviews.splice(index, 1);
                this.syncGallery();
            },
            syncGallery() {
                const dt = new DataTransfer();
                this.galleryFiles.forEach(f => dt.items.add(f));
                this.$refs.galleryInput.files = dt.files;
            },

            /* ---------- variants ---------- */
            addVariant() {
                this.variants.push(withKey({ id: null, attribute_name: '', attribute_value: '', sku: '', price: this.price || '', stock: 0 }));
            },
            duplicateVariant(index) {
                const v = this.variants[index];
                this.variants.splice(index + 1, 0, withKey({
                    id: null, attribute_name: v.attribute_name, attribute_value: '', sku: '', price: v.price, stock: 0
                }));
            },
            removeVariant(index) {
                this.variants.splice(index, 1);
            },
            setVariantImage(e, variant) {
                const file = e.target.files[0];
                if (file) variant.image_url = URL.createObjectURL(file);
            },
        };
    }

    @if ($isEdit)
    function deleteGalleryImage(id) {
        const notifyError = (msg) => {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: 'Could not delete image', text: msg });
            } else {
                alert('Could not delete image: ' + msg);
            }
        };

        const run = () => {
            fetch(`/admin/product-images/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
                .then(async res => {
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok || !data.success) {
                        throw new Error(data.message || `Request failed (HTTP ${res.status})`);
                    }
                    return data;
                })
                .then(data => {
                    document.getElementById(`gallery-box-${id}`)?.remove();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'success', title: 'Deleted', text: data.message, toast: true, position: 'top-end', showConfirmButton: false, timer: 2500 });
                    }
                })
                .catch(err => notifyError(err.message));
        };

        if (typeof Swal === 'undefined') {
            if (confirm('Delete this image?')) run();
            return;
        }

        Swal.fire({
            title: 'Delete this image?',
            text: 'This cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f43f5e',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Delete',
            customClass: {
                confirmButton: 'rounded-xl text-xs font-semibold px-4 py-2',
                cancelButton: 'rounded-xl text-xs font-semibold px-4 py-2'
            }
        }).then(result => { if (result.isConfirmed) run(); });
    }
    @endif
</script>

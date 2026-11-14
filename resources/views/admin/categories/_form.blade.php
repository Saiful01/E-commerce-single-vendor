{{--
    Shared form for create + edit.
    Expects: $action, $method ('POST' | 'PUT'), $category (null on create), $parentOptions
--}}
@php
    $isEdit = ! is_null($category);
    $hasOld = session()->hasOldInput();

    $cfg = [
        'name'            => old('name', $category->name ?? ''),
        'slug'            => old('slug', $category->slug ?? ''),
        'metaTitle'       => old('meta_title', $category->meta_title ?? ''),
        'metaDescription' => old('meta_description', $category->meta_description ?? ''),
        'description'     => old('description', $category->description ?? ''),
        'imageUrl'        => $isEdit ? $category->image_url : null,
        'siteUrl'         => rtrim(config('app.url'), '/'),
    ];

    $isActive   = $hasOld ? (bool) old('is_active')    : ($category->is_active ?? true);
    $isFeatured = $hasOld ? (bool) old('is_featured')  : ($category->is_featured ?? false);
    $inMenu     = $hasOld ? (bool) old('show_in_menu') : ($category->show_in_menu ?? true);

    $selectedParent = old('parent_id', $category->parent_id ?? request('parent'));

    $input = 'w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500';
    $label = 'block text-xs font-semibold text-slate-700 mb-1';
    $card  = 'bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-4';
    $err   = 'text-rose-500 text-[11px] mt-1 block';
    $toggle = "w-9 h-5 bg-slate-200 rounded-full peer-checked:bg-indigo-600 peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-400 transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-4";
    $toggleGreen = str_replace('peer-checked:bg-indigo-600', 'peer-checked:bg-emerald-500', $toggle);
@endphp

<form action="{{ $action }}" method="POST" enctype="multipart/form-data"
      x-data="categoryForm({{ \Illuminate\Support\Js::from($cfg) }})" class="space-y-4">
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

            {{-- Basics --}}
            <section class="{{ $card }}">
                <h3 class="text-sm font-bold text-slate-800">Basic information</h3>

                <div>
                    <label class="{{ $label }}">Category name *</label>
                    <input type="text" name="name" x-model="name" maxlength="255" placeholder="e.g. Men's Shoes" class="{{ $input }}">
                    @error('name') <span class="{{ $err }}">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="{{ $label }}">Description</label>
                    <textarea name="description" rows="4" maxlength="2000" x-model="description" class="{{ $input }}"></textarea>
                    <p class="text-[10px] text-slate-400 mt-1">Shown at the top of the category page.</p>
                    @error('description') <span class="{{ $err }}">{{ $message }}</span> @enderror
                </div>
            </section>

            {{-- Image --}}
            <section class="{{ $card }}">
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Image</h3>
                    <p class="text-[11px] text-slate-500">Used on category tiles and in the menu. Square, at least 600 × 600 px. JPG, PNG or WebP, up to 2 MB.</p>
                </div>

                <div class="border-2 border-dashed rounded-2xl p-3 text-center cursor-pointer transition-colors bg-slate-50/50"
                     :class="drag ? 'border-indigo-500 bg-indigo-50/50' : 'border-slate-200 hover:border-indigo-400'"
                     @click="$refs.imageInput.click()"
                     @dragover.prevent="drag = true"
                     @dragleave.prevent="drag = false"
                     @drop.prevent="drag = false; dropImage($event)">

                    <div x-show="!imagePreview" class="py-8 space-y-1">
                        <svg class="w-8 h-8 mx-auto text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4-4a3 3 0 014 0l4 4m0 0l4-4a3 3 0 014 0l4 4M14 8a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <p class="text-xs text-slate-500 font-medium">Drop an image or click to upload</p>
                    </div>

                    <div x-show="imagePreview" x-cloak class="relative inline-block">
                        <img :src="imagePreview" class="h-40 w-40 rounded-xl object-cover border border-slate-200" alt="Category image preview">
                        <button type="button" @click.stop="clearImage()"
                                class="absolute top-2 right-2 bg-white/90 text-rose-600 text-[11px] font-semibold px-2 py-1 rounded-lg shadow hover:bg-white"
                                x-text="hasOriginal && imagePreview !== originalImage ? 'Undo' : 'Remove'"></button>
                    </div>

                    <input type="file" x-ref="imageInput" name="image" accept="image/*" class="sr-only" tabindex="-1"
                           @click.stop @change="pickImage($event)">
                </div>
                <input type="hidden" name="remove_image" :value="removeImage ? 1 : 0">
                @error('image') <span class="{{ $err }}">{{ $message }}</span> @enderror
            </section>

            {{-- SEO --}}
            <section class="{{ $card }}">
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Search engine listing</h3>
                    <p class="text-[11px] text-slate-500">How this category appears on Google. Leave blank to use the name and description.</p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <p class="text-[11px] text-slate-500 truncate" x-text="serpUrl"></p>
                    <p class="text-[15px] leading-snug text-indigo-700 font-medium truncate" x-text="metaTitle || name || 'Category title'"></p>
                    <p class="text-xs text-slate-600 line-clamp-2" x-text="metaDescription || description || 'Add a meta description to control how this category appears in search results.'"></p>
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
                        <input type="text" name="meta_keywords" value="{{ old('meta_keywords', $category->meta_keywords ?? '') }}" placeholder="shoes, sneakers, men" class="{{ $input }}">
                        @error('meta_keywords') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="{{ $label }}">URL slug</label>
                        <input type="text" name="slug" x-model="slug" placeholder="{{ $isEdit ? 'unchanged' : 'auto-generated from name' }}" class="{{ $input }}">
                        <p class="text-[10px] text-slate-400 mt-1">
                            @if ($isEdit) Changing this changes the category link. @else Lowercase letters, numbers and dashes only. @endif
                        </p>
                        @error('slug') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                </div>
            </section>
        </div>

        {{-- ======================= SIDEBAR ======================= --}}
        <aside class="space-y-4 lg:sticky lg:top-6">

            <section class="{{ $card }}">
                <h3 class="text-sm font-bold text-slate-800">Visibility</h3>

                <label class="flex items-center justify-between cursor-pointer">
                    <span>
                        <span class="block text-xs font-medium text-slate-700">Active</span>
                        <span class="block text-[11px] text-slate-500">Visible in the store.</span>
                    </span>
                    <span class="relative inline-flex items-center shrink-0">
                        <input type="checkbox" name="is_active" value="1" @checked($isActive) class="sr-only peer">
                        <span class="{{ $toggleGreen }}"></span>
                    </span>
                </label>

                <label class="flex items-center justify-between cursor-pointer">
                    <span>
                        <span class="block text-xs font-medium text-slate-700">Featured</span>
                        <span class="block text-[11px] text-slate-500">Highlight on the home page.</span>
                    </span>
                    <span class="relative inline-flex items-center shrink-0">
                        <input type="checkbox" name="is_featured" value="1" @checked($isFeatured) class="sr-only peer">
                        <span class="{{ $toggle }}"></span>
                    </span>
                </label>

                <label class="flex items-center justify-between cursor-pointer">
                    <span>
                        <span class="block text-xs font-medium text-slate-700">Show in menu</span>
                        <span class="block text-[11px] text-slate-500">List in the navigation menu.</span>
                    </span>
                    <span class="relative inline-flex items-center shrink-0">
                        <input type="checkbox" name="show_in_menu" value="1" @checked($inMenu) class="sr-only peer">
                        <span class="{{ $toggle }}"></span>
                    </span>
                </label>

                <div class="pt-2 flex gap-2">
                    <a href="{{ route('admin.categories.index') }}" class="flex-1 text-center px-4 py-2.5 border border-slate-200 text-slate-600 font-semibold text-xs rounded-xl hover:bg-slate-50">Cancel</a>
                    <button type="submit" class="flex-1 px-4 py-2.5 bg-indigo-600 text-white font-semibold text-xs rounded-xl shadow-lg shadow-indigo-600/30 hover:bg-indigo-700 transition-all">
                        {{ $isEdit ? 'Save changes' : 'Save category' }}
                    </button>
                </div>
            </section>

            <section class="{{ $card }}">
                <h3 class="text-sm font-bold text-slate-800">Organization</h3>

                <div>
                    <label class="{{ $label }}">Parent category</label>
                    <select name="parent_id" class="{{ $input }}">
                        <option value="">None (top level)</option>
                        @foreach ($parentOptions as $option)
                            <option value="{{ $option['id'] }}" @selected((string) $selectedParent === (string) $option['id'])>{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                    @if ($isEdit)
                        <p class="text-[10px] text-slate-400 mt-1">This category and its sub-categories are not listed here.</p>
                    @endif
                    @error('parent_id') <span class="{{ $err }}">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="{{ $label }}">Display order</label>
                    <input type="number" name="sort_order" min="0" max="9999" value="{{ old('sort_order', $category->sort_order ?? '') }}" placeholder="Auto (added last)" class="{{ $input }}">
                    <p class="text-[10px] text-slate-400 mt-1">Lower numbers come first among categories with the same parent.</p>
                    @error('sort_order') <span class="{{ $err }}">{{ $message }}</span> @enderror
                </div>
            </section>

            @if ($isEdit)
                <section class="{{ $card }}">
                    <h3 class="text-sm font-bold text-slate-800">In use</h3>
                    <dl class="text-xs space-y-2">
                        <div class="flex justify-between gap-3"><dt class="text-slate-400">Products</dt><dd class="font-semibold text-slate-800">{{ $category->products_count }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-400">Sub-categories</dt><dd class="font-semibold text-slate-800">{{ $category->children_count }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-400">Created</dt><dd class="font-semibold text-slate-800">{{ $category->created_at?->format('d M Y') }}</dd></div>
                    </dl>
                    <a href="{{ route('admin.categories.create', ['parent' => $category->id]) }}" class="inline-block text-xs font-semibold text-indigo-600 hover:text-indigo-800">Add a sub-category &rarr;</a>
                </section>
            @endif
        </aside>
    </div>
</form>

<script>
    function categoryForm(cfg) {
        return {
            name: cfg.name || '',
            slug: cfg.slug || '',
            description: cfg.description || '',
            metaTitle: cfg.metaTitle || '',
            metaDescription: cfg.metaDescription || '',

            originalImage: cfg.imageUrl,
            hasOriginal: !!cfg.imageUrl,
            imagePreview: cfg.imageUrl,
            removeImage: false,
            drag: false,

            get slugPreview() {
                return this.slug || this.name.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
            },
            get serpUrl() {
                return `${cfg.siteUrl}/category/${this.slugPreview || 'your-category'}`;
            },

            pickImage(e) {
                const f = e.target.files[0];
                if (!f) return;
                this.imagePreview = URL.createObjectURL(f);
                this.removeImage = false;
            },
            dropImage(e) {
                const f = Array.from(e.dataTransfer.files).find(x => x.type.startsWith('image/'));
                if (!f) return;
                const dt = new DataTransfer();
                dt.items.add(f);
                this.$refs.imageInput.files = dt.files;
                this.imagePreview = URL.createObjectURL(f);
                this.removeImage = false;
            },
            clearImage() {
                this.$refs.imageInput.value = '';
                if (this.hasOriginal && this.imagePreview !== this.originalImage) {
                    // undo a freshly chosen file, go back to the saved image
                    this.imagePreview = this.originalImage;
                    this.removeImage = false;
                } else {
                    this.imagePreview = null;
                    this.removeImage = this.hasOriginal;
                }
            },
        };
    }
</script>

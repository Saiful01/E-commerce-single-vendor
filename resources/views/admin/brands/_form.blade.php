{{--
    Shared form for create + edit.
    Expects: $action, $method ('POST' | 'PUT'), $brand (null on create)
--}}
@php
    $isEdit = ! is_null($brand);
    $hasOld = session()->hasOldInput();

    $cfg = [
        'name'            => old('name', $brand->name ?? ''),
        'slug'            => old('slug', $brand->slug ?? ''),
        'description'     => old('description', $brand->description ?? ''),
        'metaTitle'       => old('meta_title', $brand->meta_title ?? ''),
        'metaDescription' => old('meta_description', $brand->meta_description ?? ''),
        'logoUrl'         => $isEdit ? $brand->logo_url : null,
        'siteUrl'         => rtrim(config('app.url'), '/'),
    ];

    $isActive   = $hasOld ? (bool) old('is_active')   : ($brand->is_active ?? true);
    $isFeatured = $hasOld ? (bool) old('is_featured') : ($brand->is_featured ?? false);

    $input = 'w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500';
    $label = 'block text-xs font-semibold text-slate-700 mb-1';
    $card  = 'bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-4';
    $err   = 'text-rose-500 text-[11px] mt-1 block';
    $toggle = "w-9 h-5 bg-slate-200 rounded-full peer-checked:bg-indigo-600 peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-400 transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-4";
    $toggleGreen = str_replace('peer-checked:bg-indigo-600', 'peer-checked:bg-emerald-500', $toggle);
@endphp

<form action="{{ $action }}" method="POST" enctype="multipart/form-data"
      x-data="brandForm({{ \Illuminate\Support\Js::from($cfg) }})" class="space-y-4">
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

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="{{ $label }}">Brand name *</label>
                        <input type="text" name="name" x-model="name" maxlength="255" placeholder="e.g. Nike" class="{{ $input }}">
                        @error('name') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="{{ $label }}">Website</label>
                        <input type="url" name="website_url" value="{{ old('website_url', $brand->website_url ?? '') }}" placeholder="https://www.nike.com" class="{{ $input }}">
                        @error('website_url') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="{{ $label }}">Description</label>
                    <textarea name="description" rows="4" maxlength="2000" x-model="description" class="{{ $input }}"></textarea>
                    <p class="text-[10px] text-slate-400 mt-1">Shown at the top of the brand page.</p>
                    @error('description') <span class="{{ $err }}">{{ $message }}</span> @enderror
                </div>
            </section>

            {{-- Logo --}}
            <section class="{{ $card }}">
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Logo</h3>
                    <p class="text-[11px] text-slate-500">A square PNG with a transparent background works best, at least 400 × 400 px. PNG, JPG, WebP or SVG, up to 2 MB.</p>
                </div>

                <div class="border-2 border-dashed rounded-2xl p-3 text-center cursor-pointer transition-colors bg-slate-50/50"
                     :class="drag ? 'border-indigo-500 bg-indigo-50/50' : 'border-slate-200 hover:border-indigo-400'"
                     @click="$refs.logoInput.click()"
                     @dragover.prevent="drag = true"
                     @dragleave.prevent="drag = false"
                     @drop.prevent="drag = false; dropLogo($event)">

                    <div x-show="!logoPreview" class="py-8 space-y-1">
                        <svg class="w-8 h-8 mx-auto text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4-4a3 3 0 014 0l4 4m0 0l4-4a3 3 0 014 0l4 4M14 8a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <p class="text-xs text-slate-500 font-medium">Drop a logo or click to upload</p>
                    </div>

                    <div x-show="logoPreview" x-cloak class="relative inline-block">
                        <img :src="logoPreview" class="h-40 w-40 rounded-xl object-contain border border-slate-200 bg-white p-3" alt="Logo preview">
                        <button type="button" @click.stop="clearLogo()"
                                class="absolute top-2 right-2 bg-white/90 text-rose-600 text-[11px] font-semibold px-2 py-1 rounded-lg shadow hover:bg-white"
                                x-text="hasOriginal && logoPreview !== originalLogo ? 'Undo' : 'Remove'"></button>
                    </div>

                    <input type="file" x-ref="logoInput" name="logo" accept=".png,.jpg,.jpeg,.webp,.svg" class="sr-only" tabindex="-1"
                           @click.stop @change="pickLogo($event)">
                </div>
                <input type="hidden" name="remove_logo" :value="removeLogo ? 1 : 0">
                @error('logo') <span class="{{ $err }}">{{ $message }}</span> @enderror
            </section>

            {{-- SEO --}}
            <section class="{{ $card }}">
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Search engine listing</h3>
                    <p class="text-[11px] text-slate-500">How this brand appears on Google. Leave blank to use the name and description.</p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <p class="text-[11px] text-slate-500 truncate" x-text="serpUrl"></p>
                    <p class="text-[15px] leading-snug text-indigo-700 font-medium truncate" x-text="metaTitle || name || 'Brand title'"></p>
                    <p class="text-xs text-slate-600 line-clamp-2" x-text="metaDescription || description || 'Add a meta description to control how this brand appears in search results.'"></p>
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
                        <input type="text" name="meta_keywords" value="{{ old('meta_keywords', $brand->meta_keywords ?? '') }}" placeholder="sportswear, shoes, running" class="{{ $input }}">
                        @error('meta_keywords') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="{{ $label }}">URL slug</label>
                        <input type="text" name="slug" x-model="slug" placeholder="{{ $isEdit ? 'unchanged' : 'auto-generated from name' }}" class="{{ $input }}">
                        <p class="text-[10px] text-slate-400 mt-1">
                            @if ($isEdit) Changing this changes the brand link. @else Lowercase letters, numbers and dashes only. @endif
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
                        <span class="block text-[11px] text-slate-500">Show in "top brands" sections.</span>
                    </span>
                    <span class="relative inline-flex items-center shrink-0">
                        <input type="checkbox" name="is_featured" value="1" @checked($isFeatured) class="sr-only peer">
                        <span class="{{ $toggle }}"></span>
                    </span>
                </label>

                <div>
                    <label class="{{ $label }}">Display order</label>
                    <input type="number" name="sort_order" min="0" max="9999" value="{{ old('sort_order', $brand->sort_order ?? '') }}" placeholder="Auto (added last)" class="{{ $input }}">
                    <p class="text-[10px] text-slate-400 mt-1">Lower numbers come first.</p>
                    @error('sort_order') <span class="{{ $err }}">{{ $message }}</span> @enderror
                </div>

                <div class="pt-2 flex gap-2">
                    <a href="{{ route('admin.brands.index') }}" class="flex-1 text-center px-4 py-2.5 border border-slate-200 text-slate-600 font-semibold text-xs rounded-xl hover:bg-slate-50">Cancel</a>
                    <button type="submit" class="flex-1 px-4 py-2.5 bg-indigo-600 text-white font-semibold text-xs rounded-xl shadow-lg shadow-indigo-600/30 hover:bg-indigo-700 transition-all">
                        {{ $isEdit ? 'Save changes' : 'Save brand' }}
                    </button>
                </div>
            </section>

            @if ($isEdit)
                <section class="{{ $card }}">
                    <h3 class="text-sm font-bold text-slate-800">In use</h3>
                    <dl class="text-xs space-y-2">
                        <div class="flex justify-between gap-3"><dt class="text-slate-400">Products</dt><dd class="font-semibold text-slate-800">{{ $brand->products_count }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-400">Created</dt><dd class="font-semibold text-slate-800">{{ $brand->created_at?->format('d M Y') }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-400">Last updated</dt><dd class="font-semibold text-slate-800">{{ $brand->updated_at?->diffForHumans() }}</dd></div>
                    </dl>
                </section>
            @endif
        </aside>
    </div>
</form>

<script>
    function brandForm(cfg) {
        return {
            name: cfg.name || '',
            slug: cfg.slug || '',
            description: cfg.description || '',
            metaTitle: cfg.metaTitle || '',
            metaDescription: cfg.metaDescription || '',

            originalLogo: cfg.logoUrl,
            hasOriginal: !!cfg.logoUrl,
            logoPreview: cfg.logoUrl,
            removeLogo: false,
            drag: false,

            get slugPreview() {
                return this.slug || this.name.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
            },
            get serpUrl() {
                return `${cfg.siteUrl}/brand/${this.slugPreview || 'your-brand'}`;
            },

            pickLogo(e) {
                const f = e.target.files[0];
                if (!f) return;
                this.logoPreview = URL.createObjectURL(f);
                this.removeLogo = false;
            },
            dropLogo(e) {
                const f = Array.from(e.dataTransfer.files).find(x => x.type.startsWith('image/'));
                if (!f) return;
                const dt = new DataTransfer();
                dt.items.add(f);
                this.$refs.logoInput.files = dt.files;
                this.logoPreview = URL.createObjectURL(f);
                this.removeLogo = false;
            },
            clearLogo() {
                this.$refs.logoInput.value = '';
                if (this.hasOriginal && this.logoPreview !== this.originalLogo) {
                    // undo a freshly chosen file, go back to the saved logo
                    this.logoPreview = this.originalLogo;
                    this.removeLogo = false;
                } else {
                    this.logoPreview = null;
                    this.removeLogo = this.hasOriginal;
                }
            },
        };
    }
</script>

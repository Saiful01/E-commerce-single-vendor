{{--
    Shared form for create + edit.
    Expects: $action, $method ('POST' | 'PUT'), $slider (null on create), $categories, $brands, $products
--}}
@php
    $isEdit   = ! is_null($slider);
    $hasOld   = session()->hasOldInput();
    $positions = \App\Models\Slider::POSITIONS;

    $currentLinkType = old('link_type', $slider->link_type ?? 'none');
    $currentLinkId   = old('link_id', $slider->link_id ?? null);

    $cfg = [
        'position'  => old('position', $slider->position ?? request('position', 'home_hero')),
        'positions' => collect($positions)->map(fn ($p) => [
            'label' => $p['label'], 'size' => $p['size'], 'mobileSize' => $p['mobile_size'],
            'ratio' => $p['ratio'], 'hint' => $p['hint'],
        ])->all(),
        'linkType'  => $currentLinkType,
        'title'     => old('title', $slider->title ?? ''),
        'subtitle'  => old('subtitle', $slider->subtitle ?? ''),
        'btnText'   => old('btn_text', $slider->btn_text ?? ''),
        'textColor' => old('text_color', $slider->text_color ?? '#FFFFFF'),
        'textAlign' => old('text_align', $slider->text_align ?? 'left'),
        'overlay'   => (int) old('overlay_opacity', $slider->overlay_opacity ?? 30),
        'showText'  => $hasOld ? (bool) old('show_text') : ($slider->show_text ?? true),
        'imageUrl'  => $isEdit ? $slider->image_url : null,
        'mobileUrl' => ($isEdit && $slider->mobile_image) ? $slider->mobile_image_url : null,
    ];

    $isActive  = $hasOld ? (bool) old('status')       : ($slider->status ?? true);
    $newTab    = $hasOld ? (bool) old('open_new_tab') : ($slider->open_new_tab ?? false);

    $input = 'w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500';
    $label = 'block text-xs font-semibold text-slate-700 mb-1';
    $card  = 'bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-4';
    $err   = 'text-rose-500 text-[11px] mt-1 block';
    $toggle = "w-9 h-5 bg-slate-200 rounded-full peer-checked:bg-indigo-600 peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-400 transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-4";
@endphp

<form action="{{ $action }}" method="POST" enctype="multipart/form-data"
      x-data="bannerForm({{ \Illuminate\Support\Js::from($cfg) }})" class="space-y-4">
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

            {{-- Content --}}
            <section class="{{ $card }}">
                <h3 class="text-sm font-bold text-slate-800">Content</h3>

                <div>
                    <label class="{{ $label }}">Title *</label>
                    <input type="text" name="title" x-model="title" maxlength="255" class="{{ $input }}" placeholder="Winter sale is live">
                    <p class="text-[10px] text-slate-400 mt-1">Shown on the banner. Also the name you see in this admin list.</p>
                    @error('title') <span class="{{ $err }}">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="{{ $label }}">Subtitle</label>
                    <input type="text" name="subtitle" x-model="subtitle" maxlength="255" class="{{ $input }}" placeholder="Up to 40% off selected items">
                    @error('subtitle') <span class="{{ $err }}">{{ $message }}</span> @enderror
                </div>

                <label class="flex items-center justify-between cursor-pointer pt-1">
                    <span>
                        <span class="block text-xs font-semibold text-slate-700">Show text on the banner</span>
                        <span class="block text-[11px] text-slate-500">Turn off when the text is already part of the image.</span>
                    </span>
                    <span class="relative inline-flex items-center shrink-0">
                        <input type="checkbox" name="show_text" value="1" x-model="showText" class="sr-only peer">
                        <span class="{{ $toggle }}"></span>
                    </span>
                </label>
            </section>

            {{-- Images --}}
            <section class="{{ $card }}">
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Images</h3>
                    <p class="text-[11px] text-slate-500">JPG, PNG or WebP, up to 4 MB each.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="{{ $label }}">Desktop image {{ $isEdit ? '' : '*' }}</label>
                        <label class="block cursor-pointer rounded-2xl border-2 border-dashed border-slate-200 hover:border-indigo-400 bg-slate-50/50 p-2 transition-colors">
                            <span class="block rounded-xl overflow-hidden bg-slate-100 grid place-items-center min-h-24">
                                <img x-show="imagePreview" :src="imagePreview" class="w-full max-h-40 object-contain" alt="Desktop image preview">
                                <span x-show="!imagePreview" class="text-[11px] text-slate-400 py-8">Click to choose an image</span>
                            </span>
                            <input type="file" name="image" accept="image/*" class="sr-only" @change="pickImage($event)">
                        </label>
                        <p class="text-[10px] text-slate-400 mt-1">Recommended <span class="font-semibold" x-text="current.size"></span> px</p>
                        @error('image') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="{{ $label }}">Mobile image <span class="font-normal text-slate-400">(optional)</span></label>
                        <label class="block cursor-pointer rounded-2xl border-2 border-dashed border-slate-200 hover:border-indigo-400 bg-slate-50/50 p-2 transition-colors">
                            <span class="block rounded-xl overflow-hidden bg-slate-100 grid place-items-center min-h-24">
                                <img x-show="mobilePreview" :src="mobilePreview" class="w-full max-h-40 object-contain" alt="Mobile image preview">
                                <span x-show="!mobilePreview" class="text-[11px] text-slate-400 py-8">Uses the desktop image if empty</span>
                            </span>
                            <input type="file" name="mobile_image" accept="image/*" class="sr-only" @change="pickMobile($event)">
                        </label>
                        <div class="flex items-center justify-between mt-1">
                            <p class="text-[10px] text-slate-400">Recommended <span class="font-semibold" x-text="current.mobileSize"></span> px</p>
                            <button type="button" x-show="mobilePreview" @click="clearMobile()" class="text-[11px] font-semibold text-rose-600 hover:text-rose-800">Remove</button>
                        </div>
                        <input type="hidden" name="remove_mobile_image" :value="removeMobile ? 1 : 0">
                        @error('mobile_image') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                </div>
            </section>

            {{-- Link --}}
            <section class="{{ $card }}">
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Link and button</h3>
                    <p class="text-[11px] text-slate-500">Where the banner goes when a visitor clicks it.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="{{ $label }}">Link to</label>
                        <select name="link_type" x-model="linkType" class="{{ $input }}">
                            @foreach (\App\Models\Slider::LINK_TYPES as $value => $name)
                                <option value="{{ $value }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('link_type') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>

                    <div x-show="linkType !== 'none'" x-cloak>
                        <label class="{{ $label }}">Button text</label>
                        <input type="text" name="btn_text" x-model="btnText" maxlength="50" placeholder="Shop now" class="{{ $input }}">
                        <p class="text-[10px] text-slate-400 mt-1">Leave empty to make the whole banner clickable without a button.</p>
                        @error('btn_text') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                </div>

                {{-- Only the visible select is enabled, so only one link_id is submitted --}}
                <div x-show="linkType === 'custom'" x-cloak>
                    <label class="{{ $label }}">URL *</label>
                    <input type="text" name="btn_link" :disabled="linkType !== 'custom'" value="{{ old('btn_link', $slider->btn_link ?? '') }}" placeholder="/shop?sale=1 or https://example.com" class="{{ $input }}">
                    @error('btn_link') <span class="{{ $err }}">{{ $message }}</span> @enderror
                </div>

                <div x-show="linkType === 'category'" x-cloak>
                    <label class="{{ $label }}">Category *</label>
                    <select name="link_id" :disabled="linkType !== 'category'" class="{{ $input }}">
                        <option value="">Select category</option>
                        @foreach ($categories as $c)
                            <option value="{{ $c->id }}" @selected($currentLinkType === 'category' && (string) $currentLinkId === (string) $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div x-show="linkType === 'product'" x-cloak>
                    <label class="{{ $label }}">Product *</label>
                    <select name="link_id" :disabled="linkType !== 'product'" class="{{ $input }}">
                        <option value="">Select product</option>
                        @foreach ($products as $p)
                            <option value="{{ $p->id }}" @selected($currentLinkType === 'product' && (string) $currentLinkId === (string) $p->id)>{{ $p->name }} ({{ $p->sku }})</option>
                        @endforeach
                    </select>
                    @if ($products->count() >= 500)
                        <p class="text-[10px] text-slate-400 mt-1">Showing the first 500 products. Use Custom URL for others.</p>
                    @endif
                </div>

                <div x-show="linkType === 'brand'" x-cloak>
                    <label class="{{ $label }}">Brand *</label>
                    <select name="link_id" :disabled="linkType !== 'brand'" class="{{ $input }}">
                        <option value="">Select brand</option>
                        @foreach ($brands as $b)
                            <option value="{{ $b->id }}" @selected($currentLinkType === 'brand' && (string) $currentLinkId === (string) $b->id)>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                @error('link_id') <span class="{{ $err }}">{{ $message }}</span> @enderror

                <label x-show="linkType !== 'none'" x-cloak class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="open_new_tab" value="1" @checked($newTab) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-xs font-medium text-slate-700">Open in a new tab</span>
                </label>
            </section>

            {{-- Look --}}
            <section class="{{ $card }}" x-show="showText" x-cloak>
                <h3 class="text-sm font-bold text-slate-800">Text style</h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="{{ $label }}">Text color</label>
                        <div class="flex items-center gap-2">
                            <input type="color" x-model="textColor" class="h-9 w-12 shrink-0 rounded-lg border border-slate-200 p-1 cursor-pointer bg-white" aria-label="Text color picker">
                            <input type="text" name="text_color" x-model="textColor" maxlength="7" class="{{ $input }} font-mono uppercase">
                        </div>
                        @error('text_color') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="{{ $label }}">Text position</label>
                        <select name="text_align" x-model="textAlign" class="{{ $input }}">
                            <option value="left">Left</option>
                            <option value="center">Center</option>
                            <option value="right">Right</option>
                        </select>
                        @error('text_align') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="{{ $label }}">Dark overlay <span class="font-normal text-slate-400" x-text="`· ${overlay}%`"></span></label>
                        <input type="range" name="overlay_opacity" min="0" max="100" step="5" x-model.number="overlay" class="w-full accent-indigo-600">
                        <p class="text-[10px] text-slate-400">Raise it if the text is hard to read.</p>
                        @error('overlay_opacity') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                </div>
            </section>
            {{-- Keep these fields submitting valid values when the text section is hidden --}}
            <template x-if="!showText">
                <div>
                    <input type="hidden" name="text_color" :value="textColor">
                    <input type="hidden" name="text_align" :value="textAlign">
                    <input type="hidden" name="overlay_opacity" :value="overlay">
                </div>
            </template>

            {{-- Schedule --}}
            <section class="{{ $card }}">
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Schedule</h3>
                    <p class="text-[11px] text-slate-500">Optional. The banner appears only between these times. Leave both empty to keep it on.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="{{ $label }}">Starts at</label>
                        <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $slider?->starts_at?->format('Y-m-d\TH:i')) }}" class="{{ $input }}">
                        @error('starts_at') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="{{ $label }}">Ends at</label>
                        <input type="datetime-local" name="ends_at" value="{{ old('ends_at', $slider?->ends_at?->format('Y-m-d\TH:i')) }}" class="{{ $input }}">
                        @error('ends_at') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                </div>
            </section>
        </div>

        {{-- ======================= SIDEBAR ======================= --}}
        <aside class="space-y-4 lg:sticky lg:top-6">

            {{-- Placement + publish --}}
            <section class="{{ $card }}">
                <h3 class="text-sm font-bold text-slate-800">Placement</h3>

                <div>
                    <label class="{{ $label }}">Where should it appear? *</label>
                    <select name="position" x-model="position" class="{{ $input }}">
                        @foreach ($positions as $key => $p)
                            <option value="{{ $key }}">{{ $p['label'] }}</option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-slate-500 mt-1" x-text="current.hint"></p>
                    @error('position') <span class="{{ $err }}">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="{{ $label }}">Display order</label>
                    <input type="number" name="serial_order" min="0" max="9999" value="{{ old('serial_order', $slider->serial_order ?? '') }}" placeholder="Auto (added last)" class="{{ $input }}">
                    <p class="text-[10px] text-slate-400 mt-1">Lower numbers show first. You can also drag banners in the list.</p>
                    @error('serial_order') <span class="{{ $err }}">{{ $message }}</span> @enderror
                </div>

                <label class="flex items-center justify-between cursor-pointer">
                    <span class="text-xs font-medium text-slate-700">Active</span>
                    <span class="relative inline-flex items-center">
                        <input type="checkbox" name="status" value="1" @checked($isActive) class="sr-only peer">
                        <span class="{{ str_replace('peer-checked:bg-indigo-600', 'peer-checked:bg-emerald-500', $toggle) }}"></span>
                    </span>
                </label>

                <div class="pt-2 flex gap-2">
                    <a href="{{ route('admin.sliders.index') }}" class="flex-1 text-center px-4 py-2.5 border border-slate-200 text-slate-600 font-semibold text-xs rounded-xl hover:bg-slate-50">Cancel</a>
                    <button type="submit" class="flex-1 px-4 py-2.5 bg-indigo-600 text-white font-semibold text-xs rounded-xl shadow-lg shadow-indigo-600/30 hover:bg-indigo-700 transition-all">
                        {{ $isEdit ? 'Save changes' : 'Save banner' }}
                    </button>
                </div>
            </section>

            {{-- Live preview --}}
            <section class="{{ $card }}">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-800">Preview</h3>
                    <div class="flex rounded-lg border border-slate-200 overflow-hidden text-[11px] font-semibold">
                        <button type="button" @click="device = 'desktop'" :class="device === 'desktop' ? 'bg-indigo-600 text-white' : 'text-slate-500 hover:bg-slate-50'" class="px-2.5 py-1">Desktop</button>
                        <button type="button" @click="device = 'mobile'" :class="device === 'mobile' ? 'bg-indigo-600 text-white' : 'text-slate-500 hover:bg-slate-50'" class="px-2.5 py-1">Mobile</button>
                    </div>
                </div>

                <div :class="device === 'mobile' ? 'max-w-[220px] mx-auto' : ''">
                    <div class="relative rounded-xl overflow-hidden bg-slate-200 border border-slate-200" :style="{ aspectRatio: previewRatio }">
                        <img x-show="previewImage" :src="previewImage" class="absolute inset-0 w-full h-full object-cover" alt="">
                        <span x-show="!previewImage" class="absolute inset-0 grid place-items-center text-[11px] text-slate-400">No image yet</span>

                        <template x-if="showText">
                            <div>
                                <div class="absolute inset-0" :style="{ background: `rgba(0,0,0,${overlay / 100})` }"></div>
                                <div class="absolute inset-0 flex flex-col justify-center gap-1 p-3"
                                     :class="{ 'items-start text-left': textAlign === 'left', 'items-center text-center': textAlign === 'center', 'items-end text-right': textAlign === 'right' }"
                                     :style="{ color: textColor }">
                                    <p class="font-bold leading-tight" :class="device === 'mobile' ? 'text-sm' : 'text-base'" x-text="title || 'Banner title'"></p>
                                    <p class="text-[11px] opacity-90" x-show="subtitle" x-text="subtitle"></p>
                                    <span x-show="linkType !== 'none' && btnText" class="mt-1 inline-block px-3 py-1 rounded-md bg-indigo-600 text-white text-[11px] font-semibold" x-text="btnText"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
                <p class="text-[10px] text-slate-400">Approximate. The button on your site uses the primary color from Site Settings.</p>
            </section>
        </aside>
    </div>
</form>

<script>
    function bannerForm(cfg) {
        return {
            position: cfg.position,
            positions: cfg.positions,
            linkType: cfg.linkType,
            title: cfg.title || '',
            subtitle: cfg.subtitle || '',
            btnText: cfg.btnText || '',
            textColor: cfg.textColor,
            textAlign: cfg.textAlign,
            overlay: cfg.overlay,
            showText: cfg.showText,

            imagePreview: cfg.imageUrl,
            mobilePreview: cfg.mobileUrl,
            removeMobile: false,
            device: 'desktop',

            get current() {
                return this.positions[this.position] || { size: '', mobileSize: '', ratio: '2/1', hint: '' };
            },
            get previewRatio() {
                return this.device === 'mobile' && this.position === 'home_hero' ? '4/3' : this.current.ratio;
            },
            get previewImage() {
                return this.device === 'mobile' ? (this.mobilePreview || this.imagePreview) : this.imagePreview;
            },

            pickImage(e) {
                const f = e.target.files[0];
                if (f) this.imagePreview = URL.createObjectURL(f);
            },
            pickMobile(e) {
                const f = e.target.files[0];
                if (!f) return;
                this.mobilePreview = URL.createObjectURL(f);
                this.removeMobile = false;
            },
            clearMobile() {
                document.querySelector('input[name=mobile_image]').value = '';
                this.mobilePreview = null;
                this.removeMobile = true;
            },
        };
    }
</script>

@extends('layouts.admin')

@section('title', 'Site Settings')

@section('content')
    @php
        $tabs = [
            'general' => ['label' => 'General',        'fields' => ['site_name', 'site_tagline', 'site_logo', 'site_favicon', 'footer_text']],
            'theme'   => ['label' => 'Theme colors',   'fields' => ['primary_color', 'secondary_color', 'header_bg_color', 'footer_bg_color', 'text_color']],
            'contact' => ['label' => 'Contact',        'fields' => ['primary_email', 'phone_number', 'whatsapp_number', 'address', 'business_hours']],
            'social'  => ['label' => 'Social links',   'fields' => ['facebook_url', 'instagram_url', 'youtube_url', 'twitter_url', 'linkedin_url', 'tiktok_url']],
            'store'   => ['label' => 'Store',          'fields' => ['currency_symbol', 'currency_code', 'currency_position', 'delivery_charge_inside_dhaka', 'delivery_charge_outside_dhaka', 'free_shipping_min']],
            'seo'     => ['label' => 'SEO & tracking', 'fields' => ['meta_title', 'meta_description', 'meta_keywords', 'google_analytics_id', 'facebook_pixel_id']],
        ];

        // After a failed save, open the first tab that has an error
        $initialTab = collect($tabs)->search(fn ($t) => $errors->hasAny($t['fields'])) ?: 'general';

        $colors = [
            'primary_color'   => ['Primary color',      'Buttons, links and highlights'],
            'secondary_color' => ['Secondary color',    'Accents and hover states'],
            'header_bg_color' => ['Header background',  'Top navigation bar'],
            'footer_bg_color' => ['Footer background',  'Bottom of every page'],
            'text_color'      => ['Main text color',    'Body text'],
        ];

        $socials = [
            'facebook_url'  => ['Facebook',    'https://facebook.com/yourpage'],
            'instagram_url' => ['Instagram',   'https://instagram.com/yourhandle'],
            'youtube_url'   => ['YouTube',     'https://youtube.com/@yourchannel'],
            'twitter_url'   => ['X (Twitter)', 'https://x.com/yourhandle'],
            'linkedin_url'  => ['LinkedIn',    'https://linkedin.com/company/yourcompany'],
            'tiktok_url'    => ['TikTok',      'https://tiktok.com/@yourhandle'],
        ];

        $cfg = [
            'tab'             => $initialTab,
            'colors'          => collect(array_keys($colors))->mapWithKeys(fn ($k) => [$k => old($k, $setting->{$k})])->all(),
            'logoUrl'         => $setting->logo_url,
            'faviconUrl'      => $setting->favicon_url,
            'siteName'        => old('site_name', $setting->site_name),
            'tagline'         => old('site_tagline', $setting->site_tagline),
            'footerText'      => old('footer_text', $setting->footer_text),
            'symbol'          => old('currency_symbol', $setting->currency_symbol),
            'position'        => old('currency_position', $setting->currency_position),
            'metaTitle'       => old('meta_title', $setting->meta_title),
            'metaDescription' => old('meta_description', $setting->meta_description),
        ];

        $input = 'w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500';
        $label = 'block text-xs font-semibold text-slate-700 mb-1';
        $card  = 'bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-5';
        $err   = 'text-rose-500 text-[11px] mt-1 block';
    @endphp

    <div class="max-w-5xl mx-auto space-y-5">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Site settings</h2>
            <p class="text-xs text-slate-500 mt-1">Branding, contact details, store rules and SEO for the whole site.</p>
        </div>

        <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data"
              x-data="siteSettingsForm({{ \Illuminate\Support\Js::from($cfg) }})" class="space-y-5">
            @csrf
            @method('PUT')

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

            {{-- Tabs --}}
            <div class="flex gap-1 overflow-x-auto border-b border-slate-200" role="tablist">
                @foreach ($tabs as $key => $tab)
                    <button type="button" role="tab" @click="tab = '{{ $key }}'"
                            :aria-selected="tab === '{{ $key }}'"
                            :class="tab === '{{ $key }}' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-800'"
                            class="px-4 py-2.5 text-xs font-semibold border-b-2 -mb-px whitespace-nowrap flex items-center gap-1.5">
                        {{ $tab['label'] }}
                        @if ($errors->hasAny($tab['fields']))
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                        @endif
                    </button>
                @endforeach
            </div>

            {{-- ============ GENERAL ============ --}}
            <section x-show="tab === 'general'" class="{{ $card }}">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="{{ $label }}">Site name *</label>
                        <input type="text" name="site_name" x-model="siteName" class="{{ $input }}">
                        @error('site_name') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="{{ $label }}">Tagline</label>
                        <input type="text" name="site_tagline" x-model="tagline" placeholder="Quality products, delivered fast" class="{{ $input }}">
                        @error('site_tagline') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Logo --}}
                    <div>
                        <label class="{{ $label }}">Logo</label>
                        <div class="flex items-center gap-4">
                            <div class="w-28 h-20 rounded-xl border border-dashed border-slate-300 bg-slate-50 grid place-items-center overflow-hidden">
                                <img x-show="logoPreview" :src="logoPreview" class="max-w-full max-h-full object-contain" alt="Logo preview">
                                <span x-show="!logoPreview" class="text-[11px] text-slate-400">No logo</span>
                            </div>
                            <div class="space-y-2">
                                <input type="file" name="site_logo" accept=".png,.jpg,.jpeg,.webp,.svg" @change="pickLogo($event)"
                                       class="block text-[11px] text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-indigo-600 hover:file:bg-indigo-100">
                                <button type="button" x-show="logoPreview" @click="clearLogo()" class="text-[11px] font-semibold text-rose-600 hover:text-rose-800">Remove logo</button>
                                <p class="text-[10px] text-slate-400">PNG, JPG, WebP or SVG, up to 2 MB.</p>
                            </div>
                        </div>
                        <input type="hidden" name="remove_logo" :value="removeLogo ? 1 : 0">
                        @error('site_logo') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>

                    {{-- Favicon --}}
                    <div>
                        <label class="{{ $label }}">Favicon</label>
                        <div class="flex items-center gap-4">
                            <div class="w-20 h-20 rounded-xl border border-dashed border-slate-300 bg-slate-50 grid place-items-center overflow-hidden">
                                <img x-show="faviconPreview" :src="faviconPreview" class="w-10 h-10 object-contain" alt="Favicon preview">
                                <span x-show="!faviconPreview" class="text-[11px] text-slate-400">None</span>
                            </div>
                            <div class="space-y-2">
                                <input type="file" name="site_favicon" accept=".png,.ico,.svg" @change="pickFavicon($event)"
                                       class="block text-[11px] text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-indigo-600 hover:file:bg-indigo-100">
                                <button type="button" x-show="faviconPreview" @click="clearFavicon()" class="text-[11px] font-semibold text-rose-600 hover:text-rose-800">Remove favicon</button>
                                <p class="text-[10px] text-slate-400">Square PNG, ICO or SVG, up to 1 MB.</p>
                            </div>
                        </div>
                        <input type="hidden" name="remove_favicon" :value="removeFavicon ? 1 : 0">
                        @error('site_favicon') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="{{ $label }}">Footer text</label>
                    <textarea name="footer_text" rows="2" x-model="footerText" placeholder="© {{ date('Y') }} Your Store. All rights reserved." class="{{ $input }}"></textarea>
                    @error('footer_text') <span class="{{ $err }}">{{ $message }}</span> @enderror
                </div>
            </section>

            {{-- ============ THEME ============ --}}
            <section x-show="tab === 'theme'" x-cloak class="grid grid-cols-1 lg:grid-cols-2 gap-5 items-start">
                <div class="{{ $card }}">
                    @foreach ($colors as $key => [$name, $hint])
                        <div>
                            <label class="{{ $label }}">{{ $name }} <span class="font-normal text-slate-400">· {{ $hint }}</span></label>
                            <div class="flex items-center gap-2">
                                <input type="color" x-model="colors.{{ $key }}" class="h-9 w-12 shrink-0 rounded-lg border border-slate-200 p-1 cursor-pointer bg-white" aria-label="{{ $name }} picker">
                                <input type="text" name="{{ $key }}" x-model="colors.{{ $key }}" maxlength="7" placeholder="#000000" class="{{ $input }} font-mono uppercase">
                            </div>
                            @error($key) <span class="{{ $err }}">{{ $message }}</span> @enderror
                        </div>
                    @endforeach
                </div>

                {{-- Live preview --}}
                <div class="lg:sticky lg:top-6">
                    <p class="text-xs font-semibold text-slate-700 mb-2">Live preview</p>
                    <div class="rounded-2xl overflow-hidden border border-slate-200 bg-white text-[11px] shadow-xs" :style="{ color: colors.text_color }">
                        <div class="flex items-center justify-between px-4 py-3 border-b border-black/5" :style="{ background: colors.header_bg_color }">
                            <div class="flex items-center gap-2 min-w-0">
                                <img x-show="logoPreview" :src="logoPreview" class="h-6 object-contain" alt="">
                                <span class="font-bold truncate" x-text="siteName || 'Your store'"></span>
                            </div>
                            <div class="flex gap-3 font-medium shrink-0">
                                <span>Home</span><span>Shop</span><span>Contact</span>
                            </div>
                        </div>

                        <div class="px-5 py-7 space-y-3">
                            <p class="text-base font-bold" :style="{ color: colors.primary_color }" x-text="tagline || 'Your tagline appears here'"></p>
                            <p>Body text uses your main text color so you can check readability against the page.</p>
                            <div class="flex gap-2 pt-1">
                                <span class="px-3 py-1.5 rounded-lg font-semibold text-white" :style="{ background: colors.primary_color }">Add to cart</span>
                                <span class="px-3 py-1.5 rounded-lg font-semibold border" :style="{ color: colors.secondary_color, borderColor: colors.secondary_color }">View details</span>
                            </div>
                        </div>

                        <div class="px-5 py-3 text-white/80" :style="{ background: colors.footer_bg_color }" x-text="footerText || '© {{ date('Y') }} ' + (siteName || 'Your store')"></div>
                    </div>
                </div>
            </section>

            {{-- ============ CONTACT ============ --}}
            <section x-show="tab === 'contact'" x-cloak class="{{ $card }}">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="{{ $label }}">Primary email</label>
                        <input type="email" name="primary_email" value="{{ old('primary_email', $setting->primary_email) }}" placeholder="support@yourstore.com" class="{{ $input }}">
                        @error('primary_email') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="{{ $label }}">Phone number</label>
                        <input type="text" name="phone_number" value="{{ old('phone_number', $setting->phone_number) }}" placeholder="+880 1XXX-XXXXXX" class="{{ $input }}">
                        @error('phone_number') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="{{ $label }}">WhatsApp number</label>
                        <input type="text" name="whatsapp_number" value="{{ old('whatsapp_number', $setting->whatsapp_number) }}" placeholder="+880 1XXX-XXXXXX" class="{{ $input }}">
                        @error('whatsapp_number') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="{{ $label }}">Address</label>
                    <textarea name="address" rows="3" class="{{ $input }}">{{ old('address', $setting->address) }}</textarea>
                    @error('address') <span class="{{ $err }}">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="{{ $label }}">Business hours</label>
                    <input type="text" name="business_hours" value="{{ old('business_hours', $setting->business_hours) }}" placeholder="Sat – Thu, 10:00 AM – 8:00 PM" class="{{ $input }}">
                    @error('business_hours') <span class="{{ $err }}">{{ $message }}</span> @enderror
                </div>
            </section>

            {{-- ============ SOCIAL ============ --}}
            <section x-show="tab === 'social'" x-cloak class="{{ $card }}">
                <p class="text-[11px] text-slate-500">Leave a field empty to hide that icon on the storefront. Use the full link, including https://.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach ($socials as $key => [$name, $placeholder])
                        <div>
                            <label class="{{ $label }}">{{ $name }}</label>
                            <input type="url" name="{{ $key }}" value="{{ old($key, $setting->{$key}) }}" placeholder="{{ $placeholder }}" class="{{ $input }}">
                            @error($key) <span class="{{ $err }}">{{ $message }}</span> @enderror
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- ============ STORE ============ --}}
            <section x-show="tab === 'store'" x-cloak class="{{ $card }}">
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Currency</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-3">
                        <div>
                            <label class="{{ $label }}">Symbol *</label>
                            <input type="text" name="currency_symbol" x-model="symbol" maxlength="10" class="{{ $input }}">
                            @error('currency_symbol') <span class="{{ $err }}">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="{{ $label }}">Code *</label>
                            <input type="text" name="currency_code" value="{{ old('currency_code', $setting->currency_code) }}" maxlength="3" placeholder="BDT" class="{{ $input }} uppercase">
                            @error('currency_code') <span class="{{ $err }}">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="{{ $label }}">Symbol position *</label>
                            <select name="currency_position" x-model="position" class="{{ $input }}">
                                <option value="before">Before the amount</option>
                                <option value="after">After the amount</option>
                            </select>
                            @error('currency_position') <span class="{{ $err }}">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-2">Example: <span class="font-semibold text-slate-800" x-text="moneyExample"></span></p>
                </div>

                <div class="border-t border-slate-100 pt-5">
                    <h3 class="text-sm font-bold text-slate-800">Delivery</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-3">
                        <div>
                            <label class="{{ $label }}">Inside Dhaka charge *</label>
                            <input type="number" step="0.01" min="0" name="delivery_charge_inside_dhaka" value="{{ old('delivery_charge_inside_dhaka', $setting->delivery_charge_inside_dhaka) }}" class="{{ $input }}">
                            @error('delivery_charge_inside_dhaka') <span class="{{ $err }}">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="{{ $label }}">Outside Dhaka charge *</label>
                            <input type="number" step="0.01" min="0" name="delivery_charge_outside_dhaka" value="{{ old('delivery_charge_outside_dhaka', $setting->delivery_charge_outside_dhaka) }}" class="{{ $input }}">
                            @error('delivery_charge_outside_dhaka') <span class="{{ $err }}">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="{{ $label }}">Free delivery above</label>
                            <input type="number" step="0.01" min="0" name="free_shipping_min" value="{{ old('free_shipping_min', $setting->free_shipping_min) }}" placeholder="Leave empty to disable" class="{{ $input }}">
                            @error('free_shipping_min') <span class="{{ $err }}">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
            </section>

            {{-- ============ SEO ============ --}}
            <section x-show="tab === 'seo'" x-cloak class="{{ $card }}">
                <p class="text-[11px] text-slate-500">Default search listing for pages that do not set their own.</p>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <p class="text-[11px] text-slate-500 truncate">{{ rtrim(config('app.url'), '/') }}</p>
                    <p class="text-[15px] leading-snug text-indigo-700 font-medium truncate" x-text="metaTitle || siteName || 'Site title'"></p>
                    <p class="text-xs text-slate-600 line-clamp-2" x-text="metaDescription || 'Add a meta description to control how your site appears in search results.'"></p>
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

                <div>
                    <label class="{{ $label }}">Meta keywords</label>
                    <input type="text" name="meta_keywords" value="{{ old('meta_keywords', $setting->meta_keywords) }}" placeholder="fashion, electronics, online shop" class="{{ $input }}">
                    @error('meta_keywords') <span class="{{ $err }}">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t border-slate-100 pt-5">
                    <div>
                        <label class="{{ $label }}">Google Analytics ID</label>
                        <input type="text" name="google_analytics_id" value="{{ old('google_analytics_id', $setting->google_analytics_id) }}" placeholder="G-XXXXXXXXXX" class="{{ $input }}">
                        @error('google_analytics_id') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="{{ $label }}">Facebook Pixel ID</label>
                        <input type="text" name="facebook_pixel_id" value="{{ old('facebook_pixel_id', $setting->facebook_pixel_id) }}" placeholder="1234567890" class="{{ $input }}">
                        @error('facebook_pixel_id') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                </div>
            </section>

            {{-- Save bar --}}
            <div class="flex justify-end">
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white font-semibold text-xs rounded-xl shadow-lg shadow-indigo-600/30 hover:bg-indigo-700 transition-all">
                    Save settings
                </button>
            </div>
        </form>
    </div>

    <script>
        function siteSettingsForm(cfg) {
            return {
                tab: cfg.tab,
                colors: cfg.colors,
                siteName: cfg.siteName || '',
                tagline: cfg.tagline || '',
                footerText: cfg.footerText || '',
                symbol: cfg.symbol || '',
                position: cfg.position || 'before',
                metaTitle: cfg.metaTitle || '',
                metaDescription: cfg.metaDescription || '',

                logoPreview: cfg.logoUrl,
                faviconPreview: cfg.faviconUrl,
                removeLogo: false,
                removeFavicon: false,

                get moneyExample() {
                    return this.position === 'after' ? `1,250.00 ${this.symbol}` : `${this.symbol}1,250.00`;
                },

                pickLogo(e) {
                    const f = e.target.files[0];
                    if (!f) return;
                    this.logoPreview = URL.createObjectURL(f);
                    this.removeLogo = false;
                },
                clearLogo() {
                    document.querySelector('input[name=site_logo]').value = '';
                    this.logoPreview = null;
                    this.removeLogo = true;
                },
                pickFavicon(e) {
                    const f = e.target.files[0];
                    if (!f) return;
                    this.faviconPreview = URL.createObjectURL(f);
                    this.removeFavicon = false;
                },
                clearFavicon() {
                    document.querySelector('input[name=site_favicon]').value = '';
                    this.faviconPreview = null;
                    this.removeFavicon = true;
                },
            };
        }
    </script>
@endsection

{{--
    Shared form for create + edit.
    Expects: $action, $method ('POST' | 'PUT'), $coupon (null on create), $categories, $brands, $products
--}}
@php
    $isEdit = ! is_null($coupon);
    $hasOld = session()->hasOldInput();
    $money  = \App\Models\SiteSetting::current();

    $dt = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('Y-m-d\TH:i') : '';

    $ids = fn ($relation, $field) => array_map('strval', (array) old(
        $field,
        $isEdit ? $coupon->{$relation}->pluck('id')->all() : []
    ));

    $items = [
        'categories' => $categories->map(fn ($c) => ['id' => (string) $c->id, 'name' => $c->name])->values()->all(),
        'brands'     => $brands->map(fn ($b) => ['id' => (string) $b->id, 'name' => $b->name])->values()->all(),
        'products'   => $products->map(fn ($p) => ['id' => (string) $p->id, 'name' => $p->name . ' (' . $p->sku . ')'])->values()->all(),
    ];

    $cfg = [
        'code'       => old('code', $coupon->code ?? ''),
        'type'       => old('type', $coupon->type ?? 'percentage'),
        'value'      => old('value', $coupon->value ?? ''),
        'maxDiscount'=> old('max_discount_amount', $coupon->max_discount_amount ?? ''),
        'minOrder'   => old('min_order_amount', $coupon->min_order_amount ?? ''),
        'appliesTo'  => old('applies_to', $coupon->applies_to ?? 'all'),
        'firstOrder' => $hasOld ? (bool) old('first_order_only') : ($coupon->first_order_only ?? false),
        'startsAt'   => old('starts_at', $dt($coupon->starts_at ?? null)),
        'expiresAt'  => old('expires_at', $dt($coupon->expires_at ?? null)),
        'items'      => $items,
        'selected'   => [
            'categories' => $ids('categories', 'categories'),
            'brands'     => $ids('brands', 'brands'),
            'products'   => $ids('products', 'products'),
        ],
        'symbol'     => $money->currency_symbol,
        'position'   => $money->currency_position,
    ];

    $isActive = $hasOld ? (bool) old('is_active') : ($coupon->is_active ?? true);
    $excludeDiscounted = $hasOld ? (bool) old('exclude_discounted') : ($coupon->exclude_discounted ?? false);

    $targetMeta = [
        'categories' => ['Categories', 'Choose the categories this coupon works on.'],
        'brands'     => ['Brands', 'Choose the brands this coupon works on.'],
        'products'   => ['Products', 'Choose the products this coupon works on.'],
    ];

    $input = 'w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500';
    $label = 'block text-xs font-semibold text-slate-700 mb-1';
    $card  = 'bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-4';
    $err   = 'text-rose-500 text-[11px] mt-1 block';
    $toggle = "w-9 h-5 bg-slate-200 rounded-full peer-checked:bg-indigo-600 peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-400 transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-4";
    $toggleGreen = str_replace('peer-checked:bg-indigo-600', 'peer-checked:bg-emerald-500', $toggle);
    $choice = 'block p-3 rounded-xl border border-slate-200 bg-white text-left transition-colors hover:border-indigo-200 peer-checked:border-indigo-500 peer-checked:bg-indigo-50/60 peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-400';
@endphp

<form action="{{ $action }}" method="POST" x-data="couponForm({{ \Illuminate\Support\Js::from($cfg) }})" class="space-y-4">
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

            {{-- Code --}}
            <section class="{{ $card }}">
                <h3 class="text-sm font-bold text-slate-800">Coupon details</h3>

                <div>
                    <label class="{{ $label }}">Coupon code *</label>
                    <div class="flex gap-2">
                        <input type="text" name="code" x-model="code" maxlength="50" placeholder="SAVE20"
                               @input="code = code.toUpperCase().replace(/[^A-Z0-9_-]/g, '')"
                               class="{{ $input }} font-mono uppercase tracking-wide" autocomplete="off">
                        <button type="button" @click="generateCode()" class="px-3 py-2 text-xs font-semibold text-indigo-600 bg-indigo-50 rounded-xl hover:bg-indigo-100 shrink-0">Generate</button>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1">Customers type this at checkout. Letters, numbers, dash and underscore only.</p>
                    @error('code') <span class="{{ $err }}">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="{{ $label }}">Internal name</label>
                        <input type="text" name="name" value="{{ old('name', $coupon->name ?? '') }}" maxlength="100" placeholder="Eid sale 2026" class="{{ $input }}">
                        <p class="text-[10px] text-slate-400 mt-1">Only you see this.</p>
                        @error('name') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="{{ $label }}">Description</label>
                        <input type="text" name="description" value="{{ old('description', $coupon->description ?? '') }}" maxlength="500" placeholder="20% off on all shoes" class="{{ $input }}">
                        <p class="text-[10px] text-slate-400 mt-1">Can be shown to customers.</p>
                        @error('description') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                </div>
            </section>

            {{-- Discount --}}
            <section class="{{ $card }}">
                <h3 class="text-sm font-bold text-slate-800">Discount</h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5" role="radiogroup" aria-label="Discount type">
                    <label class="cursor-pointer">
                        <input type="radio" name="type" value="percentage" x-model="type" class="sr-only peer">
                        <span class="{{ $choice }}">
                            <span class="block text-xs font-bold text-slate-800">Percentage</span>
                            <span class="block text-[11px] text-slate-500 mt-0.5">Take a % off</span>
                        </span>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="type" value="fixed" x-model="type" class="sr-only peer">
                        <span class="{{ $choice }}">
                            <span class="block text-xs font-bold text-slate-800">Fixed amount</span>
                            <span class="block text-[11px] text-slate-500 mt-0.5">Take a set amount off</span>
                        </span>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="type" value="free_shipping" x-model="type" class="sr-only peer">
                        <span class="{{ $choice }}">
                            <span class="block text-xs font-bold text-slate-800">Free delivery</span>
                            <span class="block text-[11px] text-slate-500 mt-0.5">Waive the delivery charge</span>
                        </span>
                    </label>
                </div>
                @error('type') <span class="{{ $err }}">{{ $message }}</span> @enderror

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div x-show="type !== 'free_shipping'" x-cloak>
                        <label class="{{ $label }}">
                            <span x-text="type === 'percentage' ? 'Percentage off *' : 'Amount off *'"></span>
                        </label>
                        <div class="relative">
                            <input type="number" name="value" x-model="value" step="0.01" min="0.01" :max="type === 'percentage' ? 100 : null"
                                   :disabled="type === 'free_shipping'" class="{{ $input }} pr-9">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400" x-text="type === 'percentage' ? '%' : symbol"></span>
                        </div>
                        @error('value') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>

                    <div x-show="type === 'percentage'" x-cloak>
                        <label class="{{ $label }}">Maximum discount</label>
                        <div class="relative">
                            <input type="number" name="max_discount_amount" x-model="maxDiscount" step="0.01" min="0.01" placeholder="No cap"
                                   :disabled="type !== 'percentage'" class="{{ $input }} pr-9">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400" x-text="symbol"></span>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1">Caps how much a % coupon can take off.</p>
                        @error('max_discount_amount') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="{{ $label }}">Minimum order</label>
                        <div class="relative">
                            <input type="number" name="min_order_amount" x-model="minOrder" step="0.01" min="0" placeholder="No minimum" class="{{ $input }} pr-9">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400" x-text="symbol"></span>
                        </div>
                        @error('min_order_amount') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                </div>
            </section>

            {{-- Applies to --}}
            <section class="{{ $card }}">
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Applies to</h3>
                    <p class="text-[11px] text-slate-500">Which items in the cart can the coupon take money off.</p>
                </div>

                <select name="applies_to" x-model="appliesTo" class="{{ $input }}">
                    @foreach (\App\Models\Coupon::APPLIES_TO as $value => $name)
                        <option value="{{ $value }}">{{ $name }}</option>
                    @endforeach
                </select>
                @error('applies_to') <span class="{{ $err }}">{{ $message }}</span> @enderror

                @foreach ($targetMeta as $key => [$title, $hint])
                    <div x-show="appliesTo === '{{ $key }}'" x-cloak class="space-y-2">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-[11px] text-slate-500">{{ $hint }}</p>
                            <p class="text-[11px] text-slate-500 shrink-0"><span class="font-bold text-slate-800" x-text="selected.{{ $key }}.length"></span> selected</p>
                        </div>

                        @if (empty($items[$key]))
                            <p class="text-xs text-slate-400 py-4 text-center border border-dashed border-slate-200 rounded-xl">No {{ strtolower($title) }} found.</p>
                        @else
                            <div class="flex gap-2">
                                <div class="relative flex-1">
                                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                    <input type="search" x-model="search.{{ $key }}" placeholder="Search {{ strtolower($title) }}" class="{{ $input }} pl-9">
                                </div>
                                <button type="button" @click="toggleShown('{{ $key }}')" class="px-3 py-2 text-xs font-semibold text-indigo-600 bg-indigo-50 rounded-xl hover:bg-indigo-100 shrink-0"
                                        x-text="allShownSelected('{{ $key }}') ? 'Clear shown' : 'Select shown'"></button>
                            </div>

                            <div class="max-h-60 overflow-y-auto rounded-xl border border-slate-200 divide-y divide-slate-100">
                                @foreach ($items[$key] as $item)
                                    <label x-show="matches('{{ $key }}', {{ \Illuminate\Support\Js::from($item['name']) }})"
                                           class="flex items-center gap-3 px-3 py-2 text-xs cursor-pointer hover:bg-slate-50">
                                        <input type="checkbox" name="{{ $key }}[]" value="{{ $item['id'] }}" x-model="selected.{{ $key }}"
                                               :disabled="appliesTo !== '{{ $key }}'" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                        <span class="text-slate-700 font-medium">{{ $item['name'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @if ($key === 'products' && count($items['products']) >= 500)
                                <p class="text-[10px] text-slate-400">Showing the first 500 products. Use categories or brands to cover the rest.</p>
                            @endif
                        @endif

                        @error($key) <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                @endforeach

                <label class="flex items-center justify-between cursor-pointer pt-1">
                    <span>
                        <span class="block text-xs font-semibold text-slate-700">Skip items that already have a discount price</span>
                        <span class="block text-[11px] text-slate-500">Stops the coupon stacking on top of sale prices.</span>
                    </span>
                    <span class="relative inline-flex items-center shrink-0">
                        <input type="checkbox" name="exclude_discounted" value="1" @checked($excludeDiscounted) class="sr-only peer">
                        <span class="{{ $toggle }}"></span>
                    </span>
                </label>
            </section>

            {{-- Limits --}}
            <section class="{{ $card }}">
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Usage limits</h3>
                    <p class="text-[11px] text-slate-500">Leave a limit empty for unlimited.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="{{ $label }}">Total uses</label>
                        <input type="number" name="usage_limit" min="1" value="{{ old('usage_limit', $coupon->usage_limit ?? '') }}" placeholder="Unlimited" class="{{ $input }}">
                        <p class="text-[10px] text-slate-400 mt-1">The coupon switches off after this many orders.</p>
                        @error('usage_limit') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="{{ $label }}">Uses per customer</label>
                        <input type="number" name="usage_limit_per_user" min="1" value="{{ old('usage_limit_per_user', $coupon->usage_limit_per_user ?? '') }}" placeholder="Unlimited" class="{{ $input }}">
                        <p class="text-[10px] text-slate-400 mt-1">Customers must be logged in to use it.</p>
                        @error('usage_limit_per_user') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                </div>

                <label class="flex items-center justify-between cursor-pointer">
                    <span>
                        <span class="block text-xs font-semibold text-slate-700">First order only</span>
                        <span class="block text-[11px] text-slate-500">Only customers who have never ordered before.</span>
                    </span>
                    <span class="relative inline-flex items-center shrink-0">
                        <input type="checkbox" name="first_order_only" value="1" x-model="firstOrder" class="sr-only peer">
                        <span class="{{ $toggle }}"></span>
                    </span>
                </label>
            </section>

            {{-- Schedule --}}
            <section class="{{ $card }}">
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Schedule</h3>
                    <p class="text-[11px] text-slate-500">Optional. The coupon works only between these times.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="{{ $label }}">Starts at</label>
                        <input type="datetime-local" name="starts_at" x-model="startsAt" class="{{ $input }}">
                        @error('starts_at') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="{{ $label }}">Expires at</label>
                        <input type="datetime-local" name="expires_at" x-model="expiresAt" class="{{ $input }}">
                        @error('expires_at') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                </div>
            </section>
        </div>

        {{-- ======================= SIDEBAR ======================= --}}
        <aside class="space-y-4 lg:sticky lg:top-6">

            <section class="{{ $card }}">
                <h3 class="text-sm font-bold text-slate-800">Status</h3>

                <label class="flex items-center justify-between cursor-pointer">
                    <span>
                        <span class="block text-xs font-medium text-slate-700">Active</span>
                        <span class="block text-[11px] text-slate-500">Turn off to pause it without deleting.</span>
                    </span>
                    <span class="relative inline-flex items-center shrink-0">
                        <input type="checkbox" name="is_active" value="1" @checked($isActive) class="sr-only peer">
                        <span class="{{ $toggleGreen }}"></span>
                    </span>
                </label>

                <div class="pt-2 flex gap-2">
                    <a href="{{ route('admin.coupons.index') }}" class="flex-1 text-center px-4 py-2.5 border border-slate-200 text-slate-600 font-semibold text-xs rounded-xl hover:bg-slate-50">Cancel</a>
                    <button type="submit" class="flex-1 px-4 py-2.5 bg-indigo-600 text-white font-semibold text-xs rounded-xl shadow-lg shadow-indigo-600/30 hover:bg-indigo-700 transition-all">
                        {{ $isEdit ? 'Save changes' : 'Save coupon' }}
                    </button>
                </div>
            </section>

            {{-- Live preview --}}
            <section class="{{ $card }}">
                <h3 class="text-sm font-bold text-slate-800">Preview</h3>

                <div class="rounded-xl border-2 border-dashed border-indigo-300 bg-indigo-50/40 p-4 text-center space-y-1.5">
                    <p class="text-lg font-extrabold text-indigo-700 leading-tight" x-text="headline"></p>
                    <p class="inline-block px-3 py-1 rounded-lg bg-white border border-indigo-200 font-mono text-sm font-bold tracking-widest text-slate-800" x-text="code || 'YOURCODE'"></p>
                    <ul class="text-[11px] text-slate-600 space-y-0.5 pt-1">
                        <template x-for="line in details" :key="line">
                            <li x-text="line"></li>
                        </template>
                    </ul>
                </div>
                <p class="text-[10px] text-slate-400">Approximate. Shows how the offer reads to customers.</p>
            </section>

            @if ($isEdit)
                <section class="{{ $card }}">
                    <h3 class="text-sm font-bold text-slate-800">Results</h3>
                    <dl class="text-xs space-y-2">
                        <div class="flex justify-between gap-3"><dt class="text-slate-400">Times used</dt><dd class="font-semibold text-slate-800">{{ $coupon->used_count }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-400">Discount given</dt><dd class="font-semibold text-slate-800">{{ $money->formatMoney($coupon->total_discount ?? 0) }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-400">Created</dt><dd class="font-semibold text-slate-800">{{ $coupon->created_at?->format('d M Y') }}</dd></div>
                    </dl>
                </section>
            @endif
        </aside>
    </div>
</form>

<script>
    function couponForm(cfg) {
        return {
            code: cfg.code || '',
            type: cfg.type,
            value: cfg.value,
            maxDiscount: cfg.maxDiscount,
            minOrder: cfg.minOrder,
            appliesTo: cfg.appliesTo,
            firstOrder: cfg.firstOrder,
            startsAt: cfg.startsAt,
            expiresAt: cfg.expiresAt,
            symbol: cfg.symbol,

            selected: cfg.selected,
            search: { categories: '', brands: '', products: '' },

            /* ---------- code ---------- */
            generateCode() {
                // no 0/O or 1/I so codes are easy to read out
                const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
                const pick = () => chars[crypto.getRandomValues(new Uint32Array(1))[0] % chars.length];
                this.code = 'SAVE' + Array.from({ length: 6 }, pick).join('');
            },

            /* ---------- targets ---------- */
            matches(key, name) {
                const q = this.search[key].trim().toLowerCase();
                return !q || name.toLowerCase().includes(q);
            },
            shown(key) {
                return cfg.items[key].filter(i => this.matches(key, i.name)).map(i => i.id);
            },
            allShownSelected(key) {
                const ids = this.shown(key);
                return ids.length > 0 && ids.every(id => this.selected[key].includes(id));
            },
            toggleShown(key) {
                const ids = this.shown(key);
                this.selected[key] = this.allShownSelected(key)
                    ? this.selected[key].filter(id => !ids.includes(id))
                    : Array.from(new Set([...this.selected[key], ...ids]));
            },

            /* ---------- preview ---------- */
            money(n) {
                const num = Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
                return cfg.position === 'after' ? `${num} ${cfg.symbol}` : `${cfg.symbol}${num}`;
            },
            date(v) {
                return new Date(v).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
            },
            get headline() {
                if (this.type === 'free_shipping') return 'FREE DELIVERY';
                if (!Number(this.value)) return this.type === 'percentage' ? '% OFF' : 'AMOUNT OFF';
                return this.type === 'percentage' ? `${Number(this.value)}% OFF` : `${this.money(this.value)} OFF`;
            },
            get details() {
                const lines = [];
                if (this.type === 'percentage' && Number(this.maxDiscount)) lines.push(`Up to ${this.money(this.maxDiscount)} off`);
                if (Number(this.minOrder)) lines.push(`Min. order ${this.money(this.minOrder)}`);
                if (this.appliesTo !== 'all') {
                    const n = this.selected[this.appliesTo].length;
                    lines.push(n ? `On ${n} selected ${this.appliesTo}` : `On selected ${this.appliesTo}`);
                }
                if (this.firstOrder) lines.push('First order only');
                if (this.expiresAt) lines.push(`Valid until ${this.date(this.expiresAt)}`);
                return lines.length ? lines : ['No conditions'];
            },
        };
    }
</script>

@extends('layouts.admin')

@section('title', 'New Order')

@section('content')
    @php
        $form = [
            'user_id'            => old('user_id'),
            'customer_name'      => old('customer_name', ''),
            'customer_phone'     => old('customer_phone', ''),
            'customer_email'     => old('customer_email', ''),
            'shipping_name'      => old('shipping_name', ''),
            'shipping_phone'     => old('shipping_phone', ''),
            'shipping_city'      => old('shipping_city', ''),
            'shipping_area'      => old('shipping_area', ''),
            'shipping_address'   => old('shipping_address', ''),
            'delivery_zone'      => old('delivery_zone', 'inside_dhaka'),
            'payment_gateway_id' => old('payment_gateway_id', $gateways->first()?->id),
            'coupon_code'        => old('coupon_code', ''),
        ];

        $cfg = [
            'form'      => $form,
            'lines'     => $oldLines,
            'customer'  => $oldCustomer,
            'lookupUrl' => route('admin.orders.customer-lookup'),
            'customerSearchUrl' => route('admin.orders.customers.search'),
            'searchUrl' => route('admin.orders.products.search'),
            'charges'   => [
                'inside_dhaka'  => (float) $settings->delivery_charge_inside_dhaka,
                'outside_dhaka' => (float) $settings->delivery_charge_outside_dhaka,
            ],
            'freeMin'   => $settings->free_shipping_min !== null ? (float) $settings->free_shipping_min : null,
            'symbol'    => $settings->currency_symbol,
            'position'  => $settings->currency_position,
        ];

        $input = 'w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500';
        $label = 'block text-xs font-semibold text-slate-700 mb-1';
        $card  = 'bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-4';
        $err   = 'text-rose-500 text-[11px] mt-1 block';
    @endphp

    <div class="max-w-6xl mx-auto space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-800">New order</h2>
                <p class="text-xs text-slate-500 mt-1">For phone and message orders. Stock, coupon, delivery charge and fees are worked out for you.</p>
            </div>
            <a href="{{ route('admin.orders.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">&larr; Back to orders</a>
        </div>

        <form action="{{ route('admin.orders.store') }}" method="POST" x-data="orderForm({{ \Illuminate\Support\Js::from($cfg) }})" class="space-y-4">
            @csrf
            <input type="hidden" name="user_id" :value="form.user_id || ''">

            @if ($errors->any())
                <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4">
                    <p class="text-xs font-bold text-rose-700">Please fix the following before saving:</p>
                    <ul class="mt-2 list-disc pl-5 text-[11px] text-rose-600 space-y-0.5">
                        @foreach ($errors->all() as $message) <li>{{ $message }}</li> @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-start">

                {{-- ======================= LEFT ======================= --}}
                <div class="lg:col-span-2 space-y-4">

                    {{-- Customer --}}
                    <section class="{{ $card }}">
                        <h3 class="text-sm font-bold text-slate-800">Customer</h3>

                        {{-- Pick an existing customer --}}
                        <div x-show="!customer" class="relative" @click.outside="custResults = []">
                            <label class="{{ $label }}">Find an existing customer</label>
                            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-[34px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input type="search" x-model="custSearch" @input.debounce.300ms="searchCustomers()" placeholder="Search by name, phone or email" class="{{ $input }} pl-9" autocomplete="off">

                            <div x-show="custResults.length > 0" x-cloak class="absolute z-20 mt-1 w-full bg-white rounded-xl border border-slate-200 shadow-lg max-h-72 overflow-y-auto divide-y divide-slate-100">
                                <template x-for="c in custResults" :key="c.user_id">
                                    <button type="button" @click="selectCustomer(c)" class="w-full flex items-center gap-3 px-3 py-2.5 text-left hover:bg-slate-50">
                                        <span class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 grid place-items-center font-bold uppercase shrink-0" x-text="c.name.charAt(0)"></span>
                                        <span class="min-w-0 flex-1">
                                        <span class="block text-xs font-semibold text-slate-800 truncate" x-text="c.name"></span>
                                        <span class="block text-[11px] text-slate-400 truncate" x-text="[c.phone, c.email].filter(Boolean).join(' · ')"></span>
                                    </span>
                                        <span class="text-right shrink-0">
                                        <span class="block text-[11px] font-semibold text-slate-600" x-text="c.orders_count + (c.orders_count === 1 ? ' order' : ' orders')"></span>
                                        <span class="block text-[10px] text-slate-400" x-text="c.addresses.length + (c.addresses.length === 1 ? ' address' : ' addresses')"></span>
                                    </span>
                                    </button>
                                </template>
                            </div>

                            <p x-show="custSearched && custResults.length === 0 && custSearch.trim().length >= 2" x-cloak class="text-[11px] text-slate-400 mt-2">
                                No customer matches "<span x-text="custSearch"></span>". Fill in the details below to order for a new customer.
                            </p>
                        </div>

                        {{-- The customer that was picked --}}
                        <div x-show="customer" x-cloak class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-3">
                            <span class="w-10 h-10 rounded-xl bg-white text-emerald-600 border border-emerald-200 grid place-items-center font-bold uppercase shrink-0" x-text="customer ? customer.name.charAt(0) : ''"></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-bold text-emerald-900 truncate" x-text="customer ? customer.name : ''"></p>
                                <p class="text-[11px] text-emerald-700 truncate" x-text="customer ? [customer.phone, customer.email].filter(Boolean).join(' · ') : ''"></p>
                                <p class="text-[11px] text-emerald-700" x-text="customer ? (customer.orders_count === 0 ? 'No previous orders' : customer.orders_count + (customer.orders_count === 1 ? ' previous order' : ' previous orders')) : ''"></p>
                            </div>
                            <button type="button" @click="clearCustomer()" class="text-[11px] font-semibold text-emerald-800 hover:text-emerald-950 shrink-0">Change</button>
                        </div>

                        <div class="flex items-center gap-3" x-show="!customer">
                            <span class="h-px flex-1 bg-slate-200"></span>
                            <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">or enter the details</span>
                            <span class="h-px flex-1 bg-slate-200"></span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="{{ $label }}">Phone *</label>
                                <input type="text" name="customer_phone" x-model="form.customer_phone" @blur="lookup()" maxlength="20" placeholder="01XXXXXXXXX" class="{{ $input }}" autocomplete="off">
                                @error('customer_phone') <span class="{{ $err }}">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="{{ $label }}">Name *</label>
                                <input type="text" name="customer_name" x-model="form.customer_name" maxlength="100" class="{{ $input }}" autocomplete="off">
                                @error('customer_name') <span class="{{ $err }}">{{ $message }}</span> @enderror
                            </div>
                            <div class="md:col-span-2">
                                <label class="{{ $label }}">Email <span class="font-normal text-slate-400">(optional)</span></label>
                                <input type="email" name="customer_email" x-model="form.customer_email" maxlength="100" class="{{ $input }}" autocomplete="off">
                                @error('customer_email') <span class="{{ $err }}">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <p x-show="customer" x-cloak class="text-[10px] text-slate-400 -mt-2">These details are copied onto this order only. The customer's account is not changed.</p>
                    </section>

                    {{-- Delivery --}}
                    <section class="{{ $card }}">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-sm font-bold text-slate-800">Delivery address</h3>
                            <button type="button" @click="copyContact()" class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-800">Copy name and phone from customer</button>
                        </div>

                        {{-- Saved addresses of the picked customer --}}
                        <div x-show="customer && customer.addresses.length > 0" x-cloak>
                            <p class="text-[11px] font-semibold text-slate-500 mb-2">Saved addresses. Click one to use it.</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <template x-for="a in (customer ? customer.addresses : [])" :key="a.id">
                                    <button type="button" @click="applyAddress(a)"
                                            class="text-left p-3 rounded-xl border transition-colors"
                                            :class="selectedAddressId === a.id ? 'border-indigo-500 bg-indigo-50/60' : 'border-slate-200 bg-white hover:border-indigo-200'">
                                    <span class="flex items-center gap-1.5">
                                        <span class="text-xs font-bold text-slate-800 truncate" x-text="a.name"></span>
                                        <span x-show="a.is_default" class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-indigo-600 text-white">Default</span>
                                    </span>
                                        <span class="block text-[11px] text-slate-500 mt-0.5 line-clamp-2" x-text="`${a.address_line}, ${a.area}, ${a.city}`"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="{{ $label }}">Receiver name *</label>
                                <input type="text" name="shipping_name" x-model="form.shipping_name" maxlength="100" class="{{ $input }}">
                                @error('shipping_name') <span class="{{ $err }}">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="{{ $label }}">Receiver phone *</label>
                                <input type="text" name="shipping_phone" x-model="form.shipping_phone" maxlength="20" class="{{ $input }}">
                                @error('shipping_phone') <span class="{{ $err }}">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="{{ $label }}">City / District *</label>
                                <input type="text" name="shipping_city" x-model="form.shipping_city" @input="guessZone()" maxlength="100" placeholder="Chattogram" class="{{ $input }}">
                                @error('shipping_city') <span class="{{ $err }}">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="{{ $label }}">Area / Thana *</label>
                                <input type="text" name="shipping_area" x-model="form.shipping_area" maxlength="100" placeholder="Pahartali" class="{{ $input }}">
                                @error('shipping_area') <span class="{{ $err }}">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="{{ $label }}">Full address *</label>
                            <textarea name="shipping_address" x-model="form.shipping_address" rows="2" maxlength="1000" placeholder="House, road, landmark" class="{{ $input }}"></textarea>
                            @error('shipping_address') <span class="{{ $err }}">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="{{ $label }}">Delivery area *</label>
                            <div class="grid grid-cols-2 gap-2.5">
                                @foreach (\App\Models\Order::ZONES as $value => $name)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="delivery_zone" value="{{ $value }}" x-model="form.delivery_zone" @change="zoneTouched = true" class="sr-only peer">
                                        <span class="flex items-center justify-between p-3 rounded-xl border border-slate-200 bg-white transition-colors hover:border-indigo-200 peer-checked:border-indigo-500 peer-checked:bg-indigo-50/60 peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-400">
                                        <span class="text-xs font-bold text-slate-800">{{ $name }}</span>
                                        <span class="text-[11px] text-slate-500" x-text="money(charges['{{ $value }}'])"></span>
                                    </span>
                                    </label>
                                @endforeach
                            </div>
                            @error('delivery_zone') <span class="{{ $err }}">{{ $message }}</span> @enderror
                        </div>
                    </section>

                    {{-- Products --}}
                    <section class="{{ $card }}">
                        <h3 class="text-sm font-bold text-slate-800">Products</h3>

                        <div class="relative" @click.outside="results = []">
                            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input type="search" x-model="search" @input.debounce.300ms="runSearch()" placeholder="Search products by name or SKU" class="{{ $input }} pl-9" autocomplete="off">

                            <div x-show="results.length > 0" x-cloak class="absolute z-20 mt-1 w-full bg-white rounded-xl border border-slate-200 shadow-lg max-h-72 overflow-y-auto divide-y divide-slate-100">
                                <template x-for="p in results" :key="p.product_id">
                                    <button type="button" @click="addProduct(p)" class="w-full flex items-center gap-3 px-3 py-2 text-left hover:bg-slate-50">
                                        <img :src="p.image" class="w-10 h-10 rounded-lg object-cover border border-slate-200 shrink-0" alt="">
                                        <span class="min-w-0 flex-1">
                                        <span class="block text-xs font-semibold text-slate-800 truncate" x-text="p.name"></span>
                                        <span class="block text-[10px] font-mono text-slate-400" x-text="p.sku"></span>
                                    </span>
                                        <span class="text-right shrink-0">
                                        <span class="block text-xs font-bold text-slate-800" x-text="p.has_variants ? 'Options' : money(p.price)"></span>
                                        <span class="block text-[10px]" :class="(p.has_variants ? p.variants.some(v => v.stock > 0) : p.stock > 0) ? 'text-slate-400' : 'text-rose-500'"
                                              x-text="p.has_variants ? `${p.variants.length} variants` : (p.stock > 0 ? `${p.stock} in stock` : 'Out of stock')"></span>
                                    </span>
                                    </button>
                                </template>
                            </div>
                            <p x-show="searched && results.length === 0 && search.length >= 2" x-cloak class="text-[11px] text-slate-400 mt-2">No active product matches "<span x-text="search"></span>".</p>
                        </div>

                        <p x-show="lines.length === 0" class="text-xs text-slate-400 text-center py-6 border border-dashed border-slate-200 rounded-xl">Search above to add the first product.</p>

                        <div class="space-y-2.5">
                            <template x-for="(line, index) in lines" :key="line.key">
                                <div class="rounded-xl border bg-slate-50/60 p-3" :class="overStock(line) ? 'border-rose-300' : 'border-slate-200'">
                                    <input type="hidden" :name="`items[${index}][product_id]`" :value="line.product_id">
                                    <input type="hidden" :name="`items[${index}][variant_id]`" :value="line.variant_id || ''">

                                    <div class="flex items-start gap-3">
                                        <img :src="line.image" class="w-12 h-12 rounded-lg object-cover border border-slate-200 shrink-0" alt="">

                                        <div class="min-w-0 flex-1 space-y-2">
                                            <div>
                                                <p class="text-xs font-bold text-slate-800" x-text="line.name"></p>
                                                <p class="text-[10px] font-mono text-slate-400" x-text="line.sku"></p>
                                            </div>

                                            <select x-show="line.has_variants" x-model="line.variant_id" class="w-full sm:w-64 text-xs rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
                                                <option value="">Choose an option</option>
                                                <template x-for="v in line.variants" :key="v.id">
                                                    <option :value="v.id" :disabled="v.stock < 1" x-text="`${v.label} (${money(v.price)}${v.stock < 1 ? ', out of stock' : ''})`"></option>
                                                </template>
                                            </select>

                                            <p x-show="overStock(line)" class="text-[11px] font-semibold text-rose-600" x-text="`Only ${stock(line)} in stock`"></p>
                                        </div>

                                        <div class="text-right shrink-0 space-y-2">
                                            <input type="number" :name="`items[${index}][quantity]`" x-model.number="line.quantity" min="1" max="999"
                                                   class="w-20 text-xs rounded-lg border-slate-200 text-right focus:border-indigo-500 focus:ring-indigo-500" aria-label="Quantity">
                                            <p class="text-xs font-bold text-slate-800" x-text="money(unit(line) * (line.quantity || 0))"></p>
                                        </div>

                                        <button type="button" @click="removeLine(index)" class="p-1 text-slate-400 hover:text-rose-600 shrink-0" aria-label="Remove product">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                        @error('items') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </section>
                </div>

                {{-- ======================= RIGHT ======================= --}}
                <aside class="space-y-4 lg:sticky lg:top-6">

                    {{-- Payment --}}
                    <section class="{{ $card }}">
                        <h3 class="text-sm font-bold text-slate-800">Payment method *</h3>

                        @forelse ($gateways as $gateway)
                            <label class="cursor-pointer block">
                                <input type="radio" name="payment_gateway_id" value="{{ $gateway->id }}" x-model="form.payment_gateway_id" class="sr-only peer">
                                <span class="flex items-center justify-between gap-3 p-3 rounded-xl border border-slate-200 bg-white transition-colors hover:border-indigo-200 peer-checked:border-indigo-500 peer-checked:bg-indigo-50/60 peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-400">
                                <span class="text-xs font-bold text-slate-800">{{ $gateway->name }}</span>
                                <span class="text-[11px] text-slate-500">{{ $gateway->fee_label }}</span>
                            </span>
                            </label>
                        @empty
                            <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-[11px] text-amber-800">
                                No payment method is switched on. <a href="{{ route('admin.payment-gateways.index') }}" class="font-semibold underline">Set one up</a> first.
                            </div>
                        @endforelse
                        @error('payment_gateway_id') <span class="{{ $err }}">{{ $message }}</span> @enderror

                        <div>
                            <label class="{{ $label }}">Coupon code</label>
                            <input type="text" name="coupon_code" x-model="form.coupon_code" @input="form.coupon_code = form.coupon_code.toUpperCase().replace(/\s+/g, '')"
                                   maxlength="50" placeholder="Optional" class="{{ $input }} font-mono uppercase">
                            <p class="text-[10px] text-slate-400 mt-1">Checked when you save. A wrong or unusable code stops the order with a message.</p>
                            @error('coupon_code') <span class="{{ $err }}">{{ $message }}</span> @enderror
                        </div>
                    </section>

                    {{-- Notes --}}
                    <section class="{{ $card }}">
                        <h3 class="text-sm font-bold text-slate-800">Notes</h3>
                        <div>
                            <label class="{{ $label }}">Customer note</label>
                            <textarea name="customer_note" rows="2" maxlength="1000" placeholder="Anything the customer asked for" class="{{ $input }}">{{ old('customer_note') }}</textarea>
                        </div>
                        <div>
                            <label class="{{ $label }}">Internal note</label>
                            <textarea name="admin_note" rows="2" maxlength="2000" placeholder="Only your team sees this" class="{{ $input }}">{{ old('admin_note') }}</textarea>
                        </div>
                    </section>

                    {{-- Summary --}}
                    <section class="{{ $card }}">
                        <h3 class="text-sm font-bold text-slate-800">Summary</h3>

                        <dl class="text-xs space-y-2">
                            <div class="flex justify-between"><dt class="text-slate-500" x-text="`Subtotal (${itemCount} items)`"></dt><dd class="font-semibold text-slate-800" x-text="money(subtotal)"></dd></div>
                            <div class="flex justify-between">
                                <dt class="text-slate-500">Delivery</dt>
                                <dd class="font-semibold text-slate-800" x-text="shipping === 0 && subtotal > 0 ? 'Free' : money(shipping)"></dd>
                            </div>
                            <div class="flex justify-between pt-2 border-t border-slate-200 text-sm">
                                <dt class="font-bold text-slate-800">Estimated total</dt>
                                <dd class="font-bold text-slate-800" x-text="money(subtotal + shipping)"></dd>
                            </div>
                        </dl>
                        <p class="text-[10px] text-slate-400">Coupon discount and payment fee are added when the order is saved.</p>

                        <button type="submit" :disabled="!canSubmit"
                                class="w-full px-4 py-2.5 bg-indigo-600 text-white font-semibold text-xs rounded-xl shadow-lg shadow-indigo-600/30 hover:bg-indigo-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none">
                            Create order
                        </button>
                        <p x-show="!canSubmit" x-cloak class="text-[10px] text-slate-400 text-center" x-text="blocker"></p>
                    </section>
                </aside>
            </div>
        </form>
    </div>

    <script>
        function orderForm(cfg) {
            let uid = 0;

            return {
                form: cfg.form,
                charges: cfg.charges,
                lines: cfg.lines.map(l => ({ ...l, key: ++uid, variant_id: l.variant_id ?? '' })),

                search: '',
                results: [],
                searched: false,
                customer: cfg.customer,
                custSearch: '',
                custResults: [],
                custSearched: false,
                selectedAddressId: null,
                zoneTouched: !!cfg.form.shipping_city, // do not override a zone that was already chosen

                /* ---------- money ---------- */
                money(n) {
                    const num = Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    return cfg.position === 'after' ? `${num} ${cfg.symbol}` : `${cfg.symbol}${num}`;
                },

                /* ---------- cart ---------- */
                variantOf(line) {
                    return line.has_variants ? line.variants.find(v => String(v.id) === String(line.variant_id)) : null;
                },
                unit(line) {
                    const v = this.variantOf(line);
                    return v ? v.price : (line.has_variants ? 0 : line.price);
                },
                stock(line) {
                    const v = this.variantOf(line);
                    return v ? v.stock : (line.has_variants ? 0 : line.stock);
                },
                overStock(line) {
                    if (line.has_variants && !line.variant_id) return false;
                    return (line.quantity || 0) > this.stock(line);
                },

                get itemCount() { return this.lines.reduce((s, l) => s + (Number(l.quantity) || 0), 0); },
                get subtotal() { return this.lines.reduce((s, l) => s + this.unit(l) * (Number(l.quantity) || 0), 0); },
                get shipping() {
                    if (this.subtotal === 0) return 0;
                    if (cfg.freeMin !== null && this.subtotal >= cfg.freeMin) return 0;
                    return this.charges[this.form.delivery_zone] ?? 0;
                },
                get blocker() {
                    if (this.lines.length === 0) return 'Add at least one product.';
                    if (this.lines.some(l => l.has_variants && !l.variant_id)) return 'Choose an option for every product that has variants.';
                    if (this.lines.some(l => this.overStock(l))) return 'Reduce the quantity of items that are out of stock.';
                    if (!this.form.payment_gateway_id) return 'Pick a payment method.';
                    return '';
                },
                get canSubmit() { return this.blocker === ''; },

                /* ---------- product search ---------- */
                runSearch() {
                    const q = this.search.trim();
                    if (q.length < 2) { this.results = []; this.searched = false; return; }

                    fetch(`${cfg.searchUrl}?q=${encodeURIComponent(q)}`, { headers: { 'Accept': 'application/json' } })
                        .then(res => res.ok ? res.json() : [])
                        .then(data => { this.results = data; this.searched = true; })
                        .catch(() => { this.results = []; });
                },
                addProduct(p) {
                    const existing = this.lines.find(l => l.product_id === p.product_id && !l.has_variants);
                    if (existing) {
                        existing.quantity = (Number(existing.quantity) || 0) + 1;
                    } else {
                        this.lines.push({ ...p, key: ++uid, variant_id: '', quantity: 1 });
                    }
                    this.search = '';
                    this.results = [];
                    this.searched = false;
                },
                removeLine(index) { this.lines.splice(index, 1); },

                /* ---------- customer ---------- */
                searchCustomers() {
                    const q = this.custSearch.trim();
                    if (q.length < 2) { this.custResults = []; this.custSearched = false; return; }

                    fetch(`${cfg.customerSearchUrl}?q=${encodeURIComponent(q)}`, { headers: { 'Accept': 'application/json' } })
                        .then(res => res.ok ? res.json() : [])
                        .then(data => { this.custResults = data; this.custSearched = true; })
                        .catch(() => { this.custResults = []; });
                },

                // mode 'all': the picked customer replaces what is typed. mode 'empty': only blank boxes are filled.
                selectCustomer(c, mode = 'all') {
                    this.customer = c;
                    this.form.user_id = c.user_id;

                    const fill = (key, value) => {
                        if (mode === 'all' || !this.form[key]) this.form[key] = value || '';
                    };
                    fill('customer_name', c.name);
                    fill('customer_phone', c.phone);
                    fill('customer_email', c.email);

                    const address = c.addresses[0]; // the default one comes first
                    if (address && (mode === 'all' || !this.form.shipping_address)) {
                        this.applyAddress(address);
                    } else if (!address && mode === 'all') {
                        this.copyContact();
                    }

                    this.custSearch = '';
                    this.custResults = [];
                    this.custSearched = false;
                },

                clearCustomer() {
                    this.customer = null;
                    this.form.user_id = null;
                    this.selectedAddressId = null;
                },

                applyAddress(a) {
                    this.selectedAddressId = a.id;
                    this.form.shipping_name = a.name;
                    this.form.shipping_phone = a.phone;
                    this.form.shipping_city = a.city;
                    this.form.shipping_area = a.area;
                    this.form.shipping_address = a.address_line;
                    this.zoneTouched = false; // picking an address is a clear signal, so the area can follow it
                    this.guessZone();
                },

                // typing a known phone number picks the customer up, without overwriting anything already typed
                lookup() {
                    const phone = this.form.customer_phone.trim();
                    if (phone.length < 6 || this.customer) return;

                    fetch(`${cfg.lookupUrl}?phone=${encodeURIComponent(phone)}`, { headers: { 'Accept': 'application/json' } })
                        .then(res => res.ok ? res.json() : { found: false })
                        .then(data => { if (data.found) this.selectCustomer(data, 'empty'); })
                        .catch(() => {});
                },

                copyContact() {
                    this.form.shipping_name = this.form.customer_name;
                    this.form.shipping_phone = this.form.customer_phone;
                },

                // Dhaka addresses are "inside Dhaka"; anything else is outside, until someone picks by hand
                guessZone() {
                    if (this.zoneTouched && this.form.shipping_city === '') return;
                    if (this.zoneTouched) return;
                    this.form.delivery_zone = /dhaka/i.test(this.form.shipping_city) ? 'inside_dhaka' : 'outside_dhaka';
                },
            };
        }
    </script>
@endsection

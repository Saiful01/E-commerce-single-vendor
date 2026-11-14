@extends('layouts.app')

@section('title', 'Checkout | ' . ($setting->site_name ?: config('app.name')))
@section('hide_bottom_nav', '1')

@php
    $cfg = [
        'f' => $fields, 'zone' => $zone, 'gateway' => $gatewayId, 'coupon' => $coupon, 'applied' => $quote['coupon_code'],
        'quote' => $quote, 'other' => $other, 'quoteUrl' => route('checkout.quote'),
        'addresses' => $addresses->map(fn ($a) => [
            'id' => $a->id, 'name' => $a->full_name, 'phone' => $a->phone, 'city' => $a->city, 'area' => $a->area,
            'line' => $a->address_line, 'text' => $a->full_address, 'default' => $a->is_default,
        ])->values(),
    ];
    $input = 'w-full rounded-xl border border-black/15 bg-white px-3.5 py-3 text-sm focus:border-[var(--primary)] focus:outline-none focus:ring-2 focus:ring-[color-mix(in_srgb,var(--primary)_25%,transparent)]';
@endphp

@section('content')
<form method="POST" action="{{ route('checkout.store') }}" class="wrap py-6 md:py-10" x-data="checkoutPage(@js($cfg))" @submit="busy = true" novalidate>
    @csrf
    <div class="mb-6 flex items-center justify-between">
        <h1 class="font-display text-3xl font-extrabold">Checkout</h1>
        <a href="{{ route('cart') }}" class="text-sm font-semibold text-brand">Edit cart</a>
    </div>

    @if($errors->any())
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700" role="alert">
            <p class="font-semibold">Please fix the following:</p>
            <ul class="mt-1 list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="grid items-start gap-6 lg:grid-cols-[1fr_24rem]">
        <div class="space-y-6">

            {{-- 1. Contact --}}
            <section class="space-y-4 rounded-2xl bg-white p-5 md:p-6">
                <h2 class="font-display text-xl font-bold">Your details</h2>
                @guest
                    <p class="rounded-lg bg-neutral-50 p-3 text-sm text-neutral-600">Have an account? <a href="{{ url('/login') }}" class="font-semibold text-brand underline">Sign in</a> to use saved addresses. You can also order as a guest.</p>
                @endguest
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block text-sm font-medium">Full name
                        <input name="customer_name" x-model="f.customer_name" required autocomplete="name" class="{{ $input }} mt-1.5"></label>
                    <label class="block text-sm font-medium">Mobile number
                        <input name="customer_phone" x-model="f.customer_phone" @change="refresh()" required inputmode="tel" autocomplete="tel" placeholder="01XXXXXXXXX" class="{{ $input }} mt-1.5"></label>
                    <label class="block text-sm font-medium sm:col-span-2">Email <span class="font-normal text-neutral-500">(optional, for the order receipt)</span>
                        <input type="email" name="customer_email" x-model="f.customer_email" autocomplete="email" class="{{ $input }} mt-1.5"></label>
                </div>
            </section>

            {{-- 2. Delivery --}}
            <section class="space-y-4 rounded-2xl bg-white p-5 md:p-6">
                <h2 class="font-display text-xl font-bold">Delivery address</h2>

                @if($addresses->isNotEmpty())
                    <div class="grid gap-3 sm:grid-cols-2">
                        <template x-for="a in addresses" :key="a.id">
                            <button type="button" @click="fill(a)" class="rounded-xl border p-3 text-left text-sm transition"
                                    :class="picked === a.id ? 'border-[var(--primary)] bg-[color-mix(in_srgb,var(--primary)_7%,white)]' : 'border-black/15 hover:border-[var(--primary)]'">
                                <span class="flex items-center gap-2 font-semibold"><span x-text="a.name"></span>
                                    <span x-show="a.default" class="rounded bg-neutral-100 px-1.5 py-0.5 text-[11px] font-medium text-neutral-600">Default</span></span>
                                <span class="mt-0.5 block text-neutral-600" x-text="a.text"></span>
                            </button>
                        </template>
                    </div>
                @endif

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block text-sm font-medium">City / District
                        <input name="shipping_city" x-model="f.shipping_city" required autocomplete="address-level2" placeholder="Chattogram" class="{{ $input }} mt-1.5"></label>
                    <label class="block text-sm font-medium">Area / Thana
                        <input name="shipping_area" x-model="f.shipping_area" required autocomplete="address-level3" placeholder="Pahartali" class="{{ $input }} mt-1.5"></label>
                    <label class="block text-sm font-medium sm:col-span-2">Full address
                        <textarea name="shipping_address" x-model="f.shipping_address" required rows="2" autocomplete="street-address" placeholder="House, road, landmark" class="{{ $input }} mt-1.5"></textarea></label>
                </div>

                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="ship_other" value="1" x-model="other" class="h-4 w-4 rounded accent-[var(--primary)]"> Deliver to someone else</label>
                <div class="grid gap-4 sm:grid-cols-2" x-show="other" x-cloak>
                    <label class="block text-sm font-medium">Receiver name
                        <input name="shipping_name" x-model="f.shipping_name" :disabled="!other" class="{{ $input }} mt-1.5"></label>
                    <label class="block text-sm font-medium">Receiver mobile
                        <input name="shipping_phone" x-model="f.shipping_phone" :disabled="!other" inputmode="tel" class="{{ $input }} mt-1.5"></label>
                </div>

                <div>
                    <p class="mb-2 text-sm font-medium">Delivery area</p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach(\App\Models\Order::ZONES as $key => $label)
                            <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border p-3.5 text-sm"
                                   :class="zone === '{{ $key }}' ? 'border-[var(--primary)] bg-[color-mix(in_srgb,var(--primary)_7%,white)]' : 'border-black/15'">
                                <span class="flex items-center gap-2"><input type="radio" name="delivery_zone" value="{{ $key }}" x-model="zone" @change="refresh()" class="accent-[var(--primary)]"> {{ $label }}</span>
                                <span class="font-semibold">{{ $setting->formatMoney($key === 'inside_dhaka' ? $setting->delivery_charge_inside_dhaka : $setting->delivery_charge_outside_dhaka) }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                @auth
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="save_address" value="1" checked class="h-4 w-4 rounded accent-[var(--primary)]"> Save this address for next time</label>
                @endauth
            </section>

            {{-- 3. Payment --}}
            <section class="space-y-4 rounded-2xl bg-white p-5 md:p-6">
                <h2 class="font-display text-xl font-bold">Payment method</h2>
                @forelse($gateways as $g)
                    @php
                        $fields = \App\Support\OnlineGateways::isOnline($g->driver) ? collect() : collect(\App\Models\PaymentGateway::DRIVERS[$g->driver]['fields'] ?? [])->where('secret', false)
                            ->map(fn ($f) => [$f['label'], $g->credentials[$f['key']] ?? null])->filter(fn ($r) => $r[1])->values();
                    @endphp
                    <div class="overflow-hidden rounded-xl border" :class="gateway === {{ $g->id }} ? 'border-[var(--primary)]' : 'border-black/15'">
                        <label class="flex cursor-pointer items-center gap-3 p-3.5" :class="!ok({{ $g->id }}) && 'opacity-50'">
                            <input type="radio" name="payment_gateway_id" value="{{ $g->id }}" x-model.number="gateway" @change="refresh()" :disabled="!ok({{ $g->id }})" class="accent-[var(--primary)]">
                            @if($g->logo_url)<img src="{{ $g->logo_url }}" alt="" class="h-7 w-auto max-w-[4rem] object-contain">@endif
                            <span class="flex-1 text-sm font-semibold">{{ $g->name }}</span>
                            <span class="text-xs text-neutral-500" x-text="feeText({{ $g->id }})"></span>
                        </label>
                        <div x-show="gateway === {{ $g->id }}" x-cloak class="space-y-3 border-t bg-neutral-50 p-4 text-sm">
                            @if($g->instructions)<p class="whitespace-pre-line text-neutral-700">{{ $g->instructions }}</p>@endif
                            @if(\App\Support\OnlineGateways::isOnline($g->driver))
                                <p class="text-neutral-700"><i class="bi bi-shield-lock text-brand"></i> {{ \App\Support\OnlineGateways::note($g->driver) }}</p>
                            @endif
                            @if($fields->isNotEmpty())
                                <dl class="grid gap-x-6 gap-y-1 sm:grid-cols-2">
                                    @foreach($fields as [$label, $value])
                                        <div><dt class="text-xs text-neutral-500">{{ $label }}</dt><dd class="font-semibold">{{ $value }}</dd></div>
                                    @endforeach
                                </dl>
                            @endif
                            @if($g->driver !== 'cod' && ! \App\Support\OnlineGateways::isOnline($g->driver))
                                <label class="block font-medium">Transaction ID <span class="font-normal text-neutral-500">(optional, you can send it later)</span>
                                    <input name="transaction_id" value="{{ old('transaction_id') }}" :disabled="gateway !== {{ $g->id }}" class="{{ $input }} mt-1.5 bg-white"></label>
                            @endif
                            <p x-show="!ok({{ $g->id }})" class="text-red-600">This method is not available for this order amount.</p>
                        </div>
                    </div>
                @empty
                    <p class="rounded-lg bg-amber-50 p-4 text-sm text-amber-800">No payment method is set up yet. Please contact the store.</p>
                @endforelse
            </section>

            <section class="rounded-2xl bg-white p-5 md:p-6">
                <label class="block text-sm font-medium">Order note <span class="font-normal text-neutral-500">(optional)</span>
                    <textarea name="customer_note" rows="2" maxlength="1000" placeholder="Anything we should know about delivery?" class="{{ $input }} mt-1.5">{{ old('customer_note') }}</textarea></label>
            </section>
        </div>

        {{-- Summary --}}
        <aside class="space-y-4 rounded-2xl bg-white p-5 lg:sticky lg:top-28">
            <h2 class="font-display text-xl font-bold">Order summary</h2>
            <ul class="max-h-64 space-y-3 overflow-y-auto pr-1">
                @foreach($cart['lines'] as $l)
                    <li class="flex gap-3 text-sm">
                        <span class="relative h-14 w-14 shrink-0 overflow-hidden rounded-lg bg-neutral-100">
                            <img src="{{ $l['image'] }}" alt="" class="h-full w-full object-cover">
                            <span class="absolute -right-0 -top-0 grid h-5 min-w-5 place-items-center rounded-bl-lg bg-neutral-800 px-1 text-[11px] font-bold text-white">{{ $l['qty'] }}</span>
                        </span>
                        <span class="min-w-0 flex-1"><span class="line-clamp-2 font-medium">{{ $l['name'] }}</span>@if($l['label'])<span class="block text-xs text-neutral-500">{{ $l['label'] }}</span>@endif</span>
                        <span class="font-semibold">{{ $l['line_total_fmt'] }}</span>
                    </li>
                @endforeach
            </ul>

            {{-- Coupon --}}
            <div class="border-t pt-4">
                <input type="hidden" name="coupon_code" :value="applied">
                <template x-if="!applied">
                    <div>
                        <div class="flex gap-2">
                            <input x-model="coupon" @keydown.enter.prevent="apply()" placeholder="Coupon code" class="{{ $input }} uppercase">
                            <button type="button" @click="apply()" class="btn btn-ghost shrink-0 !px-4" :disabled="loading">Apply</button>
                        </div>
                        <p class="mt-2 text-sm text-red-600" x-show="q.coupon_error" x-text="q.coupon_error"></p>
                    </div>
                </template>
                <template x-if="applied">
                    <div class="flex items-center justify-between rounded-xl bg-green-50 p-3 text-sm text-green-800">
                        <span><i class="bi bi-tag-fill"></i> <b x-text="applied"></b> <span x-text="'· ' + q.coupon_label"></span></span>
                        <button type="button" @click="removeCoupon()" class="font-semibold underline">Remove</button>
                    </div>
                </template>
            </div>

            <dl class="space-y-2 border-t pt-4 text-sm" :class="loading && 'opacity-60'">
                <div class="flex justify-between"><dt class="text-neutral-600">Subtotal</dt><dd class="font-semibold" x-text="q.fmt.subtotal"></dd></div>
                <div class="flex justify-between text-green-700" x-show="q.discount > 0"><dt>Discount</dt><dd class="font-semibold" x-text="'-' + q.fmt.discount"></dd></div>
                <div class="flex justify-between"><dt class="text-neutral-600">Delivery</dt><dd class="font-semibold" x-text="q.fmt.shipping"></dd></div>
                <div class="flex justify-between" x-show="q.fee > 0"><dt class="text-neutral-600">Payment fee</dt><dd class="font-semibold" x-text="q.fmt.fee"></dd></div>
            </dl>
            <div class="flex items-baseline justify-between border-t pt-4">
                <span class="font-semibold">Total</span>
                <span class="font-display text-2xl font-extrabold text-brand" :class="loading && 'opacity-60'" x-text="q.fmt.total"></span>
            </div>

            <button class="btn btn-primary w-full !py-3.5 text-base" :disabled="busy || !canPlace">
                <i class="bi" :class="busy ? 'bi-arrow-repeat animate-spin' : 'bi-lock'"></i>
                <span x-text="busy ? 'Placing order...' : 'Place order'"></span>
            </button>
            <p class="text-center text-xs text-neutral-500">By placing the order you agree to our
                @if($terms = \App\Models\Page::urlFor('terms-and-conditions'))<a href="{{ $terms }}" target="_blank" class="underline">Terms</a>@else Terms @endif
                and
                @if($returns = \App\Models\Page::urlFor('return-and-refund-policy'))<a href="{{ $returns }}" target="_blank" class="underline">Return policy</a>@else Return policy @endif,
                and to be contacted to confirm delivery.</p>
        </aside>
    </div>
</form>
@endsection

@push('scripts')
<script>
    function checkoutPage(cfg) {
        return {
            f: cfg.f, zone: cfg.zone, gateway: cfg.gateway, coupon: cfg.coupon || '', applied: cfg.applied || '',
            q: cfg.quote, other: cfg.other, addresses: cfg.addresses, picked: null, busy: false, loading: false,

            get canPlace() { return this.ok(this.gateway) },
            ok(id) { return !!(this.q.gateways[id] && this.q.gateways[id].ok) },
            feeText(id) { const g = this.q.gateways[id]; return g && g.fee_fmt ? g.fee_fmt + ' fee' : '' },

            async refresh(code = this.applied) {
                this.loading = true;
                try {
                    const r = await api(cfg.quoteUrl, { method: 'POST', body: JSON.stringify({
                        delivery_zone: this.zone, coupon_code: code, payment_gateway_id: this.gateway, phone: this.f.customer_phone }) });
                    if (r.ok) { this.q = await r.json(); this.applied = this.q.coupon_code || '' }
                } catch (e) {}
                this.loading = false;
            },
            apply() { if (this.coupon.trim()) this.refresh(this.coupon.trim()) },
            removeCoupon() { this.coupon = ''; this.refresh('') },

            fill(a) {
                this.picked = a.id;
                this.f.shipping_city = a.city; this.f.shipping_area = a.area; this.f.shipping_address = a.line;
                this.f.shipping_name = a.name; this.f.shipping_phone = a.phone;
                this.zone = /dhaka/i.test(a.city) ? 'inside_dhaka' : 'outside_dhaka';
                this.refresh();
            }
        }
    }
</script>
@endpush

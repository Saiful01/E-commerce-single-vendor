@extends('layouts.app')

@php
    $seo = $product->seo_metadata ?? [];
    $gallery = collect([$product->featured_image ? $product->featured_image_url : null])
        ->merge($product->images->map->image_url)->filter()->unique()->values();
    if ($gallery->isEmpty()) { $gallery = collect([asset('images/placeholder.png')]); }

    $cfg = [
        'id'       => $product->id,
        'images'   => $gallery,
        'checkout' => url('/checkout'),
        'money'    => ['symbol' => $setting->currency_symbol, 'after' => $setting->currency_position === 'after'],
        'base'     => [
            'price' => $product->final_price,
            'old'   => $product->has_discount ? (float) $product->price : null,
            'stock' => (int) $product->stock_quantity,
            'sku'   => $product->sku,
        ],
        'variants' => $product->variants->map(fn ($v) => [
            'id' => $v->id, 'group' => $v->attribute_name, 'value' => $v->attribute_value,
            'sku' => $v->sku, 'price' => $v->price !== null ? (float) $v->price : null,
            'stock' => (int) $v->stock_quantity, 'image' => $v->image_url,
        ])->values(),
    ];
    $groups = $product->variants->groupBy('attribute_name');
    $stars = fn ($avg) => collect(range(1, 5))->map(fn ($i) => $i <= round($avg) ? 'bi-star-fill' : 'bi-star');
@endphp

@section('title', ($seo['title'] ?? null) ?: $product->name . ' | ' . ($setting->site_name ?: config('app.name')))
@section('meta_description', ($seo['description'] ?? null) ?: Str::limit(strip_tags($product->short_description ?: $product->description), 155))
@section('hide_bottom_nav', '1')

@push('meta')
    <meta property="og:type" content="product">
    <meta property="og:title" content="{{ $product->name }}">
    <meta property="og:image" content="{{ $gallery->first() }}">
    <meta property="product:price:amount" content="{{ $product->final_price }}">
    <meta property="product:price:currency" content="{{ $setting->currency_code }}">
@endpush

@section('content')
<div class="wrap py-4 md:py-8" x-data="productPage(@js($cfg))">

    {{-- Breadcrumb --}}
    <nav class="mb-4 flex flex-wrap items-center gap-1 text-xs text-neutral-500 md:text-sm" aria-label="Breadcrumb">
        <a href="{{ url('/') }}" class="hover:text-brand">Home</a>
        @if($product->category?->parent)
            <i class="bi bi-chevron-right text-[10px]"></i>
            <a href="{{ $product->category->parent->url }}" class="hover:text-brand">{{ $product->category->parent->name }}</a>
        @endif
        @if($product->category)
            <i class="bi bi-chevron-right text-[10px]"></i>
            <a href="{{ $product->category->url }}" class="hover:text-brand">{{ $product->category->name }}</a>
        @endif
        <i class="bi bi-chevron-right text-[10px]"></i>
        <span class="truncate text-ink">{{ $product->name }}</span>
    </nav>

    <div class="grid gap-6 rounded-2xl bg-white p-4 md:p-8 lg:grid-cols-2 lg:gap-12">

        {{-- ===== Gallery ===== --}}
        <div class="min-w-0 lg:sticky lg:top-28 lg:self-start">
            <div class="relative aspect-square overflow-hidden rounded-xl bg-neutral-100"
                 @mousemove="zoom($event)" @mouseleave="zx = zy = 50; zoomed = false" @mouseenter="zoomed = true"
                 @touchstart.passive="x0 = $event.touches[0].clientX" @touchend="swipe($event)">
                <img :src="images[active]" alt="{{ $product->name }}"
                     class="h-full w-full object-cover transition-transform duration-200 md:cursor-zoom-in"
                     :style="zoomed ? `transform:scale(1.8);transform-origin:${zx}% ${zy}%` : ''">
                @if($product->has_discount)
                    <span class="absolute left-3 top-3 rounded-md bg-accent px-2.5 py-1 text-sm font-bold on-accent">-{{ $product->discount_percent }}%</span>
                @endif
                <template x-if="images.length > 1">
                    <div>
                        <button type="button" @click="prev()" class="absolute left-2 top-1/2 grid h-10 w-10 -translate-y-1/2 place-items-center rounded-full bg-white/90 shadow" aria-label="Previous image"><i class="bi bi-chevron-left"></i></button>
                        <button type="button" @click="next()" class="absolute right-2 top-1/2 grid h-10 w-10 -translate-y-1/2 place-items-center rounded-full bg-white/90 shadow" aria-label="Next image"><i class="bi bi-chevron-right"></i></button>
                    </div>
                </template>
            </div>
            <div class="no-scrollbar mt-3 flex gap-2 overflow-x-auto" x-show="images.length > 1">
                <template x-for="(img, i) in images" :key="img">
                    <button type="button" @click="active = i" class="h-16 w-16 shrink-0 overflow-hidden rounded-lg border-2 md:h-20 md:w-20"
                            :class="active === i ? 'border-[var(--primary)]' : 'border-transparent opacity-70'" :aria-label="`Image ${i + 1}`">
                        <img :src="img" alt="" class="h-full w-full object-cover" loading="lazy">
                    </button>
                </template>
            </div>
        </div>

        {{-- ===== Info ===== --}}
        <div class="min-w-0 space-y-5">
            <div class="space-y-2">
                @if($product->brand)
                    <a href="{{ $product->brand->url }}" class="text-sm font-medium text-brand hover:underline">{{ $product->brand->name }}</a>
                @endif
                <h1 class="font-display text-2xl font-extrabold leading-tight md:text-4xl">{{ $product->name }}</h1>
                <a href="#reviews" @click="tab = 'reviews'" class="inline-flex items-center gap-2 text-sm">
                    <span class="flex text-amber-500">@foreach($stars($stats['avg']) as $c)<i class="bi {{ $c }}"></i>@endforeach</span>
                    <span class="text-neutral-500">{{ $stats['count'] ? $stats['avg'] . ' (' . $stats['count'] . ' reviews)' : 'No reviews yet' }}</span>
                </a>
            </div>

            <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                <span class="font-display text-3xl font-extrabold text-brand" x-text="fmt(price)"></span>
                <span class="text-lg text-neutral-400 line-through" x-show="oldPrice" x-text="fmt(oldPrice)"></span>
                @if($product->has_discount)
                    <span class="rounded-md bg-red-50 px-2 py-0.5 text-sm font-semibold text-red-600" x-show="!variant || variant.price === null">Save {{ $setting->formatMoney($product->price - $product->final_price) }}</span>
                @endif
            </div>

            @if($product->short_description)
                <p class="max-w-prose text-neutral-600">{{ $product->short_description }}</p>
            @endif

            {{-- Variants --}}
            @foreach($groups as $name => $items)
                <div>
                    <p class="mb-2 text-sm font-semibold">{{ $name }}<span class="font-normal text-neutral-500" x-show="variant && variant.group === @js($name)"> : <span x-text="variant.value"></span></span></p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($items as $v)
                            <button type="button" @click="pick({{ $v->id }})" @disabled($v->stock_quantity < 1)
                                    class="rounded-lg border px-4 py-2 text-sm font-medium disabled:cursor-not-allowed disabled:text-neutral-300 disabled:line-through"
                                    :class="selected === {{ $v->id }} ? 'border-[var(--primary)] bg-[color-mix(in_srgb,var(--primary)_9%,white)] text-brand' : 'border-black/15 hover:border-[var(--primary)]'">
                                {{ $v->attribute_value }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endforeach

            {{-- Stock --}}
            <p class="flex items-center gap-2 text-sm font-medium" x-show="!needsVariant">
                <span class="h-2.5 w-2.5 rounded-full" :class="stock > 0 ? 'bg-green-500' : 'bg-red-500'"></span>
                <span x-text="stock > 0 ? (stock <= 5 ? `Only ${stock} left` : 'In stock') : 'Out of stock'"></span>
            </p>

            {{-- Qty + actions (desktop) --}}
            <div class="hidden flex-wrap items-center gap-3 md:flex">
                <div class="flex items-center rounded-xl border border-black/15">
                    <button type="button" class="grid h-12 w-12 place-items-center text-lg" @click="dec()" aria-label="Decrease quantity"><i class="bi bi-dash"></i></button>
                    <input type="number" x-model.number="qty" min="1" :max="stock || 1" class="h-12 w-14 border-0 bg-transparent text-center font-semibold focus:ring-0 [appearance:textfield]" aria-label="Quantity">
                    <button type="button" class="grid h-12 w-12 place-items-center text-lg" @click="inc()" aria-label="Increase quantity"><i class="bi bi-plus"></i></button>
                </div>
                <button type="button" class="btn btn-primary h-12 flex-1 px-6" :disabled="busy || (!needsVariant && stock < 1)" @click="add()">
                    <i class="bi bi-bag-plus"></i> Add to cart
                </button>
                <button type="button" class="btn btn-ghost h-12 px-6" :disabled="busy || (!needsVariant && stock < 1)" @click="add(true)">Buy now</button>
                <button type="button" @click="$store.wish.toggle({{ $product->id }})" class="btn btn-ghost h-12 w-12 !p-0" aria-label="Save to wishlist">
                    <i class="bi" :class="$store.wish.has({{ $product->id }}) ? 'bi-heart-fill text-red-500' : 'bi-heart'"></i>
                </button>
            </div>

            {{-- Meta --}}
            <dl class="grid gap-x-6 gap-y-2 border-t pt-4 text-sm sm:grid-cols-2">
                <div class="flex gap-2"><dt class="text-neutral-500">SKU</dt><dd class="font-medium" x-text="variant?.sku || base.sku || '-'"></dd></div>
                @if($product->category)
                    <div class="flex gap-2"><dt class="text-neutral-500">Category</dt><dd><a href="{{ $product->category->url }}" class="font-medium hover:text-brand">{{ $product->category->name }}</a></dd></div>
                @endif
            </dl>

            {{-- Delivery --}}
            <div class="soft space-y-2 rounded-xl p-4 text-sm">
                <p class="flex items-center gap-2"><i class="bi bi-truck text-brand"></i> Inside Dhaka: <b>{{ $setting->formatMoney($setting->delivery_charge_inside_dhaka ?? 0) }}</b></p>
                <p class="flex items-center gap-2"><i class="bi bi-geo-alt text-brand"></i> Outside Dhaka: <b>{{ $setting->formatMoney($setting->delivery_charge_outside_dhaka ?? 0) }}</b></p>
                @if($setting->free_shipping_min > 0)
                    <p class="flex items-center gap-2"><i class="bi bi-gift text-brand"></i> Free delivery over <b>{{ $setting->formatMoney($setting->free_shipping_min) }}</b></p>
                @endif
            </div>

            {{-- Share --}}
            <div class="flex items-center gap-2 text-sm">
                <span class="text-neutral-500">Share</span>
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($product->url) }}" target="_blank" rel="noopener" class="grid h-9 w-9 place-items-center rounded-full bg-neutral-100 hover:bg-neutral-200" aria-label="Share on Facebook"><i class="bi bi-facebook"></i></a>
                <a href="https://wa.me/?text={{ urlencode($product->name . ' ' . $product->url) }}" target="_blank" rel="noopener" class="grid h-9 w-9 place-items-center rounded-full bg-neutral-100 hover:bg-neutral-200" aria-label="Share on WhatsApp"><i class="bi bi-whatsapp"></i></a>
                <button type="button" @click="navigator.clipboard.writeText('{{ $product->url }}'); $store.toast.show('Link copied')" class="grid h-9 w-9 place-items-center rounded-full bg-neutral-100 hover:bg-neutral-200" aria-label="Copy link"><i class="bi bi-link-45deg"></i></button>
            </div>
        </div>
    </div>

    {{-- ===== Tabs ===== --}}
    <section class="mt-6 rounded-2xl bg-white p-4 md:mt-8 md:p-8" id="reviews">
        <div class="no-scrollbar flex gap-6 overflow-x-auto border-b" role="tablist">
            @foreach(['description' => 'Description', 'reviews' => 'Reviews (' . $stats['count'] . ')', 'delivery' => 'Delivery & returns'] as $key => $label)
                <button type="button" role="tab" @click="tab = '{{ $key }}'" :aria-selected="tab === '{{ $key }}'"
                        class="-mb-px shrink-0 border-b-2 py-3 text-sm font-semibold md:text-base"
                        :class="tab === '{{ $key }}' ? 'border-[var(--primary)] text-brand' : 'border-transparent text-neutral-500'">{{ $label }}</button>
            @endforeach
        </div>

        <div class="pt-6" x-show="tab === 'description'">
            <div class="max-w-3xl space-y-3 leading-relaxed text-neutral-700 [&_h2]:mt-4 [&_h2]:font-display [&_h2]:text-xl [&_h2]:font-bold [&_li]:ml-5 [&_li]:list-disc [&_img]:rounded-lg">
                {{-- Description admin editor theke ashe; rich-text hole HTMLPurifier diye clean kore save korben --}}
                {!! $product->description ?: '<p>No description yet.</p>' !!}
            </div>
        </div>

        <div class="pt-6" x-show="tab === 'delivery'" x-cloak>
            <ul class="max-w-3xl list-disc space-y-2 pl-5 text-neutral-700">
                <li>Orders are confirmed by phone or message before dispatch.</li>
                <li>Delivery takes 1-3 days inside Dhaka and 3-5 days outside Dhaka.</li>
                <li>Check the product at delivery. If it is damaged or wrong, tell us the same day for a replacement.</li>
                @if($rp = \App\Models\Page::urlFor('return-and-refund-policy'))<li>Read our <a href="{{ $rp }}" class="font-semibold text-brand underline">return and refund policy</a>.</li>@endif
                @if($sp = \App\Models\Page::urlFor('shipping-and-delivery-policy'))<li>More about <a href="{{ $sp }}" class="font-semibold text-brand underline">shipping and delivery</a>.</li>@endif
            </ul>
        </div>

        <div class="grid gap-8 pt-6 lg:grid-cols-[18rem_1fr]" x-show="tab === 'reviews'" x-cloak>
            <div class="space-y-4">
                <div class="flex items-end gap-3">
                    <span class="font-display text-5xl font-extrabold">{{ $stats['avg'] ?: '0.0' }}</span>
                    <div class="pb-1"><div class="flex text-amber-500">@foreach($stars($stats['avg']) as $c)<i class="bi {{ $c }}"></i>@endforeach</div>
                    <p class="text-sm text-neutral-500">{{ $stats['count'] }} reviews</p></div>
                </div>
                <div class="space-y-1.5">
                    @foreach($stats['dist'] as $star => $cnt)
                        <div class="flex items-center gap-2 text-sm">
                            <span class="w-3">{{ $star }}</span><i class="bi bi-star-fill text-xs text-amber-500"></i>
                            <span class="h-2 flex-1 overflow-hidden rounded-full bg-neutral-100"><span class="block h-full rounded-full bg-amber-400" style="width:{{ $stats['count'] ? round($cnt / $stats['count'] * 100) : 0 }}%"></span></span>
                            <span class="w-6 text-right text-neutral-500">{{ $cnt }}</span>
                        </div>
                    @endforeach
                </div>

                @auth
                    <form x-data="reviewForm('{{ route('product.review', $product) }}')" @submit.prevent="send()" class="space-y-3 border-t pt-4">
                        <p class="font-semibold">Write a review</p>
                        <div class="flex gap-1 text-2xl text-amber-500" role="radiogroup" aria-label="Rating">
                            <template x-for="n in 5" :key="n">
                                <button type="button" @click="rating = n" :aria-label="n + ' stars'"><i class="bi" :class="n <= rating ? 'bi-star-fill' : 'bi-star'"></i></button>
                            </template>
                        </div>
                        <input x-model="title" maxlength="120" placeholder="Title (optional)" class="w-full rounded-lg border border-black/15 px-3 py-2 text-sm">
                        <textarea x-model="comment" rows="4" required minlength="5" maxlength="1000" placeholder="What did you like or dislike?" class="w-full rounded-lg border border-black/15 px-3 py-2 text-sm"></textarea>
                        <button class="btn btn-primary w-full" :disabled="busy || !rating">Submit review</button>
                    </form>
                @else
                    <p class="border-t pt-4 text-sm"><a href="{{ url('/login') }}" class="font-semibold text-brand underline">Sign in</a> to write a review.</p>
                @endauth
            </div>

            <div class="space-y-5">
                @forelse($reviews as $r)
                    <article class="border-b pb-5 last:border-0">
                        <div class="flex items-center gap-3">
                            <span class="soft grid h-10 w-10 place-items-center rounded-full font-bold text-brand">{{ Str::upper(Str::substr($r->user->name ?? 'U', 0, 1)) }}</span>
                            <div>
                                <p class="text-sm font-semibold">{{ $r->user->name ?? 'Customer' }}</p>
                                <p class="text-xs text-neutral-500">{{ $r->created_at->format('d M Y') }}</p>
                            </div>
                            <span class="ml-auto flex text-sm text-amber-500">@foreach($stars($r->rating) as $c)<i class="bi {{ $c }}"></i>@endforeach</span>
                        </div>
                        @if($r->title)<h4 class="mt-3 font-semibold">{{ $r->title }}</h4>@endif
                        <p class="mt-1 max-w-prose text-neutral-700">{{ $r->comment }}</p>
                    </article>
                @empty
                    <p class="text-neutral-500">No reviews yet. Be the first to share how it worked for you.</p>
                @endforelse
            </div>
        </div>
    </section>

    {{-- ===== Related ===== --}}
    @if($related->isNotEmpty())
        <section class="mt-10">
            <h2 class="mb-4 font-display text-2xl font-extrabold md:text-3xl">You may also like</h2>
            <div class="no-scrollbar -mx-4 flex snap-x gap-3 overflow-x-auto px-4 pb-2 md:mx-0 md:grid md:grid-cols-4 md:gap-5 md:overflow-visible md:px-0">
                @foreach($related as $item)
                    <div class="w-44 shrink-0 snap-start md:w-auto">@include('partials.product-card', ['product' => $item])</div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ===== Mobile sticky buy bar ===== --}}
    <div class="fixed inset-x-0 bottom-0 z-40 flex items-center gap-2 border-t border-black/10 bg-white p-3 pb-[max(.75rem,env(safe-area-inset-bottom))] md:hidden">
        <div class="flex items-center rounded-xl border border-black/15">
            <button type="button" class="grid h-11 w-9 place-items-center" @click="dec()" aria-label="Decrease quantity"><i class="bi bi-dash"></i></button>
            <span class="w-6 text-center text-sm font-semibold" x-text="qty"></span>
            <button type="button" class="grid h-11 w-9 place-items-center" @click="inc()" aria-label="Increase quantity"><i class="bi bi-plus"></i></button>
        </div>
        <button type="button" class="btn btn-ghost h-11 flex-1 !px-2 text-sm" :disabled="busy || (!needsVariant && stock < 1)" @click="add()">Add to cart</button>
        <button type="button" class="btn btn-primary h-11 flex-1 !px-2 text-sm" :disabled="busy || (!needsVariant && stock < 1)" @click="add(true)">Buy now</button>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function productPage(cfg) {
        return {
            images: [...cfg.images], active: 0, base: cfg.base, variants: cfg.variants,
            selected: null, qty: 1, tab: 'description', busy: false,
            zoomed: false, zx: 50, zy: 50, x0: 0,

            get variant()      { return this.variants.find(v => v.id === this.selected) || null },
            get needsVariant() { return this.variants.length > 0 && !this.variant },
            get price()        { return this.variant && this.variant.price !== null ? this.variant.price : this.base.price },
            get oldPrice()     { return this.variant && this.variant.price !== null ? null : this.base.old },
            get stock()        { return this.variant ? this.variant.stock : this.base.stock },

            fmt(n) {
                const s = Number(n).toLocaleString('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                return cfg.money.after ? `${s} ${cfg.money.symbol}` : `${cfg.money.symbol}${s}`;
            },
            pick(id) {
                this.selected = id; this.qty = 1;
                const img = this.variant && this.variant.image;
                if (img) {
                    let i = this.images.indexOf(img);
                    if (i < 0) { this.images.push(img); i = this.images.length - 1 }
                    this.active = i;
                }
            },
            inc() { if (this.qty < (this.stock || 1)) this.qty++ },
            dec() { if (this.qty > 1) this.qty-- },
            next() { this.active = (this.active + 1) % this.images.length },
            prev() { this.active = (this.active - 1 + this.images.length) % this.images.length },
            swipe(e) { const d = e.changedTouches[0].clientX - this.x0; if (Math.abs(d) > 40) { d < 0 ? this.next() : this.prev() } },
            zoom(e) {
                if (matchMedia('(hover: none)').matches) return;
                const r = e.currentTarget.getBoundingClientRect();
                this.zx = ((e.clientX - r.left) / r.width) * 100; this.zy = ((e.clientY - r.top) / r.height) * 100;
            },
            async add(buyNow = false) {
                if (this.needsVariant) { Alpine.store('toast').show('Please choose an option first', 'err'); return }
                if (this.stock < 1) { Alpine.store('toast').show('This item is out of stock', 'err'); return }
                this.qty = Math.min(Math.max(1, this.qty || 1), this.stock);
                this.busy = true;
                const ok = await Alpine.store('cart').add(cfg.id, this.qty, this.selected);
                this.busy = false;
                if (ok && buyNow) location.href = cfg.checkout;
            }
        }
    }

    function reviewForm(url) {
        return {
            rating: 0, title: '', comment: '', busy: false,
            async send() {
                this.busy = true;
                try {
                    const r = await api(url, { method: 'POST', body: JSON.stringify({ rating: this.rating, title: this.title, comment: this.comment }) });
                    const d = await r.json().catch(() => ({}));
                    const msg = d.message || (d.errors ? Object.values(d.errors)[0][0] : 'Could not send review');
                    Alpine.store('toast').show(msg, r.ok ? 'ok' : 'err');
                    if (r.ok) { this.rating = 0; this.title = ''; this.comment = '' }
                } catch (e) { Alpine.store('toast').show('Network problem. Try again.', 'err') }
                this.busy = false;
            }
        }
    }
</script>
@endpush

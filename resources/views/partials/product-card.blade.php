{{-- Needs: $product (with category loaded), $setting --}}
@php $p = $product; @endphp
<article class="group relative flex flex-col overflow-hidden rounded-xl border border-black/5 bg-white">
    <a href="{{ $p->url }}" class="relative block aspect-square overflow-hidden bg-neutral-100">
        <img src="{{ $p->featured_image_url }}" alt="{{ $p->name }}" loading="lazy" decoding="async"
             class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
        @if($p->has_discount)
            <span class="absolute left-2 top-2 rounded-md bg-accent px-2 py-0.5 text-xs font-bold on-accent">-{{ $p->discount_percent }}%</span>
        @endif
        @unless($p->in_stock)
            <span class="absolute inset-x-0 bottom-0 bg-neutral-900/75 py-1 text-center text-xs font-medium text-white">Out of stock</span>
        @endunless
    </a>

    <button type="button" @click="$store.wish.toggle({{ $p->id }})" aria-label="Save to wishlist"
            class="absolute right-2 top-2 grid h-8 w-8 place-items-center rounded-full bg-white/95 shadow-sm">
        <i class="bi" :class="$store.wish.has({{ $p->id }}) ? 'bi-heart-fill text-red-500' : 'bi-heart'"></i>
    </button>
    <button type="button" @click="$store.quick.load('{{ route('product.quick', $p->slug) }}')"
            class="absolute right-2 top-12 hidden h-8 w-8 place-items-center rounded-full bg-white/95 shadow-sm md:group-hover:grid"
            aria-label="Quick view">
        <i class="bi bi-eye"></i>
    </button>

    <div class="flex flex-1 flex-col gap-1 p-3">
        @if($p->category)
            <a href="{{ $p->category->url }}" class="truncate text-xs text-neutral-500 hover:text-brand">{{ $p->category->name }}</a>
        @endif
        <h3 class="line-clamp-2 min-h-[2.5rem] text-sm font-semibold leading-snug">
            <a href="{{ $p->url }}">{{ $p->name }}</a>
        </h3>
        <div class="mt-1 flex flex-wrap items-baseline gap-x-2">
            <span class="font-display text-base font-bold text-brand">{{ $setting->formatMoney($p->final_price) }}</span>
            @if($p->has_discount)
                <span class="text-xs text-neutral-400 line-through">{{ $setting->formatMoney($p->price) }}</span>
            @endif
        </div>

        <div class="mt-auto pt-2">
            @if(! $p->in_stock)
                <button class="btn btn-ghost w-full !py-2 text-sm" disabled>Out of stock</button>
            @elseif($p->has_variants)
                <a href="{{ $p->url }}" class="btn btn-ghost w-full !py-2 text-sm">Choose option</a>
            @else
                <button type="button" class="btn btn-primary w-full !py-2 text-sm"
                        :disabled="$store.cart.busy" @click="$store.cart.add({{ $p->id }})">
                    <i class="bi bi-bag-plus"></i> Add to cart
                </button>
            @endif
        </div>
    </div>
</article>

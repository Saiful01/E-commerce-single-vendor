{{-- Usage: <x-banner-slot position="home_hero" class="wrap my-8" /> --}}
@if($banners->isNotEmpty())
@switch($meta['type'])

{{-- ============ Rotating slider ============ --}}
@case('slider')
<section {{ $attributes }}
    x-data="{
        i: 0, n: {{ $banners->count() }}, timer: null, x0: 0,
        start() { if (this.n > 1 && !matchMedia('(prefers-reduced-motion: reduce)').matches) this.timer = setInterval(() => this.next(), 5500) },
        stop()  { clearInterval(this.timer) },
        next()  { this.i = (this.i + 1) % this.n },
        prev()  { this.i = (this.i - 1 + this.n) % this.n },
        swipe(e){ const d = e.changedTouches[0].clientX - this.x0; if (Math.abs(d) > 40) { d < 0 ? this.next() : this.prev() } }
    }"
    x-init="start()" @mouseenter="stop()" @mouseleave="start()"
    @touchstart.passive="x0 = $event.touches[0].clientX" @touchend="swipe($event)"
    class="relative" aria-roledescription="carousel">
    <div class="overflow-hidden">
        <div class="flex transition-transform duration-700 ease-out" :style="`transform:translateX(-${i * 100}%)`">
            @foreach($banners as $b)
                <div class="w-full shrink-0" :aria-hidden="i !== {{ $loop->index }}">
                    @include('partials.banner-item', ['b' => $b, 'meta' => $meta, 'eager' => $loop->first])
                </div>
            @endforeach
        </div>
    </div>
    @if($banners->count() > 1)
        <button type="button" @click="prev()" aria-label="Previous slide"
                class="hidden md:grid absolute left-4 top-1/2 -translate-y-1/2 h-11 w-11 place-items-center rounded-full bg-white/90 shadow hover:bg-white">
            <i class="bi bi-chevron-left"></i></button>
        <button type="button" @click="next()" aria-label="Next slide"
                class="hidden md:grid absolute right-4 top-1/2 -translate-y-1/2 h-11 w-11 place-items-center rounded-full bg-white/90 shadow hover:bg-white">
            <i class="bi bi-chevron-right"></i></button>
        <div class="absolute bottom-3 inset-x-0 flex justify-center gap-2">
            @foreach($banners as $b)
                <button type="button" @click="i = {{ $loop->index }}" aria-label="Go to slide {{ $loop->iteration }}"
                        class="h-2 rounded-full bg-white transition-all"
                        :class="i === {{ $loop->index }} ? 'w-6' : 'w-2 opacity-60'"></button>
            @endforeach
        </div>
    @endif
</section>
@break

{{-- ============ Popup (once a day) ============ --}}
@case('popup')
<div x-data="{
        open: false, key: 'promo_popup_{{ $banners->first()->id }}',
        init() {
            try { const t = localStorage.getItem(this.key); if (t && Date.now() - Number(t) < 864e5) return } catch (e) {}
            setTimeout(() => this.open = true, 2500)
        },
        close() { this.open = false; try { localStorage.setItem(this.key, Date.now()) } catch (e) {} }
    }"
    x-show="open" x-cloak @keydown.escape.window="close()"
    class="fixed inset-0 z-[60] grid place-items-center p-4" role="dialog" aria-modal="true">
    <div class="absolute inset-0 bg-black/60" @click="close()"></div>
    <div class="relative w-full max-w-sm md:max-w-md overflow-hidden rounded-2xl shadow-2xl" x-show="open" x-transition>
        <button type="button" @click="close()" aria-label="Close"
                class="absolute right-2 top-2 z-10 grid h-9 w-9 place-items-center rounded-full bg-white shadow">
            <i class="bi bi-x-lg"></i></button>
        @include('partials.banner-item', ['b' => $banners->first(), 'meta' => $meta, 'eager' => true])
    </div>
</div>
@break

{{-- ============ Grid ============ --}}
@default
@php
    $n    = $banners->count();
    $cols = match (true) {
        $n === 1 => 'grid-cols-1',
        $n === 2 => 'grid-cols-1 sm:grid-cols-2',
        $n === 3 => 'grid-cols-1 sm:grid-cols-3',
        default  => 'grid-cols-2 lg:grid-cols-4',
    };
@endphp
<section {{ $attributes }}>
    <div class="grid gap-3 md:gap-5 {{ $cols }}">
        @foreach($banners as $b)
            <div class="overflow-hidden rounded-xl">
                @include('partials.banner-item', ['b' => $b, 'meta' => $meta, 'eager' => false])
            </div>
        @endforeach
    </div>
</section>
@endswitch
@endif

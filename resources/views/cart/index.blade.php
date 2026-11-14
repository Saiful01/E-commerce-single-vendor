@extends('layouts.app')

@section('title', 'Your cart | ' . ($setting->site_name ?: config('app.name')))
@section('hide_bottom_nav', '1')

@section('content')
<div class="wrap py-6 md:py-10" x-data="cartPage(@js($cart))">
    <h1 class="mb-6 font-display text-3xl font-extrabold">Your cart <span class="text-lg font-semibold text-neutral-500" x-show="lines.length" x-text="'(' + count + ')'"></span></h1>

    {{-- Empty --}}
    <div x-show="!lines.length" x-cloak class="rounded-2xl bg-white px-6 py-16 text-center">
        <i class="bi bi-bag text-5xl text-neutral-300"></i>
        <p class="mt-4 text-lg font-semibold">Your cart is empty</p>
        <p class="mt-1 text-neutral-500">Add a few products and they will show up here.</p>
        <a href="{{ url('/shop') }}" class="btn btn-primary mt-6">Start shopping</a>
    </div>

    <div x-show="lines.length" x-cloak class="grid items-start gap-6 lg:grid-cols-[1fr_22rem]">
        <div class="space-y-4">
            {{-- Free delivery progress --}}
            <template x-if="free_min">
                <div class="rounded-2xl bg-white p-4">
                    <p class="text-sm" x-show="free_progress < 100">Add <b x-text="free_left_fmt"></b> more for free delivery.</p>
                    <p class="text-sm font-medium text-brand" x-show="free_progress >= 100"><i class="bi bi-check-circle-fill"></i> You get free delivery on this order.</p>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-neutral-100"><div class="h-full rounded-full bg-brand transition-all" :style="`width:${free_progress}%`"></div></div>
                </div>
            </template>

            <ul class="divide-y divide-black/5 rounded-2xl bg-white">
                <template x-for="l in lines" :key="l.key">
                    <li class="flex gap-3 p-4 md:gap-5 md:p-5">
                        <a :href="l.url" class="h-20 w-20 shrink-0 overflow-hidden rounded-xl bg-neutral-100 md:h-24 md:w-24"><img :src="l.image" :alt="l.name" class="h-full w-full object-cover"></a>
                        <div class="flex min-w-0 flex-1 flex-col gap-1">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <a :href="l.url" class="line-clamp-2 font-semibold leading-snug hover:text-brand" x-text="l.name"></a>
                                    <p class="text-sm text-neutral-500" x-show="l.label" x-text="l.label"></p>
                                </div>
                                <button type="button" @click="remove(l.key)" class="grid h-8 w-8 shrink-0 place-items-center rounded-full text-neutral-400 hover:bg-neutral-100 hover:text-red-600" aria-label="Remove item"><i class="bi bi-trash3"></i></button>
                            </div>
                            <p class="text-sm font-semibold text-red-600" x-show="!l.available">Out of stock. Remove it to continue.</p>
                            <div class="mt-auto flex flex-wrap items-center justify-between gap-3 pt-2">
                                <div class="flex items-center rounded-xl border border-black/15" :class="!l.available && 'opacity-40 pointer-events-none'">
                                    <button type="button" class="grid h-9 w-9 place-items-center" @click="change(l, l.qty - 1)" aria-label="Decrease quantity"><i class="bi bi-dash"></i></button>
                                    <span class="w-8 text-center text-sm font-semibold" x-text="l.qty"></span>
                                    <button type="button" class="grid h-9 w-9 place-items-center" @click="change(l, l.qty + 1)" :disabled="l.qty >= l.stock" aria-label="Increase quantity"><i class="bi bi-plus"></i></button>
                                </div>
                                <div class="text-right">
                                    <p class="font-display text-lg font-bold text-brand" x-text="l.line_total_fmt"></p>
                                    <p class="text-xs text-neutral-500" x-show="l.qty > 1"><span x-text="l.unit_fmt"></span> each</p>
                                </div>
                            </div>
                        </div>
                    </li>
                </template>
            </ul>
            <a href="{{ url('/shop') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-brand"><i class="bi bi-arrow-left"></i> Continue shopping</a>
        </div>

        <aside class="space-y-4 rounded-2xl bg-white p-5 lg:sticky lg:top-28">
            <h2 class="font-display text-xl font-bold">Order summary</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-neutral-600">Subtotal</dt><dd class="font-semibold" x-text="subtotal_fmt"></dd></div>
                <div class="flex justify-between"><dt class="text-neutral-600">Delivery</dt><dd class="text-neutral-500">Calculated at checkout</dd></div>
            </dl>
            <div class="flex items-baseline justify-between border-t pt-4">
                <span class="font-semibold">Total so far</span>
                <span class="font-display text-2xl font-extrabold text-brand" x-text="subtotal_fmt"></span>
            </div>
            <a :href="ok ? '{{ route('checkout') }}' : '#'" class="btn btn-primary hidden w-full md:flex" :class="!ok && 'opacity-50 pointer-events-none'">Go to checkout</a>
            <p class="text-xs text-neutral-500">Coupons and payment methods are on the next step.</p>
        </aside>
    </div>

    {{-- Mobile checkout bar --}}
    <div x-show="lines.length" x-cloak class="fixed inset-x-0 bottom-0 z-40 flex items-center gap-3 border-t border-black/10 bg-white p-3 pb-[max(.75rem,env(safe-area-inset-bottom))] md:hidden">
        <div class="min-w-0"><p class="text-xs text-neutral-500">Subtotal</p><p class="font-display text-lg font-bold" x-text="subtotal_fmt"></p></div>
        <a :href="ok ? '{{ route('checkout') }}' : '#'" class="btn btn-primary ml-auto flex-1" :class="!ok && 'opacity-50 pointer-events-none'">Go to checkout</a>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function cartPage(init) {
        return {
            ...init, busy: false,
            sync(d) {
                Object.assign(this, d);
                Alpine.store('cart').count = d.count;
                if (d.message) Alpine.store('toast').show(d.message, 'err');
            },
            async call(url, body) {
                if (this.busy) return;
                this.busy = true;
                try {
                    const r = await api(url, { method: 'POST', body: JSON.stringify(body) });
                    if (r.ok) this.sync(await r.json()); else Alpine.store('toast').show('Could not update cart', 'err');
                } catch (e) { Alpine.store('toast').show('Network problem. Try again.', 'err') }
                this.busy = false;
            },
            change(l, qty) { this.call('{{ route('cart.update') }}', { key: l.key, qty }) },
            remove(key)    { this.call('{{ route('cart.remove') }}', { key }) },
        }
    }
</script>
@endpush

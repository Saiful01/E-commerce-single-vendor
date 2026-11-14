@php
    $primary   = $setting->primary_color   ?: '#0f766e';
    $secondary = $setting->secondary_color ?: '#f5b83d';
    $headerBg  = $setting->header_bg_color ?: '#ffffff';
    $footerBg  = $setting->footer_bg_color ?: '#12201f';
    $textColor = $setting->text_color      ?: '#1f2933';
    $siteName  = $setting->site_name ?: config('app.name');

    // Text colour that stays readable on the secondary (accent) colour: white on dark, dark on light
    $hex = ltrim($secondary, '#');
    if (strlen($hex) === 3) { $hex = preg_replace('/(.)/', '$1$1', $hex); }
    $lum = strlen($hex) === 6 && ctype_xdigit($hex)
        ? (0.299 * hexdec(substr($hex, 0, 2)) + 0.587 * hexdec(substr($hex, 2, 2)) + 0.114 * hexdec(substr($hex, 4, 2))) / 255
        : 0;
    $onAccent = $lum > 0.6 ? '#1f2933' : '#ffffff';
    $wa        = preg_replace('/\D/', '', (string) $setting->whatsapp_number);
    if ($wa && str_starts_with($wa, '0')) { $wa = '88' . $wa; }   // Bangladesh: 01xxxxxxxxx -> 8801xxxxxxxxx
    $socials = ['facebook_url' => 'bi-facebook', 'instagram_url' => 'bi-instagram', 'youtube_url' => 'bi-youtube',
                'twitter_url' => 'bi-twitter-x', 'linkedin_url' => 'bi-linkedin', 'tiktok_url' => 'bi-tiktok'];
    $accountUrl = auth()->check() ? url('/account') : url('/login');
    $wishIds = auth()->check() ? \App\Models\Wishlist::where('user_id', auth()->id())->pluck('product_id')->all() : [];
@endphp
    <!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $setting->meta_title ?: $siteName)</title>
    <meta name="description" content="@yield('meta_description', $setting->meta_description)">
    <meta name="keywords" content="{{ $setting->meta_keywords }}">
    <meta name="theme-color" content="{{ $primary }}">
    @if($setting->favicon_url)<link rel="icon" href="{{ $setting->favicon_url }}">@endif
    @stack('meta')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,800&family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    {{-- Production e Vite (npm i tailwindcss) use korben. Ei CDN gula shudhu quick start er jonno. --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: {
                    fontFamily: { sans: ['Hind Siliguri', 'system-ui', 'sans-serif'], display: ['Bricolage Grotesque', 'Hind Siliguri', 'sans-serif'] },
                    colors: { brand: 'var(--primary)', accent: 'var(--secondary)', ink: 'var(--text)' }
                } } }
    </script>

    <style>
        :root { --primary: {{ $primary }}; --secondary: {{ $secondary }}; --on-accent: {{ $onAccent }}; --header-bg: {{ $headerBg }};
            --footer-bg: {{ $footerBg }}; --text: {{ $textColor }}; }
        body { color: var(--text); background: #f7f7f5; }
        .wrap { max-width: 80rem; margin-inline: auto; padding-inline: 1rem; }
        .on-accent { color: var(--on-accent); }
        .soft { background: color-mix(in srgb, var(--primary) 9%, white); }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; font-weight: 600;
            border-radius: .75rem; padding: .7rem 1.1rem; transition: filter .15s, background .15s; }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover:not(:disabled) { filter: brightness(.92); }
        .btn-ghost { border: 1px solid color-mix(in srgb, var(--text) 20%, transparent); background: #fff; }
        .btn-ghost:hover:not(:disabled) { border-color: var(--primary); color: var(--primary); }
        .btn:disabled { opacity: .5; cursor: not-allowed; }
        .ar { aspect-ratio: var(--r-m); }
        @media (min-width: 768px) { .ar { aspect-ratio: var(--r-d); } }
        :focus-visible { outline: 2px solid var(--primary); outline-offset: 2px; }
        [x-cloak] { display: none !important; }
        .no-scrollbar::-webkit-scrollbar { display: none; } .no-scrollbar { scrollbar-width: none; }
        @media (prefers-reduced-motion: reduce) { *, ::before, ::after { transition: none !important; animation: none !important; scroll-behavior: auto !important; } }
    </style>

    {{-- Global Alpine stores: cart, wishlist, toast, quick view --}}
    <script>
        const csrf = document.querySelector('meta[name=csrf-token]').content;
        const api = (url, opt = {}) => fetch(url, { ...opt, headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', ...(opt.headers || {}) } });

        document.addEventListener('alpine:init', () => {
            Alpine.store('toast', {
                items: [],
                show(msg, type = 'ok', link = null) {
                    const id = Date.now() + Math.random();
                    this.items.push({ id, msg, type, link });
                    setTimeout(() => this.items = this.items.filter(i => i.id !== id), 3500);
                }
            });

            Alpine.store('cart', {
                count: 0, busy: false,
                async init() {
                    try { const r = await api('{{ url('/cart/summary') }}'); if (r.ok) this.count = (await r.json()).count ?? 0 } catch (e) {}
                },
                // Porer step e CartController::add() banabo. Expected response: { count: 3, message: "..." }
                async add(productId, qty = 1, variantId = null) {
                    this.busy = true;
                    try {
                        const r = await api('{{ url('/cart/add') }}', { method: 'POST', body: JSON.stringify({ product_id: productId, variant_id: variantId, qty }) });
                        const d = await r.json().catch(() => ({}));
                        if (!r.ok) { Alpine.store('toast').show(d.message || 'Could not add to cart', 'err'); return false }
                        this.count = d.count ?? this.count + qty;
                        Alpine.store('toast').show(d.message || 'Added to cart', 'ok', '{{ url('/cart') }}');
                        return true;
                    } catch (e) { Alpine.store('toast').show('Network problem. Try again.', 'err'); return false }
                    finally { this.busy = false }
                }
            });

            Alpine.store('wish', {
                ids: @json($wishIds),
                auth: @json(auth()->check()),
                has(id) { return this.ids.includes(id) },
                async toggle(id) {
                    if (!this.auth) {
                        Alpine.store('toast').show('Sign in to save items to your wishlist', 'err');
                        setTimeout(() => location.href = '{{ url('/login') }}', 1200);
                        return;
                    }
                    const was = this.has(id);
                    this.ids = was ? this.ids.filter(i => i !== id) : [...this.ids, id];
                    try {
                        const r = await api('{{ url('/wishlist/toggle') }}', { method: 'POST', body: JSON.stringify({ product_id: id }) });
                        if (!r.ok) throw new Error();
                        Alpine.store('toast').show(was ? 'Removed from wishlist' : 'Saved to wishlist');
                    } catch (e) {
                        this.ids = was ? [...this.ids, id] : this.ids.filter(i => i !== id);
                        Alpine.store('toast').show('Could not update wishlist', 'err');
                    }
                }
            });

            Alpine.store('quick', {
                open: false, loading: false, p: null,
                async load(url) {
                    this.open = true; this.loading = true; this.p = null;
                    try { const r = await api(url); this.p = r.ok ? await r.json() : null } catch (e) {}
                    this.loading = false;
                    if (!this.p) { this.open = false; Alpine.store('toast').show('Could not load product', 'err') }
                },
                close() { this.open = false }
            });
        });

        function liveSearch() {
            return {
                q: '', results: [], open: false, loading: false, ctrl: null,
                async run() {
                    if (this.q.trim().length < 2) { this.results = []; this.open = false; return }
                    this.ctrl?.abort(); this.ctrl = new AbortController(); this.loading = true;
                    try {
                        const r = await fetch('{{ route('search.suggest') }}?q=' + encodeURIComponent(this.q), { headers: { Accept: 'application/json' }, signal: this.ctrl.signal });
                        this.results = await r.json(); this.open = true;
                    } catch (e) {}
                    this.loading = false;
                }
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.14.8/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

    @if($setting->google_analytics_id)
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $setting->google_analytics_id }}"></script>
        <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag('js',new Date());gtag('config','{{ $setting->google_analytics_id }}');</script>
    @endif
    @if($setting->facebook_pixel_id)
        <script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','{{ $setting->facebook_pixel_id }}');fbq('track','PageView');</script>
    @endif
    @stack('head')
</head>

<body class="font-sans antialiased @hasSection('hide_bottom_nav') pb-24 @else pb-16 @endif md:pb-0"
      x-data="{ drawer: false, mSearch: false }" :class="drawer && 'overflow-hidden'">

{{-- ===================== Header ===================== --}}
@if($setting->free_shipping_min > 0)
    <div class="bg-brand py-1.5 text-center text-xs font-medium text-white sm:text-sm">
        Free delivery on orders over {{ $setting->formatMoney($setting->free_shipping_min) }}
    </div>
@endif

<header class="sticky top-0 z-40 border-b border-black/5" style="background:var(--header-bg)">
    <div class="wrap flex h-[4.5rem] items-center gap-3 md:grid md:h-24 md:grid-cols-[1fr_minmax(0,42rem)_1fr] md:gap-8">
        <button class="grid h-10 w-10 place-items-center rounded-lg md:hidden" @click="drawer = true" aria-label="Open menu">
            <i class="bi bi-list text-2xl"></i>
        </button>

        <a href="{{ url('/') }}" class="shrink-0 max-md:mx-auto md:justify-self-start" aria-label="{{ $siteName }}">
            @if($setting->logo_url)
                <img src="{{ $setting->logo_url }}" alt="{{ $siteName }}" class="h-10 w-auto max-w-[11rem] object-contain md:h-16 md:max-w-[17rem]">
            @else
                <span class="font-display text-2xl font-extrabold text-brand md:text-3xl">{{ $siteName }}</span>
            @endif
        </a>

        {{-- Desktop live search --}}
        <form action="{{ url('/shop') }}" method="GET" x-data="liveSearch()" @click.outside="open = false"
              class="relative hidden w-full md:block">
            <input type="search" name="q" x-model="q" @input.debounce.300ms="run()" @focus="results.length && (open = true)"
                   placeholder="Search products, brands, SKU" autocomplete="off"
                   class="w-full rounded-xl border border-black/10 bg-neutral-50 py-3 pl-4 pr-12 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
            <button class="absolute right-1 top-1 grid h-10 w-10 place-items-center rounded-lg bg-brand text-white" aria-label="Search">
                <i class="bi" :class="loading ? 'bi-arrow-repeat animate-spin' : 'bi-search'"></i>
            </button>
            <div x-show="open" x-cloak class="absolute left-0 right-0 top-full z-50 mt-2 overflow-hidden rounded-xl border border-black/5 bg-white shadow-xl">
                <template x-for="r in results" :key="r.url">
                    <a :href="r.url" class="flex items-center gap-3 px-3 py-2 hover:bg-neutral-50">
                        <img :src="r.image" alt="" class="h-12 w-12 rounded-lg object-cover">
                        <span class="min-w-0 flex-1 truncate text-sm font-medium" x-text="r.name"></span>
                        <span class="text-sm font-bold text-brand" x-text="r.price"></span>
                    </a>
                </template>
                <p x-show="!results.length" class="px-4 py-3 text-sm text-neutral-500">No product found for "<span x-text="q"></span>".</p>
            </div>
        </form>

        <nav class="ml-auto flex items-center gap-1 md:ml-0 md:justify-self-end md:gap-2" aria-label="Quick links">
            <button class="grid h-10 w-10 place-items-center rounded-lg md:hidden" @click="mSearch = !mSearch" aria-label="Search">
                <i class="bi bi-search text-xl"></i>
            </button>
            <a href="{{ url('/wishlist') }}" class="relative hidden h-10 w-10 place-items-center rounded-lg hover:bg-black/5 sm:grid" aria-label="Wishlist">
                <i class="bi bi-heart text-xl"></i>
                <span x-show="$store.wish.ids.length" x-text="$store.wish.ids.length" x-cloak
                      class="absolute -right-0.5 -top-0.5 grid h-5 min-w-5 place-items-center rounded-full bg-accent px-1 text-[11px] font-bold on-accent"></span>
            </a>
            <a href="{{ $accountUrl }}" class="hidden items-center gap-2 rounded-lg px-2 py-2 hover:bg-black/5 md:flex">
                <i class="bi bi-person text-xl"></i>
                <span class="text-sm font-medium">{{ auth()->check() ? 'My account' : 'Sign in' }}</span>
            </a>
            <a href="{{ url('/cart') }}" class="relative grid h-10 w-10 place-items-center rounded-lg hover:bg-black/5" aria-label="Cart">
                <i class="bi bi-bag text-xl"></i>
                <span x-show="$store.cart.count" x-text="$store.cart.count" x-cloak
                      class="absolute -right-0.5 -top-0.5 grid h-5 min-w-5 place-items-center rounded-full bg-brand px-1 text-[11px] font-bold text-white"></span>
            </a>
        </nav>
    </div>

    {{-- Mobile search --}}
    <form action="{{ url('/shop') }}" method="GET" x-show="mSearch" x-cloak x-transition class="wrap pb-3 md:hidden">
        <div class="relative">
            <input type="search" name="q" placeholder="Search products" class="w-full rounded-xl border border-black/10 bg-neutral-50 py-3 pl-4 pr-12 text-sm">
            <button class="absolute right-1 top-1 grid h-10 w-10 place-items-center rounded-lg bg-brand text-white" aria-label="Search"><i class="bi bi-search"></i></button>
        </div>
    </form>

    {{-- Desktop category bar --}}
    @if($menuCategories->isNotEmpty())
        <div class="hidden border-t border-black/5 md:block">
            <ul class="wrap flex items-center gap-1 text-sm font-medium">
                <li><a href="{{ url('/shop') }}" class="block px-3 py-3 hover:text-brand">All products</a></li>
                @foreach($menuCategories as $cat)
                    <li class="group relative">
                        <a href="{{ $cat->url }}" class="flex items-center gap-1 px-3 py-3 hover:text-brand">
                            {{ $cat->name }} @if($cat->children->isNotEmpty())<i class="bi bi-chevron-down text-[10px]"></i>@endif
                        </a>
                        @if($cat->children->isNotEmpty())
                            <div class="invisible absolute left-0 top-full z-40 min-w-56 rounded-xl border border-black/5 bg-white p-2 opacity-0 shadow-xl transition group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100">
                                @foreach($cat->children as $child)
                                    <a href="{{ $child->url }}" class="block rounded-lg px-3 py-2 hover:bg-neutral-50 hover:text-brand">{{ $child->name }}</a>
                                @endforeach
                            </div>
                        @endif
                    </li>
                @endforeach
                @foreach($headerPages as $hp)
                    <li class="{{ $loop->first ? 'ml-auto' : '' }}"><a href="{{ url('/pages/' . $hp->slug) }}" class="block px-3 py-3 text-neutral-600 hover:text-brand">{{ $hp->title }}</a></li>
                @endforeach
            </ul>
        </div>
    @endif
</header>

{{-- ===================== Mobile drawer ===================== --}}
<div class="md:hidden" x-show="drawer" x-cloak @keydown.escape.window="drawer = false">
    <div class="fixed inset-0 z-50 bg-black/50" @click="drawer = false" x-transition.opacity></div>
    <aside class="fixed inset-y-0 left-0 z-50 flex w-[85%] max-w-xs flex-col bg-white shadow-2xl"
           x-show="drawer" x-transition:enter="transition duration-200" x-transition:enter-start="-translate-x-full"
           x-transition:enter-end="translate-x-0" x-transition:leave="transition duration-150"
           x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full">
        <div class="flex items-center justify-between border-b p-4">
            <span class="font-display text-lg font-extrabold text-brand">{{ $siteName }}</span>
            <button @click="drawer = false" class="grid h-9 w-9 place-items-center rounded-lg" aria-label="Close menu"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="flex-1 overflow-y-auto p-2">
            <a href="{{ url('/shop') }}" class="block rounded-lg px-3 py-3 font-medium">All products</a>
            @foreach($menuCategories as $cat)
                <div x-data="{ o: false }">
                    <div class="flex items-center">
                        <a href="{{ $cat->url }}" class="flex-1 rounded-lg px-3 py-3 font-medium">{{ $cat->name }}</a>
                        @if($cat->children->isNotEmpty())
                            <button @click="o = !o" class="grid h-10 w-10 place-items-center" :aria-expanded="o" aria-label="Show subcategories">
                                <i class="bi bi-chevron-down transition" :class="o && 'rotate-180'"></i></button>
                        @endif
                    </div>
                    @if($cat->children->isNotEmpty())
                        <div x-show="o" x-collapse x-cloak class="ml-3 border-l pl-2">
                            @foreach($cat->children as $child)
                                <a href="{{ $child->url }}" class="block rounded-lg px-3 py-2 text-sm text-neutral-600">{{ $child->name }}</a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
        <div class="space-y-2 border-t p-4 text-sm">
            <a href="{{ $accountUrl }}" class="btn btn-primary w-full"><i class="bi bi-person"></i> {{ auth()->check() ? 'My account' : 'Sign in / Register' }}</a>
            @if($setting->phone_number)<a href="tel:{{ $setting->phone_number }}" class="btn btn-ghost w-full"><i class="bi bi-telephone"></i> {{ $setting->phone_number }}</a>@endif
        </div>
    </aside>
</div>

<main>@yield('content')</main>

{{-- ===================== Footer ===================== --}}
<footer class="mt-16 text-white/80" style="background:var(--footer-bg)">
    <div class="wrap grid gap-10 py-12 sm:grid-cols-2 {{ $footerPages->isNotEmpty() ? 'lg:grid-cols-5' : 'lg:grid-cols-4' }}">
        <div class="space-y-3">
            <p class="font-display text-2xl font-extrabold text-white">{{ $siteName }}</p>
            @if($setting->site_tagline)<p class="text-sm">{{ $setting->site_tagline }}</p>@endif
            @if($setting->footer_text)<p class="text-sm text-white/60">{{ $setting->footer_text }}</p>@endif
            <div class="flex flex-wrap gap-2 pt-2">
                @foreach($socials as $field => $icon)
                    @if($setting->$field)
                        <a href="{{ $setting->$field }}" target="_blank" rel="noopener" aria-label="{{ Str::before($field, '_url') }}"
                           class="grid h-10 w-10 place-items-center rounded-full bg-white/10 hover:bg-white/20"><i class="bi {{ $icon }}"></i></a>
                    @endif
                @endforeach
            </div>
        </div>
        <div>
            <h4 class="mb-3 font-semibold text-white">Shop</h4>
            <ul class="space-y-2 text-sm">
                <li><a href="{{ url('/shop') }}" class="hover:text-white">All products</a></li>
                @foreach($menuCategories->take(5) as $cat)
                    <li><a href="{{ $cat->url }}" class="hover:text-white">{{ $cat->name }}</a></li>
                @endforeach
            </ul>
        </div>
        @if($footerPages->isNotEmpty())
            <div>
                <h4 class="mb-3 font-semibold text-white">Information</h4>
                <ul class="space-y-2 text-sm">
                    @foreach($footerPages as $fp)
                        <li><a href="{{ url('/pages/' . $fp->slug) }}" class="hover:text-white">{{ $fp->title }}</a></li>
                    @endforeach
                    <li><a href="{{ url('/track-order') }}" class="hover:text-white">Track order</a></li>
                </ul>
            </div>
        @endif
        <div>
            <h4 class="mb-3 font-semibold text-white">My account</h4>
            <ul class="space-y-2 text-sm">
                <li><a href="{{ $accountUrl }}" class="hover:text-white">Profile</a></li>
                <li><a href="{{ url('/account/orders') }}" class="hover:text-white">My orders</a></li>
                <li><a href="{{ url('/wishlist') }}" class="hover:text-white">Wishlist</a></li>
                <li><a href="{{ url('/cart') }}" class="hover:text-white">Cart</a></li>
            </ul>
        </div>
        <div>
            <h4 class="mb-3 font-semibold text-white">Contact</h4>
            <ul class="space-y-2 text-sm">
                @if($setting->address)<li class="flex gap-2"><i class="bi bi-geo-alt mt-0.5"></i><span>{{ $setting->address }}</span></li>@endif
                @if($setting->phone_number)<li class="flex gap-2"><i class="bi bi-telephone mt-0.5"></i><a href="tel:{{ $setting->phone_number }}">{{ $setting->phone_number }}</a></li>@endif
                @if($setting->primary_email)<li class="flex gap-2"><i class="bi bi-envelope mt-0.5"></i><a href="mailto:{{ $setting->primary_email }}" class="break-all">{{ $setting->primary_email }}</a></li>@endif
                @if($setting->business_hours)<li class="flex gap-2"><i class="bi bi-clock mt-0.5"></i><span>{{ $setting->business_hours }}</span></li>@endif
            </ul>
        </div>
    </div>
    <div class="border-t border-white/10 py-4 text-center text-xs text-white/50">
        &copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.
    </div>
</footer>

{{-- ===================== Mobile bottom nav ===================== --}}
@hasSection('hide_bottom_nav')
@else
    <nav class="fixed inset-x-0 bottom-0 z-40 grid grid-cols-4 border-t border-black/10 bg-white pb-[env(safe-area-inset-bottom)] md:hidden" aria-label="Main">
        @php $tab = fn ($active) => 'flex flex-col items-center gap-0.5 py-2 text-[11px] font-medium ' . ($active ? 'text-brand' : 'text-neutral-500'); @endphp
        <a href="{{ url('/') }}" class="{{ $tab(request()->is('/')) }}"><i class="bi bi-house text-xl"></i>Home</a>
        <button type="button" @click="drawer = true" class="{{ $tab(false) }}"><i class="bi bi-grid text-xl"></i>Categories</button>
        <a href="{{ url('/cart') }}" class="relative {{ $tab(request()->is('cart')) }}">
            <i class="bi bi-bag text-xl"></i>Cart
            <span x-show="$store.cart.count" x-text="$store.cart.count" x-cloak
                  class="absolute left-1/2 top-1 grid h-4 min-w-4 place-items-center rounded-full bg-brand px-1 text-[10px] font-bold text-white"></span>
        </a>
        <a href="{{ $accountUrl }}" class="{{ $tab(request()->is('account*')) }}"><i class="bi bi-person text-xl"></i>Account</a>
    </nav>
@endif

{{-- WhatsApp --}}
@if($wa)
    <a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener" aria-label="Chat on WhatsApp"
       class="fixed bottom-20 right-4 z-30 grid h-12 w-12 place-items-center rounded-full bg-[#25d366] text-2xl text-white shadow-lg md:bottom-6 md:h-14 md:w-14">
        <i class="bi bi-whatsapp"></i>
    </a>
@endif

{{-- Quick view modal --}}
<div x-data x-show="$store.quick.open" x-cloak @keydown.escape.window="$store.quick.close()"
     class="fixed inset-0 z-[55] flex items-end justify-center md:items-center md:p-6" role="dialog" aria-modal="true">
    <div class="absolute inset-0 bg-black/55" @click="$store.quick.close()"></div>
    <div class="relative max-h-[90vh] w-full overflow-y-auto rounded-t-2xl bg-white md:max-w-3xl md:rounded-2xl"
         x-show="$store.quick.open" x-transition>
        <button @click="$store.quick.close()" class="absolute right-3 top-3 z-10 grid h-9 w-9 place-items-center rounded-full bg-white shadow" aria-label="Close"><i class="bi bi-x-lg"></i></button>
        <template x-if="$store.quick.loading">
            <div class="grid h-72 place-items-center"><i class="bi bi-arrow-repeat animate-spin text-3xl text-brand"></i></div>
        </template>
        <template x-if="$store.quick.p && !$store.quick.loading">
            <div class="grid md:grid-cols-2">
                <img :src="$store.quick.p.image" :alt="$store.quick.p.name" class="aspect-square w-full object-cover">
                <div class="flex flex-col gap-3 p-5 md:p-7">
                    <p class="text-sm text-neutral-500" x-text="$store.quick.p.brand" x-show="$store.quick.p.brand"></p>
                    <h3 class="font-display text-2xl font-bold leading-tight" x-text="$store.quick.p.name"></h3>
                    <div class="flex items-baseline gap-3">
                        <span class="font-display text-2xl font-bold text-brand" x-text="$store.quick.p.price"></span>
                        <span class="text-neutral-400 line-through" x-show="$store.quick.p.old_price" x-text="$store.quick.p.old_price"></span>
                        <span class="rounded-md bg-accent px-2 py-0.5 text-xs font-bold on-accent" x-show="$store.quick.p.discount" x-text="'-' + $store.quick.p.discount + '%'"></span>
                    </div>
                    <p class="text-sm text-neutral-600" x-text="$store.quick.p.excerpt" x-show="$store.quick.p.excerpt"></p>
                    <div class="mt-auto flex flex-col gap-2 pt-3 sm:flex-row">
                        <template x-if="$store.quick.p.in_stock && !$store.quick.p.has_variants">
                            <button class="btn btn-primary flex-1" :disabled="$store.cart.busy"
                                    @click="await $store.cart.add($store.quick.p.id) && $store.quick.close()">
                                <i class="bi bi-bag-plus"></i> Add to cart</button>
                        </template>
                        <a :href="$store.quick.p.url" class="btn btn-ghost flex-1">View details</a>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>

{{-- Toasts --}}
<div x-data class="pointer-events-none fixed inset-x-0 bottom-20 z-[70] flex flex-col items-center gap-2 px-4 md:bottom-6" aria-live="polite">
    <template x-for="t in $store.toast.items" :key="t.id">
        <div x-transition class="pointer-events-auto flex max-w-sm items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-white shadow-lg"
             :class="t.type === 'err' ? 'bg-red-600' : 'bg-neutral-900'">
            <i class="bi" :class="t.type === 'err' ? 'bi-exclamation-circle' : 'bi-check-circle'"></i>
            <span x-text="t.msg"></span>
            <a x-show="t.link" :href="t.link" class="ml-2 font-bold underline">View cart</a>
        </div>
    </template>
</div>

@if(session('success') || session('error'))
    <script>
        document.addEventListener('alpine:initialized', () =>
            Alpine.store('toast').show(@js(session('error') ?? session('success')), '{{ session('error') ? 'err' : 'ok' }}'));
    </script>
@endif
@stack('scripts')
</body>
</html>

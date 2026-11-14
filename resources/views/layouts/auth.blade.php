@php
    $primary   = $setting->primary_color   ?: '#0f766e';
    $secondary = $setting->secondary_color ?: '#f5b83d';
    $textColor = $setting->text_color      ?: '#1f2933';
    $siteName  = $setting->site_name ?: config('app.name');
    $freeMin   = (float) $setting->free_shipping_min;

    $perks = array_values(array_filter([
        ['bi-truck', $freeMin > 0 ? 'Free delivery on orders over ' . $setting->formatMoney($freeMin) : 'Fast delivery across Bangladesh'],
        ['bi-wallet2', 'Pay on delivery or with bKash, Nagad and bank transfer'],
        ['bi-geo-alt', 'Save addresses and check out in a few taps'],
        ['bi-box-seam', 'Track every order from your account'],
    ]));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="{{ $primary }}">
    <title>@yield('title', 'Account') | {{ $siteName }}</title>
    @if($setting->favicon_url)<link rel="icon" href="{{ $setting->favicon_url }}">@endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,800&family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    {{-- Production e Vite use korben (storefront layout er moto) --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: {
            fontFamily: { sans: ['Hind Siliguri', 'system-ui', 'sans-serif'], display: ['Bricolage Grotesque', 'Hind Siliguri', 'sans-serif'] },
            colors: { brand: 'var(--primary)', accent: 'var(--secondary)', ink: 'var(--text)' }
        } } }
    </script>
    <style>
        :root { --primary: {{ $primary }}; --secondary: {{ $secondary }}; --text: {{ $textColor }}; }
        body { color: var(--text); background: #f7f7f5; }
        .soft { background: color-mix(in srgb, var(--primary) 9%, white); }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; font-weight: 600; border-radius: .75rem; padding: .75rem 1.1rem; transition: filter .15s; }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover:not(:disabled) { filter: brightness(.92); }
        .btn:disabled { opacity: .6; cursor: wait; }
        :focus-visible { outline: 2px solid var(--primary); outline-offset: 2px; }
        [x-cloak] { display: none !important; }
        @media (prefers-reduced-motion: reduce) { *, ::before, ::after { transition: none !important; animation: none !important; } }
    </style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>

<body class="font-sans antialiased">
<div class="grid min-h-screen lg:grid-cols-[5fr_6fr]">

    {{-- Brand panel (desktop) --}}
    <aside class="relative hidden overflow-hidden bg-brand p-12 text-white lg:flex lg:flex-col lg:justify-between">
        <span class="pointer-events-none absolute -right-28 -top-28 h-96 w-96 rounded-full border-[56px] border-white/10"></span>
        <span class="pointer-events-none absolute -bottom-32 -left-20 h-80 w-80 rounded-full border-[48px] border-white/10"></span>

        <a href="{{ url('/') }}" class="relative inline-flex w-fit items-center" aria-label="{{ $siteName }}">
            @if($setting->logo_url)
                <span class="rounded-xl bg-white px-4 py-2"><img src="{{ $setting->logo_url }}" alt="{{ $siteName }}" class="h-9 w-auto"></span>
            @else
                <span class="font-display text-3xl font-extrabold">{{ $siteName }}</span>
            @endif
        </a>

        <div class="relative max-w-md space-y-8">
            <div class="space-y-3">
                <h2 class="font-display text-4xl font-extrabold leading-tight">@yield('aside_title', 'Your orders, your addresses, all in one place')</h2>
                @if($setting->site_tagline)<p class="text-lg text-white/80">{{ $setting->site_tagline }}</p>@endif
            </div>
            <ul class="space-y-4">
                @foreach($perks as [$icon, $text])
                    <li class="flex items-center gap-3">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-white/15 text-lg"><i class="bi {{ $icon }}"></i></span>
                        <span class="text-white/90">{{ $text }}</span>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="relative space-y-1 text-sm text-white/75">
            @if($setting->phone_number)<p><i class="bi bi-telephone me-2"></i>{{ $setting->phone_number }}</p>@endif
            @if($setting->primary_email)<p><i class="bi bi-envelope me-2"></i>{{ $setting->primary_email }}</p>@endif
            @if($setting->business_hours)<p><i class="bi bi-clock me-2"></i>{{ $setting->business_hours }}</p>@endif
        </div>
    </aside>

    {{-- Form side --}}
    <main class="flex min-h-screen flex-col px-4 py-6 sm:px-8">
        <div class="mx-auto flex w-full max-w-md items-center justify-between lg:max-w-lg">
            <a href="{{ url('/') }}" class="lg:hidden" aria-label="{{ $siteName }}">
                @if($setting->logo_url)<img src="{{ $setting->logo_url }}" alt="{{ $siteName }}" class="h-8 w-auto">
                @else<span class="font-display text-xl font-extrabold text-brand">{{ $siteName }}</span>@endif
            </a>
            <a href="{{ url('/') }}" class="ml-auto inline-flex items-center gap-1.5 text-sm font-medium text-neutral-600 hover:text-brand"><i class="bi bi-arrow-left"></i> Back to store</a>
        </div>

        <div class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center py-8 lg:max-w-lg">
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-black/5 sm:p-9">
                <div class="mb-6 space-y-1.5">
                    <h1 class="font-display text-3xl font-extrabold">@yield('heading')</h1>
                    <p class="text-neutral-600">@yield('subheading')</p>
                </div>
                @yield('content')
            </div>
            @hasSection('footer')<div class="mt-5 text-center text-sm text-neutral-600">@yield('footer')</div>@endif
        </div>

        <p class="text-center text-xs text-neutral-500">&copy; {{ date('Y') }} {{ $siteName }}@if($setting->footer_text) · {{ $setting->footer_text }}@endif</p>
    </main>
</div>
</body>
</html>

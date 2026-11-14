@extends('layouts.app')

@section('title', trim(($setting->meta_title ?: $setting->site_name) . ($setting->site_tagline ? ' | ' . $setting->site_tagline : '')))

@section('content')

    {{-- Hero slider (full width) --}}
    <x-banner-slot position="home_hero" />

    {{-- Store promises --}}
    <section class="wrap -mt-0 py-5 md:py-8">
        <ul class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            @php
                $promises = [
                    ['bi-truck', 'Fast delivery', $setting->free_shipping_min > 0 ? 'Free over ' . $setting->formatMoney($setting->free_shipping_min) : 'Across Bangladesh'],
                    ['bi-shield-check', 'Secure checkout', 'Pay on delivery or online'],
                    ['bi-arrow-repeat', 'Easy returns', 'Hassle-free exchange'],
                    ['bi-headset', 'Real support', $setting->phone_number ?: 'Message us anytime'],
                ];
            @endphp
            @foreach($promises as [$icon, $title, $sub])
                <li class="flex items-center gap-3 rounded-xl bg-white p-3 md:p-4">
                    <span class="soft grid h-10 w-10 shrink-0 place-items-center rounded-full text-lg text-brand md:h-12 md:w-12"><i class="bi {{ $icon }}"></i></span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold">{{ $title }}</p>
                        <p class="truncate text-xs text-neutral-500">{{ $sub }}</p>
                    </div>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- Featured categories --}}
    @if($categories->isNotEmpty())
        <section class="wrap py-4 md:py-6">
            <div class="mb-4 flex items-end justify-between">
                <h2 class="font-display text-2xl font-extrabold md:text-3xl">Shop by category</h2>
                <a href="{{ url('/shop') }}" class="text-sm font-semibold text-brand">All products</a>
            </div>
            <div class="no-scrollbar -mx-4 flex snap-x gap-4 overflow-x-auto px-4 pb-2 md:mx-0 md:grid md:grid-cols-5 md:gap-6 md:px-0 lg:grid-cols-10">
                @foreach($categories as $cat)
                    <a href="{{ $cat->url }}" class="group w-20 shrink-0 snap-start text-center md:w-auto">
                <span class="soft mx-auto grid aspect-square w-20 place-items-center overflow-hidden rounded-full border-2 border-transparent transition group-hover:border-[var(--primary)] md:w-full">
                    @if($cat->image_url)
                        <img src="{{ $cat->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                    @else
                        <span class="font-display text-2xl font-extrabold text-brand">{{ Str::upper(Str::substr($cat->name, 0, 1)) }}</span>
                    @endif
                </span>
                        <span class="mt-2 block truncate text-xs font-medium md:text-sm">{{ $cat->name }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Category tile banners (square) --}}
    <x-banner-slot position="home_category" class="wrap my-6 md:my-8" />

    {{-- Deals --}}
    @if($deals->isNotEmpty())
        <section class="wrap py-4 md:py-6">
            <div class="mb-4 flex items-end justify-between">
                <div>
                    <h2 class="font-display text-2xl font-extrabold md:text-3xl">Deals this week</h2>
                    <p class="text-sm text-neutral-500">Discounted prices while stock lasts</p>
                </div>
                <a href="{{ url('/shop?sort=discount') }}" class="text-sm font-semibold text-brand">See all</a>
            </div>
            <div class="grid grid-cols-2 gap-3 md:grid-cols-3 md:gap-5 lg:grid-cols-4">
                @foreach($deals as $product) @include('partials.product-card', ['product' => $product]) @endforeach
            </div>
        </section>
    @endif

    <x-banner-slot position="home_middle" class="wrap my-6 md:my-10" />

    {{-- Featured --}}
    @if($featured->isNotEmpty())
        <section class="wrap py-4 md:py-6">
            <div class="mb-4 flex items-end justify-between">
                <h2 class="font-display text-2xl font-extrabold md:text-3xl">Our picks</h2>
                <a href="{{ url('/shop?featured=1') }}" class="text-sm font-semibold text-brand">See all</a>
            </div>
            <div class="grid grid-cols-2 gap-3 md:grid-cols-3 md:gap-5 lg:grid-cols-4">
                @foreach($featured as $product) @include('partials.product-card', ['product' => $product]) @endforeach
            </div>
        </section>
    @endif

    {{-- New arrivals --}}
    @if($newArrivals->isNotEmpty())
        <section class="wrap py-4 md:py-6">
            <div class="mb-4 flex items-end justify-between">
                <h2 class="font-display text-2xl font-extrabold md:text-3xl">Just arrived</h2>
                <a href="{{ url('/shop?sort=latest') }}" class="text-sm font-semibold text-brand">See all</a>
            </div>
            <div class="no-scrollbar -mx-4 flex snap-x gap-3 overflow-x-auto px-4 pb-2 md:mx-0 md:grid md:grid-cols-3 md:gap-5 md:overflow-visible md:px-0 lg:grid-cols-4">
                @foreach($newArrivals as $product)
                    <div class="w-44 shrink-0 snap-start md:w-auto">@include('partials.product-card', ['product' => $product])</div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Brands --}}
    @if($brands->isNotEmpty())
        <section class="wrap py-6 md:py-8">
            <h2 class="mb-4 font-display text-2xl font-extrabold md:text-3xl">Brands we carry</h2>
            <div class="grid grid-cols-3 gap-3 sm:grid-cols-4 md:grid-cols-6">
                @foreach($brands as $brand)
                    <a href="{{ $brand->url }}" class="grid aspect-[3/2] place-items-center rounded-xl bg-white p-4 hover:ring-2 hover:ring-[var(--primary)]" title="{{ $brand->name }}">
                        @if($brand->logo_url)
                            <img src="{{ $brand->logo_url }}" alt="{{ $brand->name }}" loading="lazy" class="max-h-full max-w-full object-contain grayscale transition hover:grayscale-0">
                        @else
                            <span class="text-center text-sm font-semibold">{{ $brand->name }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <x-banner-slot position="home_bottom" class="wrap my-6 md:my-10" />

    {{-- Promo popup (once a day) --}}
    <x-banner-slot position="popup" />

@endsection

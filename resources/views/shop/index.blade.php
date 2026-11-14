@extends('layouts.app')

@section('title', $title . ' | ' . ($setting->site_name ?: config('app.name')))
@section('meta_description', $category?->meta_description ?? $brand?->meta_description ?? $setting->meta_description)

@php
    $sorts = ['latest' => 'Newest', 'price_asc' => 'Price: low to high', 'price_desc' => 'Price: high to low', 'discount' => 'Biggest discount', 'name' => 'Name A-Z'];
    $filtered = $min !== null || $max !== null || request()->boolean('in_stock') || request()->boolean('featured') || $q !== '';
    $input = 'w-full rounded-lg border border-black/15 bg-white px-3 py-2 text-sm';
@endphp

@section('content')
<div class="wrap py-4 md:py-8" x-data="{ filters: false }">

    <nav class="mb-3 flex flex-wrap items-center gap-1 text-xs text-neutral-500 md:text-sm" aria-label="Breadcrumb">
        <a href="{{ url('/') }}" class="hover:text-brand">Home</a><i class="bi bi-chevron-right text-[10px]"></i>
        <a href="{{ url('/shop') }}" class="hover:text-brand">Shop</a>
        @if($category?->parent)<i class="bi bi-chevron-right text-[10px]"></i><a href="{{ $category->parent->url }}" class="hover:text-brand">{{ $category->parent->name }}</a>@endif
        @if($category || $brand)<i class="bi bi-chevron-right text-[10px]"></i><span class="text-ink">{{ $title }}</span>@endif
    </nav>

    <x-banner-slot position="category_page" class="mb-5" />

    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-2xl font-extrabold md:text-4xl">{{ $title }}</h1>
            <p class="text-sm text-neutral-500">{{ $products->total() }} {{ Str::plural('product', $products->total()) }}</p>
            @if($category?->description)<p class="mt-2 max-w-2xl text-sm text-neutral-600">{{ $category->description }}</p>@endif
        </div>
        <div class="flex items-center gap-2">
            <button type="button" @click="filters = true" class="btn btn-ghost !py-2 lg:hidden"><i class="bi bi-sliders"></i> Filters</button>
            <label class="sr-only" for="sort">Sort</label>
            <select id="sort" name="sort" form="filterForm" onchange="document.getElementById('filterForm').submit()" class="rounded-xl border border-black/15 bg-white py-2 pl-3 pr-8 text-sm">
                @foreach($sorts as $k => $label)<option value="{{ $k }}" @selected($sort === $k)>{{ $label }}</option>@endforeach
            </select>
        </div>
    </div>

    <div class="grid items-start gap-6 lg:grid-cols-[16rem_1fr]">

        {{-- Filters (drawer on mobile) --}}
        <div x-show="filters" x-cloak class="fixed inset-0 z-50 bg-black/50 lg:hidden" @click="filters = false"></div>
        <aside class="fixed inset-y-0 left-0 z-50 w-[88%] max-w-sm space-y-6 overflow-y-auto bg-white p-5 transition-transform lg:static lg:z-auto lg:w-auto lg:max-w-none lg:translate-x-0 lg:space-y-5 lg:overflow-visible lg:bg-transparent lg:p-0"
               :class="filters ? 'translate-x-0' : '-translate-x-full'">
            <div class="flex items-center justify-between lg:hidden"><p class="font-display text-lg font-bold">Filters</p>
                <button type="button" @click="filters = false" class="grid h-9 w-9 place-items-center" aria-label="Close filters"><i class="bi bi-x-lg"></i></button></div>

            @if($categories->isNotEmpty())
                <div class="rounded-2xl bg-white p-4 lg:p-5">
                    <p class="mb-2 font-semibold">Categories</p>
                    <ul class="space-y-1 text-sm">
                        @foreach($categories as $c)
                            @php $open = $category && ($category->id === $c->id || $category->parent_id === $c->id); @endphp
                            <li>
                                <a href="{{ $c->url }}" class="block rounded-lg px-2 py-1.5 hover:bg-neutral-50 {{ $category?->id === $c->id ? 'font-semibold text-brand' : '' }}">{{ $c->name }}</a>
                                @if($c->children->isNotEmpty() && $open)
                                    <ul class="ml-3 border-l pl-2">@foreach($c->children as $ch)
                                        <li><a href="{{ $ch->url }}" class="block rounded-lg px-2 py-1 text-neutral-600 hover:text-brand {{ $category?->id === $ch->id ? 'font-semibold text-brand' : '' }}">{{ $ch->name }}</a></li>
                                    @endforeach</ul>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form id="filterForm" method="GET" action="{{ url()->current() }}" class="space-y-5 rounded-2xl bg-white p-4 lg:p-5">
                @if($q !== '')<input type="hidden" name="q" value="{{ $q }}">@endif
                @if(request()->boolean('featured'))<input type="hidden" name="featured" value="1">@endif
                <div>
                    <p class="mb-2 font-semibold">Price</p>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="number" name="min" min="0" value="{{ $min }}" placeholder="Min" class="{{ $input }}" aria-label="Minimum price">
                        <input type="number" name="max" min="0" value="{{ $max }}" placeholder="Max" class="{{ $input }}" aria-label="Maximum price">
                    </div>
                </div>
                @if(! $brand && $brands->isNotEmpty())
                    <div>
                        <p class="mb-2 font-semibold">Brand</p>
                        <select onchange="if(this.value) location.href=this.value" class="{{ $input }}" aria-label="Brand">
                            <option value="">All brands</option>
                            @foreach($brands as $b)<option value="{{ url('/brand/' . $b->slug) }}">{{ $b->name }}</option>@endforeach
                        </select>
                    </div>
                @endif
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="in_stock" value="1" @checked(request()->boolean('in_stock')) class="h-4 w-4 rounded accent-[var(--primary)]"> In stock only</label>
                <div class="flex gap-2">
                    <button class="btn btn-primary flex-1 !py-2.5 text-sm" @click="filters = false">Apply</button>
                    @if($filtered)<a href="{{ url()->current() }}" class="btn btn-ghost !py-2.5 text-sm">Clear</a>@endif
                </div>
            </form>

            <x-banner-slot position="sidebar" class="hidden lg:block" />
        </aside>

        {{-- Products --}}
        <div class="min-w-0">
            @if($products->isEmpty())
                <div class="rounded-2xl bg-white px-6 py-16 text-center">
                    <i class="bi bi-search text-5xl text-neutral-300"></i>
                    <p class="mt-4 text-lg font-semibold">No products found</p>
                    <p class="mt-1 text-neutral-500">Try removing a filter or searching with a different word.</p>
                    <a href="{{ url('/shop') }}" class="btn btn-primary mt-6">See all products</a>
                </div>
            @else
                <div class="grid grid-cols-2 gap-3 md:grid-cols-3 md:gap-5">
                    @foreach($products as $product) @include('partials.product-card', ['product' => $product]) @endforeach
                </div>
                <div class="mt-8">{{ $products->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection

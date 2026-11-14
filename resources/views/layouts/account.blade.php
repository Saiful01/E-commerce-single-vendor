@extends('layouts.app')

@section('title', trim($__env->yieldContent('page_title', 'My account')) . ' | ' . ($setting->site_name ?: config('app.name')))

@php
    $nav = [
        ['account.dashboard', 'Overview', 'bi-grid-1x2', 'account.dashboard'],
        ['account.orders', 'My orders', 'bi-box-seam', 'account.orders*'],
        ['wishlist', 'Wishlist', 'bi-heart', 'wishlist'],
        ['account.addresses', 'Addresses', 'bi-geo-alt', 'account.addresses*'],
        ['account.profile', 'Profile and password', 'bi-person-gear', 'account.profile*'],
    ];
@endphp

@section('content')
<div class="wrap grid items-start gap-5 py-5 md:py-10 lg:grid-cols-[16rem_1fr] lg:gap-8">
    <aside class="space-y-3 lg:sticky lg:top-28">
        <div class="hidden items-center gap-3 rounded-2xl bg-white p-4 lg:flex">
            <span class="soft grid h-12 w-12 shrink-0 place-items-center rounded-full font-display text-lg font-extrabold text-brand">{{ Str::upper(Str::substr(auth()->user()->name, 0, 1)) }}</span>
            <div class="min-w-0"><p class="truncate font-semibold">{{ auth()->user()->name }}</p><p class="truncate text-xs text-neutral-500">{{ auth()->user()->phone ?: auth()->user()->email }}</p></div>
        </div>
        <nav class="no-scrollbar -mx-4 flex gap-2 overflow-x-auto px-4 lg:mx-0 lg:flex-col lg:gap-1 lg:rounded-2xl lg:bg-white lg:p-2" aria-label="Account">
            @foreach($nav as [$route, $label, $icon, $match])
                @php $on = request()->routeIs($match); @endphp
                <a href="{{ route($route) }}" @if($on) aria-current="page" @endif
                   class="flex shrink-0 items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-medium {{ $on ? 'bg-brand text-white' : 'bg-white text-neutral-700 lg:bg-transparent lg:hover:bg-neutral-50' }}">
                    <i class="bi {{ $icon }}"></i> {{ $label }}
                </a>
            @endforeach
            <form method="POST" action="{{ route('logout') }}" class="hidden lg:block">@csrf
                <button class="flex w-full items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50"><i class="bi bi-box-arrow-right"></i> Sign out</button>
            </form>
        </nav>
    </aside>

    <section class="min-w-0 space-y-5">@yield('account')</section>
</div>
@endsection

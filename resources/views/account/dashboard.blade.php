@extends('layouts.account')
@section('page_title', 'My account')

@section('account')
<div class="rounded-2xl bg-white p-5 md:p-7">
    <h1 class="font-display text-2xl font-extrabold md:text-3xl">Hello, {{ Str::before($user->name, ' ') }}</h1>
    <p class="text-neutral-600">Here is what is happening with your orders.</p>
</div>

<div class="grid grid-cols-2 gap-3 md:gap-4 xl:grid-cols-4">
    @foreach([
        ['bi-box-seam', 'Orders', $stats['orders']],
        ['bi-truck', 'On the way', $stats['active']],
        ['bi-wallet2', 'Total spent', $setting->formatMoney($stats['spent'])],
        ['bi-heart', 'Wishlist', $stats['wishlist']],
    ] as [$icon, $label, $value])
        <div class="rounded-2xl bg-white p-4 md:p-5">
            <span class="soft grid h-10 w-10 place-items-center rounded-full text-brand"><i class="bi {{ $icon }}"></i></span>
            <p class="mt-3 truncate font-display text-xl font-extrabold md:text-2xl">{{ $value }}</p>
            <p class="text-sm text-neutral-500">{{ $label }}</p>
        </div>
    @endforeach
</div>

<div class="grid items-start gap-5 xl:grid-cols-[1fr_20rem]">
    <section class="rounded-2xl bg-white p-5 md:p-6">
        <div class="mb-3 flex items-center justify-between"><h2 class="font-display text-xl font-bold">Recent orders</h2><a href="{{ route('account.orders') }}" class="text-sm font-semibold text-brand">See all</a></div>
        @forelse($recent as $o)
            <a href="{{ route('account.orders.show', $o->order_number) }}" class="flex items-center gap-3 border-t py-3 first:border-0 hover:bg-neutral-50">
                <span class="flex -space-x-3">@foreach($o->items->take(3) as $i)<img src="{{ $i->image_url ?: asset('images/placeholder.png') }}" alt="" class="h-11 w-11 rounded-lg border-2 border-white object-cover">@endforeach</span>
                <span class="min-w-0 flex-1"><span class="block truncate text-sm font-semibold">{{ $o->order_number }}</span><span class="text-xs text-neutral-500">{{ $o->created_at->format('d M Y') }}</span></span>
                <span class="text-right"><span class="block text-sm font-bold">{{ $setting->formatMoney($o->total) }}</span>@include('partials.order-badge', ['status' => $o->status])</span>
            </a>
        @empty
            <div class="py-8 text-center"><p class="text-neutral-500">You have not ordered anything yet.</p><a href="{{ url('/shop') }}" class="btn btn-primary mt-4">Start shopping</a></div>
        @endforelse
    </section>

    <section class="rounded-2xl bg-white p-5 md:p-6">
        <h2 class="font-display text-xl font-bold">Default address</h2>
        @if($address)
            <p class="mt-3 font-semibold">{{ $address->full_name }}</p><p class="text-sm text-neutral-600">{{ $address->phone }}</p>
            <p class="mt-1 text-sm text-neutral-600">{{ $address->full_address }}</p>
            <a href="{{ route('account.addresses') }}" class="mt-4 inline-block text-sm font-semibold text-brand">Manage addresses</a>
        @else
            <p class="mt-2 text-sm text-neutral-500">No saved address yet. Add one to check out faster.</p>
            <a href="{{ route('account.addresses') }}" class="btn btn-ghost mt-4">Add address</a>
        @endif
    </section>
</div>
@endsection

@extends('layouts.account')
@section('page_title', 'Order ' . $order->order_number)

@section('account')
<div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-white p-5 md:p-6">
    <div>
        <a href="{{ route('account.orders') }}" class="text-sm text-neutral-500 hover:text-brand"><i class="bi bi-arrow-left"></i> All orders</a>
        <h1 class="font-display text-2xl font-extrabold">{{ $order->order_number }}</h1>
        <p class="text-sm text-neutral-500">Placed {{ $order->created_at->format('d M Y, h:i A') }}</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        @include('partials.order-badge', ['status' => $order->status])
        <form method="POST" action="{{ route('account.orders.reorder', $order->order_number) }}">@csrf<button class="btn btn-ghost !py-2 text-sm"><i class="bi bi-arrow-repeat"></i> Order again</button></form>
        @if($canCancel)
            <form method="POST" action="{{ route('account.orders.cancel', $order->order_number) }}" onsubmit="return confirm('Cancel this order?')">@csrf<button class="btn !py-2 text-sm text-red-600 ring-1 ring-red-200 hover:bg-red-50">Cancel order</button></form>
        @endif
    </div>
</div>

@if($order->courier && $order->tracking_code)
    <div class="rounded-2xl bg-white p-5 text-sm md:p-6">
        <p class="font-semibold"><i class="bi bi-truck text-brand"></i> {{ $order->courier->name }} · <span class="font-normal">Tracking code</span> {{ $order->tracking_code }}</p>
        @if($order->courier_status)<p class="mt-1 font-semibold text-brand">{{ $order->courier_status_label }}</p>@endif
        @if($order->tracking_url)<a href="{{ $order->tracking_url }}" target="_blank" rel="noopener" class="mt-1 inline-block font-semibold text-brand underline">Follow the parcel</a>@endif
    </div>
@endif

<div class="grid items-start gap-5 xl:grid-cols-[1fr_20rem]">
    <div class="space-y-5">
        <section class="rounded-2xl bg-white p-5 md:p-6">
            <h2 class="mb-2 font-display text-xl font-bold">Items</h2>
            <ul class="divide-y divide-black/5">
                @foreach($order->items as $item)
                    <li class="flex gap-3 py-3">
                        <img src="{{ $item->image_url ?: asset('images/placeholder.png') }}" alt="" class="h-16 w-16 rounded-lg object-cover">
                        <div class="min-w-0 flex-1">
                            @if($item->product_id && $item->product)<a href="{{ $item->product->url }}" class="font-medium hover:text-brand">{{ $item->name }}</a>@else<p class="font-medium">{{ $item->name }}</p>@endif
                            @if($item->variant_label)<p class="text-xs text-neutral-500">{{ $item->variant_label }}</p>@endif
                            <p class="text-sm text-neutral-500">{{ $setting->formatMoney($item->unit_price) }} × {{ $item->quantity }}</p>
                        </div>
                        <p class="font-semibold">{{ $setting->formatMoney($item->line_total) }}</p>
                    </li>
                @endforeach
            </ul>
        </section>
        <section class="rounded-2xl bg-white p-5 md:p-6">
            <h2 class="mb-4 font-display text-xl font-bold">Order progress</h2>
            @include('partials.order-timeline', ['order' => $order])
        </section>
        @include('account.orders._returns')
    </div>

    <aside class="space-y-5">
        @include('account.orders._pay')
        <section class="space-y-1 rounded-2xl bg-white p-5 text-sm">
            <h2 class="mb-1 font-display text-lg font-bold">Delivery</h2>
            <p class="font-semibold">{{ $order->shipping_name }}</p><p class="text-neutral-600">{{ $order->shipping_phone }}</p>
            <p class="text-neutral-600">{{ $order->shipping_address }}, {{ $order->shipping_area }}, {{ $order->shipping_city }}</p>
            <p class="pt-1 text-xs text-neutral-500">{{ $order->zone_label }}</p>
        </section>
    </aside>
</div>
@endsection

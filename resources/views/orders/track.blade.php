@extends('layouts.app')

@section('title', 'Track your order | ' . ($setting->site_name ?: config('app.name')))

@section('content')
<div class="wrap max-w-3xl py-8 md:py-14">
    <h1 class="font-display text-3xl font-extrabold">Track your order</h1>
    <p class="mt-1 text-neutral-600">Enter the order number from your confirmation and the mobile number you used.</p>

    <form method="GET" action="{{ route('order.track') }}" class="mt-6 grid gap-3 rounded-2xl bg-white p-5 sm:grid-cols-[1fr_1fr_auto]">
        <input name="order" value="{{ $number }}" required placeholder="ORD-261012-4821" class="rounded-xl border border-black/15 px-3.5 py-3 text-sm uppercase" aria-label="Order number">
        <input name="phone" value="{{ $phone }}" required inputmode="tel" placeholder="01XXXXXXXXX" class="rounded-xl border border-black/15 px-3.5 py-3 text-sm" aria-label="Mobile number">
        <button class="btn btn-primary">Track</button>
    </form>

    @if($searched && ! $order)
        <p class="mt-4 rounded-xl bg-red-50 p-4 text-sm text-red-700">We could not find an order with these details. Check the order number and the mobile number and try again.</p>
    @endif

    @if($order)
        <div class="mt-6 space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-white p-5">
                <div><p class="text-xs text-neutral-500">Order</p><p class="font-display text-xl font-bold">{{ $order->order_number }}</p>
                    <p class="text-sm text-neutral-500">Placed {{ $order->created_at->format('d M Y, h:i A') }}</p></div>
                @include('partials.order-badge', ['status' => $order->status])
            </div>

            @if($order->courier && $order->tracking_code)
                <div class="rounded-2xl bg-white p-5 text-sm">
                    <p class="font-semibold">Courier: {{ $order->courier->name }}</p>
                    <p class="text-neutral-600">Tracking code <b>{{ $order->tracking_code }}</b></p>
                    @if($order->courier_status)<p class="mt-1 font-semibold text-brand">{{ $order->courier_status_label }}</p>@endif
                    @if($order->tracking_url)<a href="{{ $order->tracking_url }}" target="_blank" rel="noopener" class="mt-2 inline-block font-semibold text-brand underline">Follow the parcel</a>@endif
                </div>
            @endif

            <div class="rounded-2xl bg-white p-5 md:p-6">
                <h2 class="mb-4 font-display text-xl font-bold">Progress</h2>
                @include('partials.order-timeline', ['order' => $order])
            </div>

            <div class="rounded-2xl bg-white p-5 text-sm md:p-6">
                <h2 class="mb-3 font-display text-xl font-bold">Items</h2>
                <ul class="divide-y divide-black/5">
                    @foreach($order->items as $item)
                        <li class="flex justify-between gap-3 py-2"><span>{{ $item->name }} @if($item->variant_label)<span class="text-neutral-500">({{ $item->variant_label }})</span>@endif × {{ $item->quantity }}</span><b>{{ $setting->formatMoney($item->line_total) }}</b></li>
                    @endforeach
                </ul>
                <p class="mt-3 flex justify-between border-t pt-3 text-base font-bold"><span>Total</span><span class="text-brand">{{ $setting->formatMoney($order->total) }}</span></p>
            </div>
        </div>
    @endif
</div>
@endsection

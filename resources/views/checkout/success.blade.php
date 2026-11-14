@extends('layouts.app')

@section('title', 'Order placed | ' . ($setting->site_name ?: config('app.name')))

@php
    $g = $order->paymentGateway;
    $details = ($g && ! \App\Support\OnlineGateways::isOnline($g->driver)) ? collect(\App\Models\PaymentGateway::DRIVERS[$g->driver]['fields'] ?? [])->where('secret', false)
        ->map(fn ($f) => [$f['label'], $g->credentials[$f['key']] ?? null])->filter(fn ($r) => $r[1])->values() : collect();
    $needsPayment = $g && $g->driver !== 'cod' && ! \App\Support\OnlineGateways::isOnline($g->driver) && $order->payment_status !== 'paid';
    $flag      = request('payment');
    $canPayOnline = $g && \App\Support\OnlineGateways::isOnline($g->driver) && $order->due_amount > 0 && ! $order->status->restock_items;
@endphp

@section('content')
<div class="wrap max-w-3xl py-8 md:py-14">
    <div class="rounded-2xl bg-white p-6 text-center md:p-10">
        <span class="soft mx-auto grid h-16 w-16 place-items-center rounded-full text-3xl text-brand"><i class="bi bi-check-lg"></i></span>
        <h1 class="mt-4 font-display text-3xl font-extrabold">Thank you, your order is placed</h1>
        <p class="mt-2 text-neutral-600">Order number <b class="text-ink">{{ $order->order_number }}</b>. We will call or message <b>{{ $order->customer_phone }}</b> to confirm it.</p>
        <div class="mt-4 flex justify-center">@include('partials.order-badge', ['status' => $order->status])</div>
    </div>

    @if($needsPayment)
        <div class="mt-4 space-y-2 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
            <p class="font-semibold">Complete your payment ({{ $g->name }}) of {{ $setting->formatMoney($order->due_amount) }}</p>
            @if($g->instructions)<p class="whitespace-pre-line">{{ $g->instructions }}</p>@endif
            @if($details->isNotEmpty())
                <dl class="grid gap-x-6 gap-y-1 sm:grid-cols-2">@foreach($details as [$label, $value])<div><dt class="text-xs opacity-70">{{ $label }}</dt><dd class="font-semibold">{{ $value }}</dd></div>@endforeach</dl>
            @endif
            <p>{{ $order->transaction_id ? 'Transaction ID received: ' . $order->transaction_id . '. We will verify it shortly.' : 'After sending the money, reply to our confirmation call or message with the transaction ID.' }}</p>
        </div>
    @endif

    @if($flag === 'paid' || ($order->payment_status === 'paid' && \App\Support\OnlineGateways::isOnline($g?->driver)))
        <div class="mt-4 rounded-2xl border border-green-200 bg-green-50 p-5 text-sm text-green-800"><i class="bi bi-check-circle-fill"></i> <b>Payment received.</b> Thank you, we will start preparing your order.</div>
    @elseif($flag === 'review')
        <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900"><b>We received your payment and are checking it.</b> We will confirm the order shortly.</div>
    @elseif(in_array($flag, ['failed', 'cancelled', 'error'], true) && $canPayOnline)
        <div class="mt-4 rounded-2xl border border-red-200 bg-red-50 p-5 text-sm text-red-800">
            <b>{{ $flag === 'cancelled' ? 'You cancelled the payment.' : 'The payment was not completed.' }}</b> Your order is saved, you can try again.
        </div>
    @endif

    @if($canPayOnline)
        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-white p-5">
            <div><p class="font-semibold">{{ $setting->formatMoney($order->due_amount) }} is still to pay</p><p class="text-sm text-neutral-500">Pay safely with {{ \App\Support\OnlineGateways::label($g->driver) }}.</p></div>
            <a href="{{ \App\Support\OnlineGateways::startUrl($order, null, 1440) }}" class="btn btn-primary"><i class="bi bi-credit-card"></i> Pay online now</a>
        </div>
    @endif

    <div class="mt-4 rounded-2xl bg-white p-5 md:p-6">
        <h2 class="mb-4 font-display text-xl font-bold">Order details</h2>
        <ul class="divide-y divide-black/5">
            @foreach($order->items as $item)
                <li class="flex gap-3 py-3 text-sm">
                    <img src="{{ $item->image_url ?: asset('images/placeholder.png') }}" alt="" class="h-14 w-14 rounded-lg object-cover">
                    <div class="min-w-0 flex-1"><p class="font-medium">{{ $item->name }}</p>
                        <p class="text-xs text-neutral-500">{{ $item->variant_label }} @if($item->variant_label) · @endif Qty {{ $item->quantity }}</p></div>
                    <p class="font-semibold">{{ $setting->formatMoney($item->line_total) }}</p>
                </li>
            @endforeach
        </ul>
        <dl class="mt-3 space-y-1.5 border-t pt-4 text-sm">
            <div class="flex justify-between"><dt class="text-neutral-600">Subtotal</dt><dd>{{ $setting->formatMoney($order->subtotal) }}</dd></div>
            @if($order->discount_amount > 0)<div class="flex justify-between text-green-700"><dt>Discount @if($order->coupon_code)({{ $order->coupon_code }})@endif</dt><dd>-{{ $setting->formatMoney($order->discount_amount) }}</dd></div>@endif
            <div class="flex justify-between"><dt class="text-neutral-600">Delivery ({{ $order->zone_label }})</dt><dd>{{ $order->shipping_charge > 0 ? $setting->formatMoney($order->shipping_charge) : 'Free' }}</dd></div>
            @if($order->payment_fee > 0)<div class="flex justify-between"><dt class="text-neutral-600">Payment fee</dt><dd>{{ $setting->formatMoney($order->payment_fee) }}</dd></div>@endif
            <div class="flex justify-between border-t pt-3 text-lg font-bold"><dt>Total</dt><dd class="text-brand">{{ $setting->formatMoney($order->total) }}</dd></div>
        </dl>
        <div class="mt-5 grid gap-4 border-t pt-4 text-sm sm:grid-cols-2">
            <div><p class="text-xs text-neutral-500">Delivering to</p><p class="font-medium">{{ $order->shipping_name }} · {{ $order->shipping_phone }}</p><p class="text-neutral-600">{{ $order->shipping_address }}, {{ $order->shipping_area }}, {{ $order->shipping_city }}</p></div>
            <div><p class="text-xs text-neutral-500">Payment</p><p class="font-medium">{{ $g?->name }}</p><p class="text-neutral-600">{{ $order->payment_status_label }}</p></div>
        </div>
    </div>

    <div class="mt-6 flex flex-col justify-center gap-3 sm:flex-row">
        @auth
            <a href="{{ route('account.orders.show', $order->order_number) }}" class="btn btn-primary">View in my orders</a>
        @else
            <a href="{{ route('order.track', ['order' => $order->order_number, 'phone' => $order->customer_phone]) }}" class="btn btn-primary">Track this order</a>
        @endauth
        <a href="{{ url('/shop') }}" class="btn btn-ghost">Continue shopping</a>
    </div>
</div>
@endsection

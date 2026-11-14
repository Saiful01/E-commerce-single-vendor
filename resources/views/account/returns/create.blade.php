@extends('layouts.account')
@section('page_title', 'Return items')

@section('account')
<div class="rounded-2xl bg-white p-5 md:p-6">
    <a href="{{ route('account.orders.show', $order->order_number) }}" class="text-sm text-neutral-500 hover:text-brand"><i class="bi bi-arrow-left"></i> Back to order</a>
    <h1 class="font-display text-2xl font-extrabold">Return items</h1>
    <p class="text-sm text-neutral-600">Order {{ $order->order_number }}. Pick what you want to send back. You have {{ config('shop.return_window_days') }} days from delivery.</p>
</div>

<form method="POST" action="{{ route('account.orders.return.store', $order->order_number) }}" class="space-y-5 rounded-2xl bg-white p-5 md:p-6">
    @csrf
    @if($errors->any())<ul class="rounded-lg bg-red-50 p-3 text-sm text-red-700">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>@endif

    <div class="space-y-3">
        @foreach($order->items as $item)
            @php $left = $remaining[$item->id] ?? 0; @endphp
            <div class="flex items-center gap-3 rounded-xl border border-black/10 p-3 {{ $left < 1 ? 'opacity-50' : '' }}">
                <img src="{{ $item->image_url ?: asset('images/placeholder.png') }}" alt="" class="h-16 w-16 rounded-lg object-cover">
                <div class="min-w-0 flex-1 text-sm">
                    <p class="font-medium">{{ $item->name }}</p>
                    @if($item->variant_label)<p class="text-xs text-neutral-500">{{ $item->variant_label }}</p>@endif
                    <p class="text-xs text-neutral-500">Bought {{ $item->quantity }} · {{ $setting->formatMoney($item->unit_price) }} each</p>
                </div>
                <label class="text-right text-xs text-neutral-500">Return
                    <select name="qty[{{ $item->id }}]" @disabled($left < 1) class="mt-1 block rounded-lg border border-black/15 px-2 py-2 text-sm text-ink">
                        @for($i = 0; $i <= $left; $i++)<option value="{{ $i }}" @selected((int) old('qty.' . $item->id, 0) === $i)>{{ $i }}</option>@endfor
                    </select></label>
            </div>
        @endforeach
    </div>

    <label class="block text-sm font-medium">Why are you returning?
        <select name="reason" required class="mt-1.5 w-full rounded-xl border border-black/15 px-3.5 py-3 text-sm">
            <option value="">Choose a reason</option>
            @foreach(config('shop.return_reasons') as $k => $text)<option value="{{ $k }}" @selected(old('reason') === $k)>{{ $text }}</option>@endforeach
        </select></label>
    <label class="block text-sm font-medium">Tell us more <span class="font-normal text-neutral-500">(optional)</span>
        <textarea name="details" rows="3" maxlength="1000" class="mt-1.5 w-full rounded-xl border border-black/15 px-3.5 py-3 text-sm">{{ old('details') }}</textarea></label>

    <button class="btn btn-primary">Send return request</button>
    <p class="text-xs text-neutral-500">We will review it and contact you. Money is refunded after we receive the items.</p>
</form>
@endsection

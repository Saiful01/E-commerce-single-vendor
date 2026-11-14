@extends('layouts.account')
@section('page_title', 'My orders')

@section('account')
<div class="rounded-2xl bg-white p-5 md:p-6">
    <h1 class="font-display text-2xl font-extrabold">My orders</h1>
    <div class="no-scrollbar -mx-1 mt-4 flex gap-2 overflow-x-auto px-1">
        <a href="{{ route('account.orders') }}" class="shrink-0 rounded-full px-4 py-1.5 text-sm font-medium {{ ! $statusId ? 'bg-brand text-white' : 'bg-neutral-100' }}">All</a>
        @foreach($statuses as $s)
            <a href="{{ route('account.orders', ['status' => $s->id]) }}" class="shrink-0 rounded-full px-4 py-1.5 text-sm font-medium {{ (string) $statusId === (string) $s->id ? 'bg-brand text-white' : 'bg-neutral-100' }}">{{ $s->name }}</a>
        @endforeach
    </div>
</div>

@forelse($orders as $o)
    <a href="{{ route('account.orders.show', $o->order_number) }}" class="block rounded-2xl bg-white p-4 transition hover:ring-2 hover:ring-[var(--primary)] md:p-5">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div><p class="font-semibold">{{ $o->order_number }}</p><p class="text-xs text-neutral-500">{{ $o->created_at->format('d M Y, h:i A') }}</p></div>
            @include('partials.order-badge', ['status' => $o->status])
        </div>
        <div class="mt-3 flex items-center gap-3">
            <span class="flex -space-x-3">@foreach($o->items->take(4) as $i)<img src="{{ $i->image_url ?: asset('images/placeholder.png') }}" alt="" class="h-12 w-12 rounded-lg border-2 border-white object-cover">@endforeach</span>
            <span class="text-sm text-neutral-600">{{ $o->items->sum('quantity') }} {{ Str::plural('item', $o->items->sum('quantity')) }}</span>
            <span class="ml-auto text-right"><span class="block font-display text-lg font-bold text-brand">{{ $setting->formatMoney($o->total) }}</span>
                <span class="text-xs {{ $o->payment_status === 'paid' ? 'text-green-600' : 'text-neutral-500' }}">{{ $o->payment_status_label }}</span></span>
        </div>
    </a>
@empty
    <div class="rounded-2xl bg-white px-6 py-14 text-center"><i class="bi bi-box-seam text-5xl text-neutral-300"></i>
        <p class="mt-3 font-semibold">No orders found</p><a href="{{ url('/shop') }}" class="btn btn-primary mt-5">Start shopping</a></div>
@endforelse

{{ $orders->links() }}
@endsection

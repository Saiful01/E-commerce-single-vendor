{{-- Needs: $order, $setting --}}
@php
    $returns = $order->returns()->with('items.orderItem')->get();
    $check   = app(\App\Services\ReturnService::class)->eligibility($order);
    $tone    = ['requested' => 'text-amber-700 bg-amber-50', 'approved' => 'text-indigo-700 bg-indigo-50', 'rejected' => 'text-red-700 bg-red-50', 'completed' => 'text-green-700 bg-green-50'];
@endphp

@if($returns->isNotEmpty() || $order->delivered_at)
<section class="rounded-2xl bg-white p-5 md:p-6">
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <h2 class="font-display text-xl font-bold">Returns</h2>
        @if($check['ok'])
            <a href="{{ route('account.orders.return', $order->order_number) }}" class="btn btn-ghost !py-2 text-sm"><i class="bi bi-arrow-return-left"></i> Return items</a>
        @endif
    </div>

    @unless($check['ok'])<p class="mb-3 text-sm text-neutral-500">{{ $check['reason'] }}</p>@endunless

    @foreach($returns as $r)
        <div class="mb-3 rounded-xl border border-black/10 p-4 text-sm last:mb-0">
            <div class="flex items-center justify-between gap-2">
                <p class="font-semibold">{{ $r->return_number }}</p>
                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $tone[$r->status] }}">{{ $r->status_label }}</span>
            </div>
            <ul class="mt-2 text-neutral-600">@foreach($r->items as $ri)<li>{{ $ri->orderItem->name }} × {{ $ri->quantity }}</li>@endforeach</ul>
            <p class="mt-1 text-xs text-neutral-500">{{ $r->reason_label }} · {{ $r->created_at->format('d M Y') }}</p>
            @if($r->status === 'completed' && $r->refund_amount > 0)<p class="mt-1 font-semibold text-green-700">Refunded {{ $setting->formatMoney($r->refund_amount) }}</p>@endif
            @if($r->status === 'rejected' && $r->admin_note)<p class="mt-1 text-red-700">Reason: {{ $r->admin_note }}</p>@endif
        </div>
    @endforeach
</section>
@endif

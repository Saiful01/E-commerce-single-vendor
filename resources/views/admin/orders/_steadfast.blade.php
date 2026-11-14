{{-- Needs: $order. Put it in admin/orders/show.blade.php, under the payment panel:  @include('admin.orders._steadfast', ['order' => $order]) --}}
@php
    $courier  = \App\Services\Couriers\SteadfastService::activeCourier();
    $booked   = $order->courier_consignment_id;
    $canSend  = $courier && (! $booked || $order->courier_status === 'cancelled') && ! $order->status->restock_items;
    $events   = $booked ? \App\Models\CourierEvent::where('order_id', $order->id)->latest('id')->take(8)->get() : collect();
    $money    = \App\Models\SiteSetting::current();
    $balance  = $courier ? (new \App\Services\Couriers\SteadfastService($courier))->balance() : null;
    $tone     = ['delivered' => 'bg-emerald-50 text-emerald-700', 'partial_delivered' => 'bg-amber-50 text-amber-700', 'cancelled' => 'bg-rose-50 text-rose-700', 'hold' => 'bg-amber-50 text-amber-700'];
    $input    = 'w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500';
    $trackUrl = $order->tracking_code && $order->courier ? $order->courier->trackingUrlFor($order->tracking_code) : null;
@endphp

@if($courier || $booked)
<section class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h3 class="text-sm font-bold text-slate-800">Steadfast courier</h3>
            @if($balance !== null)<p class="text-[11px] text-slate-500 mt-0.5">Your Steadfast balance: <span class="font-semibold text-slate-700">{{ $money->formatMoney($balance) }}</span></p>@endif
        </div>
        @if($order->courier_status)
            <span class="shrink-0 px-2.5 py-1 rounded-md text-[10px] font-bold {{ $tone[$order->courier_status] ?? 'bg-indigo-50 text-indigo-700' }}">{{ $order->courier_status_label }}</span>
        @endif
    </div>

    {{-- Parcel is booked --}}
    @if($booked)
        <dl class="grid grid-cols-2 gap-3 text-xs">
            <div><dt class="text-slate-400">Consignment ID</dt><dd class="font-mono font-semibold text-slate-800">{{ $booked }}</dd></div>
            <div><dt class="text-slate-400">Tracking code</dt>
                <dd class="font-mono font-semibold text-slate-800">@if($trackUrl)<a href="{{ $trackUrl }}" target="_blank" rel="noopener" class="text-indigo-600 hover:text-indigo-800">{{ $order->tracking_code }}</a>@else{{ $order->tracking_code ?: '-' }}@endif</dd></div>
            <div class="col-span-2"><dt class="text-slate-400">Sent</dt><dd class="font-semibold text-slate-800">{{ $order->courier_booked_at?->format('d M Y, h:i A') }}</dd></div>
        </dl>

        @if($courier && $order->courier_status !== 'delivered')
            <form action="{{ route('admin.orders.steadfast.refresh', $order) }}" method="POST">@csrf
                <button class="w-full px-4 py-2.5 border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50">Check status now</button></form>
        @endif

        @if($order->courier_status === 'cancelled')
            <p class="rounded-xl bg-rose-50 border border-rose-200 p-3 text-[11px] text-rose-700">Steadfast cancelled this parcel. When it is back with you, set the order to Cancelled so the items go back in stock, or send it again below.</p>
        @endif
    @endif

    {{-- Send --}}
    @if($canSend)
        <form action="{{ route('admin.orders.steadfast.book', $order) }}" method="POST" class="space-y-3 {{ $booked ? 'border-t border-slate-100 pt-4' : '' }}">
            @csrf
            <div class="rounded-xl bg-indigo-50/60 border border-indigo-100 p-3 text-[11px] text-indigo-900 space-y-0.5">
                <p><span class="font-semibold">{{ $order->shipping_name }}</span> · {{ $order->shipping_phone }}</p>
                <p>{{ $order->shipping_address }}, {{ $order->shipping_area }}, {{ $order->shipping_city }}</p>
                <p class="pt-1 font-bold">Cash to collect: {{ $money->formatMoney($order->due_amount) }}@if($order->due_amount <= 0) <span class="font-normal">(already paid)</span>@endif</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Delivery</label>
                <select name="delivery_type" class="{{ $input }}">
                    <option value="0">Home delivery</option>
                    <option value="1">Customer picks up from the Steadfast point</option>
                </select>
            </div>
            <input type="text" name="note" maxlength="300" placeholder="Note for the rider (optional)" class="{{ $input }}">
            <label class="flex items-center gap-2 text-xs font-semibold text-slate-700"><input type="checkbox" name="mark_shipped" value="1" @checked(config('shop.steadfast.mark_shipped_on_booking', true)) class="rounded border-slate-300 text-indigo-600"> Move the order to Shipped</label>
            <button class="w-full px-4 py-2.5 bg-indigo-600 text-white font-semibold text-xs rounded-xl shadow-lg shadow-indigo-600/30 hover:bg-indigo-700 transition-all">{{ $booked ? 'Send again to Steadfast' : 'Send to Steadfast' }}</button>
        </form>
    @elseif(! $booked && $order->status->restock_items)
        <p class="text-[11px] text-slate-500">This order is {{ $order->status->name }}, so it cannot be sent.</p>
    @endif

    {{-- What the courier reported --}}
    @if($events->isNotEmpty())
        <div class="border-t border-slate-100 pt-4">
            <p class="text-xs font-bold text-slate-800 mb-2">Courier updates</p>
            <ul class="space-y-2">
                @foreach($events as $e)
                    <li class="text-[11px]"><p class="text-slate-700">{{ $e->message }}</p><p class="text-slate-400">{{ $e->created_at->format('d M, h:i A') }}</p></li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($courier)
        <p class="text-[10px] text-slate-400 break-all">Webhook address for the Steadfast dashboard: {{ url('/steadfast/webhook') }}</p>
    @endif
</section>
@endif
